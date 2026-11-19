<?php
// Validate the trusted TAKP reference dump and check its import; never import here.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function database_fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function private_write(string $path, string $contents): void
{
    if (@file_put_contents($path, $contents) !== strlen($contents) || !@chmod($path, 0600)) {
        database_fail('Could not write private database maintenance files.');
    }
}

function application_settings(): array
{
    ob_start();
    try {
        require '/app/config/application.php';
    } catch (Throwable $error) {
        ob_end_clean();
        database_fail('Cannot load config/application.php; check its PHP settings.');
    }
    if (ob_get_clean() !== '') {
        database_fail('config/application.php must contain PHP settings only, with no output.');
    }
    if (($dbhost ?? null) !== 'localhost'
        || !isset($dbname) || !is_string($dbname)
        || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/', $dbname)
        || in_array(strtolower($dbname), ['mysql', 'sys', 'information_schema', 'performance_schema'], true)
        || !isset($dbuser) || !is_string($dbuser)
        || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,31}\z/', $dbuser)
        || in_array(strtolower($dbuser), ['root', 'mysql', 'mariadb'], true)
        || !isset($dbpasswd) || !is_string($dbpasswd) || $dbpasswd === ''
        || $dbpasswd === 'CHANGE_ME' || str_contains($dbpasswd, "\0")) {
        database_fail('Use localhost and valid application database/user/password settings; run maintenance setup first.');
    }
    return [$dbname, $dbuser, $dbpasswd];
}

function reference_tables(string $directory): array
{
    $drop = @fopen($directory . '/drop.sql', 'r');
    $main = @fopen($directory . '/alkabor.sql', 'r');
    if ($drop === false || $main === false) {
        database_fail('Could not read the extracted drop.sql and alkabor.sql files.');
    }
    $drops = [];
    while (($line = fgets($drop)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (!preg_match('/\ADROP TABLE IF EXISTS (`?)([A-Za-z][A-Za-z0-9_]{0,63})\1;\z/', $line, $match)
            || isset($drops[$match[2]])) {
            database_fail('drop.sql must contain unique, single-table DROP TABLE IF EXISTS statements only.');
        }
        $drops[$match[2]] = true;
    }
    if (!feof($drop)) {
        database_fail('Could not finish reading drop.sql.');
    }
    fclose($drop);

    // This checks the upstream dump layout, not arbitrary or hostile SQL.
    // Stream INSERT lines instead of loading the entire reference dump into PHP.
    $creates = [];
    $inTable = false;
    $lineNumber = 0;
    while (($line = fgets($main)) !== false) {
        ++$lineNumber;
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            continue;
        }
        if ($inTable) {
            if (preg_match('/\A\) ENGINE=(?:InnoDB|MyISAM|Aria)(?: AUTO_INCREMENT=[0-9]+)? DEFAULT CHARSET=[A-Za-z0-9_]+(?: COLLATE=[A-Za-z0-9_]+)?(?: PACK_KEYS=[01])?(?: ROW_FORMAT=[A-Za-z0-9_]+)?;\z/', $line)) {
                $inTable = false;
                continue;
            }
            if (preg_match('/\A  (?:`[A-Za-z_][A-Za-z0-9_]*` |PRIMARY KEY |UNIQUE KEY |KEY |CONSTRAINT )/', $line)) {
                continue;
            }
        } elseif (preg_match('/\ACREATE TABLE `([A-Za-z][A-Za-z0-9_]{0,63})` \(\z/', $line, $match)) {
            $name = $match[1];
            if (isset($creates[$name]) || strtolower($name) === 'eqbnews'
                || str_starts_with(strtolower($name), 'quest_')) {
                database_fail('Reference SQL contains a duplicate or protected application table.');
            }
            $creates[$name] = true;
            $inTable = true;
            continue;
        } elseif (preg_match('/\AINSERT INTO `([A-Za-z][A-Za-z0-9_]{0,63})` VALUES \(/', $line, $match)
            && isset($creates[$match[1]]) && str_ends_with($line, ';')) {
            continue;
        } elseif (preg_match('/\A\/\*!40101 SET (?:@saved_cs_client\s*= @@character_set_client|character_set_client = (?:utf8|utf8mb4|@saved_cs_client)) \*\/;\z/', $line)) {
            continue;
        }
        database_fail("Unexpected reference SQL format at line $lineNumber.");
    }
    if (!feof($main) || $inTable) {
        database_fail('Reference SQL is incomplete or could not be read.');
    }
    fclose($main);
    $tables = array_keys($creates);
    $dropTables = array_keys($drops);
    sort($tables);
    sort($dropTables);
    if ($tables !== $dropTables
        || array_diff(['items', 'npc_types', 'zone', 'races', 'spells_new'], $tables)) {
        database_fail('Reference CREATE/DROP table lists differ or required core tables are missing.');
    }
    return $tables;
}

umask(0077);
if ($argc !== 3 || !in_array($argv[1], ['prepare', 'verify'], true)
    || !is_dir($argv[2]) || !is_writable($argv[2])) {
    database_fail('Usage: php /app/ops/database.php prepare|verify WORKDIR');
}
$action = $argv[1];
$directory = realpath($argv[2]);
[$dbname, $dbuser, $dbpasswd] = application_settings();
$tables = reference_tables($directory);
if ($action === 'verify' && @file_get_contents($directory . '/database.txt') !== $dbname . "\n") {
    database_fail('The configured database differs from the prepared import target.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$stage = 'connect to the application database';
try {
    $db = new mysqli('localhost', $dbuser, $dbpasswd, $dbname, 0, '/app/run/mysql/mysql.sock');
    $db->set_charset('latin1');
    $existing = array_column($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM), 0);
    $report = ['stage' => $action === 'prepare' ? 'prepared' : 'verified', 'database' => $dbname,
        'tables' => $tables, 'existing_table_count' => count($existing)];
    if ($action === 'prepare') {
        // Native option-file quoting; never send credentials in command arguments.
        $password = strtr($dbpasswd, ["\\" => "\\\\", '"' => '\\"', "\n" => '\\n',
            "\r" => '\\r', "\t" => '\\t', "\x08" => '\\b', ' ' => '\\s']);
        private_write($directory . '/client.cnf', "[client]\nhost=localhost\nuser=\"$dbuser\"\n"
            . "password=\"$password\"\nprotocol=socket\nsocket=/app/run/mysql/mysql.sock\n"
            . "default-character-set=latin1\n[mariadb-client]\ndatabase=\"$dbname\"\n");
        unset($password);
        private_write($directory . '/tables.txt', implode("\n", $tables) . "\n");
        private_write($directory . '/database.txt', $dbname . "\n");
        private_write($directory . '/existing-table-count.txt', count($existing) . "\n");
        echo 'Prepared ', count($tables), ' reference tables; existing application tables: ', count($existing), ".\n";
    } else {
        if (array_diff($tables, $existing)) {
            database_fail('Import verification failed: one or more reference tables are missing.');
        }
        $stage = 'count imported core table rows';
        foreach (['items', 'npc_types', 'zone', 'races', 'spells_new'] as $table) {
            $count = (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
            if ($count === 0) {
                database_fail("Import verification failed: $table is empty.");
            }
            $report['core_row_counts'][$table] = $count;
            echo "$table: $count rows\n";
        }
        echo 'Verified ', count($tables), " imported reference tables.\n";
    }
    $db->close();
} catch (mysqli_sql_exception $error) {
    database_fail("Could not $stage (MariaDB error {$error->getCode()}); check the application settings and MariaDB status.");
}
private_write($directory . '/result.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");

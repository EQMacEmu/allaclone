<?php
// Supply private settings to the existing importer, without passwords in argv.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
function quest_fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}
ob_start();
try {
    require '/app/config/application.php';
} catch (Throwable $error) {
    ob_end_clean();
    quest_fail('Cannot load config/application.php; check its PHP settings.');
}
if (ob_get_clean() !== '') {
    quest_fail('config/application.php must contain PHP settings only, with no output.');
}
if (($dbhost ?? null) !== 'localhost'
    || !isset($dbname, $dbuser, $dbpasswd) || !is_string($dbname) || !is_string($dbuser) || !is_string($dbpasswd)
    || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/', $dbname)
    || in_array(strtolower($dbname), ['mysql', 'sys', 'information_schema', 'performance_schema'], true)
    || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,31}\z/', $dbuser)
    || in_array(strtolower($dbuser), ['root', 'mysql', 'mariadb'], true)
    || $dbpasswd === '' || $dbpasswd === 'CHANGE_ME' || str_contains($dbpasswd, "\0")) {
    quest_fail('Use the local application database/account; run maintenance setup first.');
}
if (($argv[1] ?? '') === '--database') {
    echo $dbname, "\n";
    exit;
}
if (($argv[1] ?? '') === '--verify') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = new mysqli('localhost', $dbuser, $dbpasswd, $dbname, 0, '/app/run/mysql/mysql.sock');
        $db->set_charset('latin1');
        $counts = [];
        foreach (['quest_npc', 'quest_trade', 'quest_trade_item', 'quest_faction_hit',
            'quest_faction_req', 'quest_say_branch', 'quest_spawn', 'quest_death_spawn',
            'quest_items', 'quest_import_meta'] as $table) {
            $counts[$table] = (int) $db->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0];
        }
        $meta = $db->query('SELECT * FROM quest_import_meta WHERE id=1')->fetch_assoc();
        if (!$meta || $counts['quest_import_meta'] !== 1 || $counts['quest_npc'] === 0
            || $counts['quest_trade'] === 0 || $counts['quest_items'] === 0
            || $meta['zones'] !== '*' || $meta['quest_root'] !== '/app/source/quests') {
            quest_fail('Quest verification failed: missing or incomplete full-import results.');
        }
        foreach (['npc_count' => 'quest_npc', 'trade_count' => 'quest_trade',
            'say_count' => 'quest_say_branch', 'spawn_count' => 'quest_spawn'] as $field => $table) {
            if ((int) $meta[$field] !== $counts[$table]) {
                quest_fail('Quest verification failed: manifest and table counts differ.');
            }
        }
        echo json_encode(['table_counts' => $counts, 'import' => $meta], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";
        $db->close();
    } catch (mysqli_sql_exception $error) {
        quest_fail("Could not verify quest tables (MariaDB error {$error->getCode()}).");
    }
    exit;
}
// getopt() in the required CLI importer reads this process's --quests/--dry-run.
// Populate every setting explicitly, so its root/root/peq defaults are never used.
putenv('ALLACLONE_DB_HOST=' . $dbhost);
putenv('ALLACLONE_DB_USER=' . $dbuser);
putenv('ALLACLONE_DB_PASS=' . $dbpasswd);
putenv('ALLACLONE_DB_NAME=' . $dbname);
require '/source/quests/import_lua_quests.php';

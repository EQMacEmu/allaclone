<?php
// Called by host-side maintenance setup, as root inside the running container.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function setup_fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

umask(0077);
$config = '/app/config/application.php';
if (!file_exists($config) && !is_link($config)) {
    $settings = file_get_contents('/app/config/application.example.php');
    if ($settings === false) {
        setup_fail('Could not read config/application.example.php.');
    }
    // Hex is safe inside a PHP single-quoted string; keep the password private.
    $settings = str_replace("\$dbpasswd = 'CHANGE_ME';",
        "\$dbpasswd = '" . bin2hex(random_bytes(32)) . "';", $settings, $replacements);
    if ($replacements !== 1) {
        setup_fail('Expected exactly one dbpasswd placeholder in config/application.example.php.');
    }
    $file = fopen($config, 'x'); // Exclusive creation: never overwrite existing settings.
    if ($file === false) {
        setup_fail('Could not create private config/application.php.');
    }
    $written = fwrite($file, $settings);
    fclose($file);
    if ($written !== strlen($settings) || !chgrp($config, 'www-data') || !chmod($config, 0640)) {
        setup_fail('Could not create private config/application.php. Check its permissions.');
    }
    unset($settings);
    echo "Created config/application.php with a random password (root:www-data, mode 0640).\n";
}
if (!is_file($config) || !is_readable($config)) {
    setup_fail('config/application.php must be a readable PHP settings file.');
}
// Reject accidental text outside PHP tags without exposing private settings.
ob_start();
try {
    require $config;
} catch (Throwable $error) {
    ob_end_clean();
    setup_fail('Cannot load config/application.php; check its PHP syntax/settings near line '
        . $error->getLine() . '.');
}
if (ob_get_clean() !== '') {
    setup_fail('config/application.php must contain PHP settings only, with no output.');
}

if (($dbhost ?? null) !== 'localhost') {
    setup_fail('setup supports dbhost=localhost and the container MariaDB socket only.');
}
if (!isset($dbname) || !is_string($dbname)
    || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/', $dbname)
    || in_array(strtolower($dbname), array('mysql', 'sys', 'information_schema', 'performance_schema'), true)) {
    setup_fail('Use a non-system database name: a letter followed by letters, digits or underscores (up to 64).');
}
if (!isset($dbuser) || !is_string($dbuser)
    || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,31}\z/', $dbuser)
    || in_array(strtolower($dbuser), array('root', 'mysql', 'mariadb'), true)) {
    setup_fail('Use a dedicated application user: a letter followed by letters, digits or underscores (up to 32).');
}
if (!isset($dbpasswd) || !is_string($dbpasswd) || $dbpasswd === '' || $dbpasswd === 'CHANGE_ME') {
    setup_fail('Set dbpasswd in config/application.php, then rerun ./maintenance setup. No database changes made.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$socket = '/app/run/mysql/mysql.sock';
$stage = 'connect as MariaDB root through the local socket';
try {
    $root = new mysqli('localhost', 'root', '', null, 0, $socket);
    $root->set_charset('utf8mb4');
    $exists = $root->query("SELECT COUNT(*) FROM mysql.user WHERE User = '$dbuser' AND Host = 'localhost'")
        ->fetch_row()[0] > 0;

    // Verify an existing password before creating a DB or changing any grants.
    if ($exists) {
        $stage = 'authenticate the existing application user; its password was not changed';
        $app = new mysqli('localhost', $dbuser, $dbpasswd, null, 0, $socket);
        $app->close();
    }

    $stage = 'create the application database if missing';
    $root->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET latin1 COLLATE latin1_swedish_ci");
    if (!$exists) {
        $stage = 'create the application user';
        $password = $root->real_escape_string($dbpasswd);
        $root->query("CREATE USER '$dbuser'@'localhost' IDENTIFIED BY '$password'");
        unset($password);
    }

    // GRANT treats underscores as wildcards, even inside backticks.
    $grantDatabase = str_replace('_', '\\_', $dbname);
    $stage = 'grant privileges on the application database';
    $root->query("GRANT ALL PRIVILEGES ON `$grantDatabase`.* TO '$dbuser'@'localhost'");

    $stage = 'verify the application database connection';
    $app = new mysqli('localhost', $dbuser, $dbpasswd, $dbname, 0, $socket);
    $app->set_charset('latin1');
    $app->query('SELECT 1');
    $app->close();
    $root->close();
} catch (mysqli_sql_exception $error) {
    // Raw exception text can contain SQL/passwords; report the operation and code.
    setup_fail("Could not $stage (MariaDB error {$error->getCode()}). Check config/application.php and MariaDB status.");
}

echo "Application setup verified: $dbuser@localhost can access $dbname.\n";
echo "Existing config, passwords and tables were preserved; application database privileges are ensured.\n";
echo "No application files, reference SQL or quests were imported.\n";

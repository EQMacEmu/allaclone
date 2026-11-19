<?php
// Install this adapter as www/includes/config.php when deploying Allaclone.
// A malformed private config must not print its contents to the browser.
ob_start();
try {
    require '/app/config/application.php';
} catch (Throwable $error) {
    ob_end_clean();
    error_log('Allaclone could not load config/application.php.');
    http_response_code(503);
    exit('Configuration unavailable.');
}
if (ob_get_clean() !== '') {
    error_log('Allaclone config/application.php produced unexpected output.');
    http_response_code(503);
    exit('Configuration unavailable.');
}

// Validate form and self-link paths before the query helpers are loaded.
$__script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
if ($__script !== '' && $__script[0] === '/' && strpos($__script, '..') === false
    && preg_match('#^/[A-Za-z0-9._/-]+$#', $__script)) {
    $PHP_SELF = $__script;
} else {
    $__base = basename($__script);
    $PHP_SELF = ($__base !== '' && preg_match('#^[A-Za-z0-9._-]+$#', $__base))
        ? $__base : 'index.php';
}
unset($__script, $__base);

// Allaclone expects config.php to open $db; mysql.php contains helpers.
// Use the application's query error handling and latin1 charset.
mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);
if (!$db) {
    error_log('Allaclone database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('Database unavailable.');
}
if (!mysqli_set_charset($db, 'latin1')) {
    error_log('Allaclone database charset setup failed: ' . mysqli_error($db));
    http_response_code(503);
    exit('Database unavailable.');
}

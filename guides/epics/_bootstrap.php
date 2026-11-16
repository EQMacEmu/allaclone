<?php
/**
 * Shared bootstrap for Epic 1.0 guide pages.
 */
$includes_dir_local = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
include($includes_dir_local . 'constantes.php');
include($includes_dir_local . 'config.php');
include($includes_dir_local . 'mysql.php');
include($includes_dir_local . 'functions.php');
include($includes_dir_local . 'quest_helpers.php');
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/_catalog.php';

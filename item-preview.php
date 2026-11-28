<?php

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');

$item = GetItemRow($_GET['id'] ?? '', $_GET['name'] ?? '');
$stats=BuildItemStats($item, 0);


echo "<div class='item-stats'>";
echo $stats;
echo "</div>";
?>

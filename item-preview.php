<?php

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');

$id   = (isset($_GET['id']) ? addslashes($_GET['id']) : '');
$name = (isset($_GET['name']) ? addslashes($_GET['name']) : '');

ini_set('display_errors', 1);
$item=GetItemRow($id);
$stats=BuildItemStats($item, 0);


echo "<div class='item-stats'>";
echo $stats;
echo "</div>";
?>

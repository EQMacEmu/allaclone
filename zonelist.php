<?php
$Title = "Zone List";
include('./includes/config.php');
include($includes_dir . 'constantes.php');
include($includes_dir . 'headers.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');
$expansionStrings = array(
	"antonica"=>"Antonica",
	"odus"=>"Odus",
	"faydwer"=>"Faydwer",
	"planes"=>"Old World Planes",
);
if ($expansion > 0) {
	$expansionStrings["kunark"] = "Ruins of Kunark";
}
if ($expansion > 1) {
	$expansionStrings["velious"] = "Scars of Velious";
}
if ($expansion > 2) {
	$expansionStrings["luclin"] = "Shadows of Luclin";
}
if ($expansion > 3) {
	$expansionStrings["power"] = "Planes of Power";
}
?>

<div class="container zonelist">
	<ul class="expansion-list">
<?
foreach ($expansionStrings as $short => $long) {
	print "<li><a href=\"static/$short.php\">$long</a></li>";
}
?>
	</ul>
</div>
</div>

<?php include($includes_dir . "footers.php"); ?>

<?php
include('../includes/constantes.php');
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mysql.php');

$npc = isset($_REQUEST['npc']) ? $_REQUEST['npc'] : null;
$zone = isset($_REQUEST['zone']) ? $_REQUEST['zone'] : null;
$npcid = isset($_REQUEST['npcid']) ? $_REQUEST['npcid'] : 0;
$iclass = isset($_REQUEST['iclass']) ? $_REQUEST['iclass'] : 'c';
$ilevel = isset($_REQUEST['ilevel']) ? $_REQUEST['ilevel'] : 'l';
$ifaction = isset($_REQUEST['ifaction']) ? $_REQUEST['ifaction'] : '5';
$irace = isset($_REQUEST['irace']) ? $_REQUEST['irace'] : '\&lt\;your\&nbsp\;race\&gt\;';

if ($npc === null || $npc === '' || $zone === null || $zone === '') {
	print "<script>document.location=\"../index.php\";</script>";
	exit;
}

// Restrict path components used for generated .inc lookup.
if (!preg_match('/^[A-Za-z0-9_#.-]+$/', $zone) || !preg_match('/^[A-Za-z0-9_#.-]+$/', $npc)) {
	print "<script>document.location=\"../index.php\";</script>";
	exit;
}

$Title="Quest(s) for :: ".str_replace('_',' ',$npc);
if ($zone!="") { $myzone=GetFieldByQuery("long_name","SELECT long_name FROM zone WHERE short_name like '".addslashes($zone)."'"); }
$Title.="<br><a href=zones.php?aZone=".rawurlencode($zone).">$myzone</a>";
include('../includes/headers.php');

$file=$quests_datas.urlencode($zone)."/".urlencode($npc).".inc";

// Legacy live Perl parse is disabled on HTTP (command-injection risk). Serve prebuilt .inc only.
if (!file_exists($file)) {
	print "<p>Quest data not found for this NPC. Pre-generate quest .inc files offline, or use the Lua DB importer when available.</p>";
	include("../includes/footers.php");
	exit;
}

require($file);

include("../includes/footers.php");
?>

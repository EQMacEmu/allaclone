<?php
$Title = "Advanced NPC Search";
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'headers.php');
include($includes_dir . 'functions.php');

$isearch = (isset($_GET['isearch']) ? $_GET['isearch'] : '');
$id   = isset($_GET['id']) ? sql_int($_GET['id'], 0) : 0;
$iname = (isset($_GET['iname']) ? $_GET['iname'] : '');
$iminlevel = isset($_GET['iminlevel']) ? sql_int($_GET['iminlevel'], 0) : 0;
$imaxlevel = isset($_GET['imaxlevel']) ? sql_int($_GET['imaxlevel'], 0) : 0;
$inamed = (isset($_GET['inamed']) ? $_GET['inamed'] : '');
$ishowlevel = (isset($_GET['ishowlevel']) ? $_GET['ishowlevel'] : '');
$irace = isset($_GET['irace']) ? sql_int($_GET['irace'], 0) : 0;
if ($irace == 0) {
	$irace = '';
}


print "<div class='container advnpc'>";
print "<form method='GET' action='" . html_attr($PHP_SELF) . "'>";
print "<div class='form-control'>";
print "<strong>Name: </strong><input type='text' value=\"" . html_attr($iname) . "\" size='30' name='iname'>";
print "</div>";
print "<div class='form-control'>";
print "<strong>Level Range: </strong>";
print SelectLevel("iminlevel", $ServerMaxNPCLevel, $iminlevel);
print " &mdash; ";
print SelectLevel("imaxlevel", $ServerMaxNPCLevel, $imaxlevel);
print "</div>";
print "<div class='form-control'>";
print "<strong>Race: </strong>";
print SelectMobRace("irace", $irace);
print "</div>";
// print "<strong>Named mob : </strong><input type=checkbox name=inamed " . ($inamed ? " checked" : "") . ">";
// print "<strong>Show level : </strong><input type=checkbox name=ishowlevel " . ($ishowlevel ? " checked" : "") . ">";
print "<div class='form-control'>";
print "<input type=submit value=Search name=isearch class=form>";
print "</div>";
print "</form>";

if (isset($isearch) && $isearch != '') {
	$query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbnpctypes.level
				FROM $tbnpctypes
				WHERE 1=1";
	if ($iminlevel > $imaxlevel) {
		$c = $iminlevel;
		$iminlevel = $imaxlevel;
		$imaxlevel = $c;
	}
	if ($iminlevel > 0) {
		$query .= " AND $tbnpctypes.level>=$iminlevel";
	}
	if ($imaxlevel > 0) {
		$query .= " AND $tbnpctypes.level<=$imaxlevel";
	}
	if ($inamed) {
		$query .= " AND substring($tbnpctypes.name,1,1)='#'";
	}
	if ($irace > 0) {
		$query .= " AND $tbnpctypes.race=$irace";
	}
	if ($iname != "") {
		$search_name = sql_like($db, $iname, array(' ' => '%', '`' => '%'));
		$query .= " AND $tbnpctypes.name LIKE '%$search_name%'";
	}
	if ($HideInvisibleMen) {
		$query .= " AND $tbnpctypes.race!=127";
	}
	$query .= " ORDER BY $tbnpctypes.name";
	$result = mysqli_query($db, $query);
	$n = mysqli_num_rows($result);
	if ($n > $MaxNpcsReturned) {
		print "$n ncps found, showing the $MaxNpcsReturned first ones...";
		$query .= " LIMIT $MaxNpcsReturned";
		$result = mysqli_query($db, $query);
	}
	if (mysqli_num_rows($result) > 0) {
		print "<ul>";
		while ($row = mysqli_fetch_array($result)) {
			print "<li><a href=npc.php?id=" . $row["id"] . ">" . ReadableNpcName($row["name"]) . "</a>";
			if ($ishowlevel) {
				print " - level " . $row["level"];
			}
			print "</li>";
		}
		print "</ul>";
	} else {
		print "<p>No npc found.</p>";
	}
}

print "</div></div>";

include($includes_dir . "footers.php");

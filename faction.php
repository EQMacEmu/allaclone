<?php

/** Displays the faction identified by 'id' if it is specified and a faction by this ID exists.
 *  Otherwise queries for the factions identified by 'name'. Underscores are considered as spaces, for Wiki compatibility.
 *    If exactly one faction is found, displays this faction.
 *    Otherwise redirects to the faction search page, displaying the results for '%name%'.
 *  If neither 'id' nor 'name' are specified or if 'id' is not a valid faction ID, redirects to the faction search page.
 */

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'functions.php');
include($includes_dir . 'mysql.php');

/** Formats the npc/zone info selected in '$QueryResult' to display them by zone
 *  The top-level sort must be on the zone.
 */
function PrintNpcsByZone($QueryResult) {
	if (mysqli_num_rows($QueryResult) > 0) {
		$CurrentZone = "";
		echo '<ul>';
		while ($row = mysqli_fetch_array($QueryResult)) {
			if ($CurrentZone != $row["zone"]) {
				if ($CurrentZone != "")
					print "<br/><br/>\n";
				print "<a class='zone' href='zone.php?name=" . $row["zone"] . "'>" . $row["long_name"] . "</a>";
				$CurrentZone = $row["zone"];
			}
			$id=$row["id"];
			$name=str_replace(array("_","#"), " ", $row["name"]);
			$value = "";
			if (array_key_exists("value", $row)) {
				$value=" (" . $row["value"] . ")";
			}
			print "<li><a href='npc.php?id=$id'>$name$value</a></li>";
		}
		echo '</ul>';
	}
}


$id   = (isset($_GET['id']) ? $_GET['id'] : '');
$name = (isset($_GET['name']) ? addslashes($_GET['name']) : '');

if ($id != "" && is_numeric($id)) {
	$Query = "SELECT id,name FROM $tbfactionlist WHERE id='" . $id . "'";
	$QueryResult = mysqli_query($db, $Query) or message_die('faction.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: factions.php");
		exit();
	}
	$FactionRow = mysqli_fetch_array($QueryResult);
	$name = $FactionRow["name"];
} elseif ($name != "") {
	$Query = "SELECT id,name FROM $tbfactionlist WHERE name like '$name'";
	$QueryResult = mysqli_query($db, $Query) or message_die('faction.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: factions.php?iname=" . $name . "&isearch=true");
		exit();
	} else {
		$FactionRow = mysqli_fetch_array($QueryResult);
		$id = $FactionRow["id"];
		$name = $FactionRow["name"];
	}
} else {
	header("Location: factions.php");
	exit();
}

/** Here the following stands :
 *    $id : ID of the faction to display
 *    $name : name of the faction to display
 *    $FactionRow : row of the faction to display extracted from the database
 *    The faction actually exists
 */
$Title = "Faction: " . $name . " - id: " . $id;
include($includes_dir . 'headers.php');

//echo "<a href='".$peqeditor_url."index.php?editor=faction&amp;fid=".$id."'><img src='".$images_url."/peq_faction.png' align='right'/></a>";

echo '<div class="container faction-details">';
echo '<div class="faction-list">';
echo '<div class="consider">';
echo "<strong>NPCs that consider on this faction</strong>";
$Query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbzones.long_name,$tbzones.short_name as zone
FROM $tbfactionlist
JOIN $tbnpcfaction ON $tbnpcfaction.primaryfaction = $tbfactionlist.id
JOIN $tbnpctypes ON $tbnpctypes.npc_faction_id = $tbnpcfaction.id
JOIN $tbzones ON $tbzones.zoneidnumber = $tbnpctypes.id DIV 1000
WHERE $tbfactionlist.id = $id
ORDER BY $tbzones.long_name ASC, npc_types.name ASC
";
$QueryResult = mysqli_query($db, $Query) or message_die('faction.php', 'MYSQL_QUERY', $query, mysqli_error($db));
PrintNpcsByZone($QueryResult);
echo '</div>'; // consider
echo '<div class="raise">';
echo "<strong>NPCs whom death raises the faction</strong>";
$Query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbzones.long_name,$tbzones.short_name as zone,$tbnpcfactionentries.value
			FROM $tbnpcfactionentries,$tbnpctypes,$tbzones
			WHERE $tbnpcfactionentries.faction_id=$id
			AND $tbnpcfactionentries.npc_faction_id=$tbnpctypes.npc_faction_id
			AND $tbnpcfactionentries.value>0
			AND $tbzones.zoneidnumber = $tbnpctypes.id DIV 1000
			ORDER BY $tbzones.zoneidnumber ASC, $tbnpcfactionentries.value DESC
			";
$QueryResult = mysqli_query($db, $Query) or message_die('faction.php', 'MYSQL_QUERY', $query, mysqli_error($db));
PrintNpcsByZone($QueryResult);
echo '</div>'; // raise

echo '<div class="lower">';
echo "<strong>NPCs whom death lowers the faction</strong>";
$Query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbzones.long_name,$tbzones.short_name as zone,$tbnpcfactionentries.value
			FROM $tbnpcfactionentries,$tbnpctypes,$tbzones
			WHERE $tbnpcfactionentries.faction_id=$id
			AND $tbnpcfactionentries.npc_faction_id=$tbnpctypes.npc_faction_id
			AND $tbnpcfactionentries.value<0
			AND $tbzones.zoneidnumber=$tbnpctypes.id DIV 1000
			ORDER BY $tbzones.long_name ASC, $tbnpcfactionentries.value ASC
			";
$QueryResult = mysqli_query($db, $Query) or message_die('faction.php', 'MYSQL_QUERY', $query, mysqli_error($db));
PrintNpcsByZone($QueryResult);
echo '</div>'; // lower
echo '</div>'; // faction-list
echo '</div>'; // faction-details
echo '</div>';

include($includes_dir . "footers.php");

<?php

/** Displays the NPC identified by 'id' if it is specified and an NPC by this ID exists.
 *  Otherwise queries for the NPCs identified by 'name'. Underscores are considered as spaces and backquotes as minuses,
 *    for Wiki-EQEmu compatibility.
 *    If exactly one NPC is found, displays this NPC.
 *    Otherwise redirects to the NPC search page, displaying the results for '%name%'.
 *  If neither 'id' nor 'name' are specified or if 'id' is not a valid NPC ID, redirects to the NPC search page.
 */

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'functions.php');
include($includes_dir . 'mysql.php');

$id   = (isset($_GET['id']) ? $_GET['id'] : '');
$name = (isset($_GET['name']) ? addslashes($_GET['name']) : '');
$content = (isset($_GET['content']) ? addslashes($_GET['content']) : '');

if ($id != "" && !in_array($id, $hide_npc_id) && is_numeric($id)) {
	$Query = "SELECT * FROM $tbnpctypes WHERE id='" . $id . "'";
	foreach ($hide_npc_id as $hideme) {
		$Query .= " AND $tbnpctypes.id != $hideme"; // Block by ID set in config
	}
	$QueryResult = mysqli_query($db, $Query) or message_die('npc.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: npcs.php");
		exit();
	}
	$npc = mysqli_fetch_array($QueryResult);
	$name = $npc["name"];
} elseif ($name != "") {
	$Query = "SELECT * FROM $tbnpctypes WHERE name like '$name'";
	$QueryResult = mysqli_query($db, $Query) or message_die('npc.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: npcs.php?iname=" . $name . "&isearch=true");
		exit();
	} else {
		$npc = mysqli_fetch_array($QueryResult);
		$id = $npc["id"];
		$name = $npc["name"];
	}
} else {
	header("Location: npcs.php");
	exit();
}

if ((ReadableNpcName($npc["name"])) == '') {
	header("Location: npcs.php");
	exit();
}

/** Here the following stands :
 *    $id : ID of the NPC to display
 *    $name : name of the NPC to display
 *    $NpcRow : row of the NPC to display extracted from the database
 *    The NPC actually exists
 */

include($includes_dir . 'headers.php');

print "<div class='npc-wrapper'>";
print "<div class='npc-info'>";

print "<div class='left-col'><h2>" . ReadableNpcName($npc["name"]) . "</h2>";

if ($npc["lastname"] != "") {
	print "<br/>" . str_replace("_", " ", " (" . $npc["lastname"] . ")") . " - id : " . $id;
} else {
	print "<p>Level: " . $npc["level"];
	if ($npc["maxlevel"] > $npc["level"]) {
		print "-".$npc["maxlevel"];
	}
	print "</p>";
}

print "<div class='secondary-info'>";

print "<p><strong>Race:</strong> " . $dbiracenames[$npc["race"]] . "</p>";
print "<p><strong>Class:</strong> " . $dbclasses[$npc["class"]] . "</p>";
print "<p><strong>Hit Points:</strong> " . $npc["hp"] . "</p>";
if ($npc["mana"] > 0) {
	print "<p><strong>Mana:</strong> " . $npc["mana"] . "</p>";
}
print "<p><strong>Dmg/Delay:</strong> " . $npc["mindmg"] . "-" . $npc["maxdmg"] . " / " . $npc["attack_delay"] . "</p>";
$pr = $npc["PR"];
$mr = $npc["MR"];
$dr = $npc["DR"];
$fr = $npc["FR"];
$cr = $npc["CR"];
print "\n<table id='resists-table'>";
print "\n<tr><td colspan='5'><strong>Resists:</strong></td></tr>";
print "\n<tr>";
print "\n<td><img width='20' height='20' title='Magic Resist' src='${icons_url}/161.gif' /></td>";
print "\n<td><img width='20' height='20' title='Fire Resist' src='${icons_url}/51.gif' /></td>";
print "\n<td><img width='20' height='20' title='Cold Resist' src='${icons_url}/56.gif' /></td>";
print "\n<td><img width='20' height='20' title='Disease Resist' src='${icons_url}/41.gif' /></td>";
print "\n<td><img width='20' height='20' title='Poison Resist' src='${icons_url}/42.gif' /></td>";
print "\n</tr>";
print "\n<tr>";
print "\n<td>$mr</td>";
print "\n<td>$fr</td>";
print "\n<td>$cr</td>";
print "\n<td>$dr</td>";
print "\n<td>$pr</td>";
print "\n</tr>";
print "\n</table>";
print "\n<p><strong>Special:</strong> " . SpecialAttacks($npc["special_abilities"]) . "</p>";
print "\n<p><strong>Agro Radius</strong>: " . $npc["aggroradius"] . "</p>";
print "\n<p><strong>Assist Radius</strong>: " . $npc["assistradius"] . "</p>";
print "\n</div>";

if ($npc["npc_faction_id"] > 0) {
	$query = "SELECT $tbfactionlist.name,$tbfactionlist.id
				FROM $tbfactionlist,$tbnpcfaction 
				WHERE $tbnpcfaction.id=" . $npc["npc_faction_id"] . " 
				AND $tbnpcfaction.primaryfaction=$tbfactionlist.id";
	$faction = GetRowByQuery($query);
}


$loottable_id = 0;
if (($npc["loottable_id"] > 0) and ((!in_array($npc["class"], $dbmerchants)) or ($MerchantsDontDropStuff == FALSE))) {
	$filter = gatefilter(array($tbloottable), $expansion);
	$query = "SELECT
		id
		FROM
		$tbloottable
		WHERE
		id=" . $npc["loottable_id"] . "
		$filter
	";
	$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$loottable_id = $row["id"];
		}
	}
}

function printLootDrop($loottable)
{
	global $expansion;
	global $tblootdropentries;
	global $tbitems;
	global $db;
	global $icons_url;
	$name = $loottable["name"];
	$lootdrop_id = $loottable["lootdrop_id"];
	$drp_sum = $loottable["drp_sum"];
	$tbl_chance = $loottable["tbl_chance"];
	$tbl_min = $loottable["tbl_min"];
	$tbl_max = $loottable["tbl_max"];
	$drp_min = $loottable["drp_min"];
	$drp_max = $loottable["drp_max"];

	$tbl_diff = $tbl_max - $tbl_min;
	if ($drp_max < $drp_min) {
		$drp_max = $drp_min;
	}
	$drp_diff = $drp_max - $drp_min;

	$table_chance = $tbl_chance*$tbl_diff;
	if ($tbl_min > 0) {
		$table_failure = 0;
	} else {
		$table_failure = ((1-($drp_chance))**$drp_mult);
	}
	$table_chance = round($tbl_chance * 100, 2);
	$sum = round($drp_sum * 100, 2);
	//print "<li>[$lootdrop_id] $name</li>";
	print "<li>Table ($tbl_min:$tbl_max:$table_chance%) Drop ($drp_min:$drp_max:$sum%)</li>";
	print "<li><u>";
	if ($drp_min == 0 && $drp_max != 0) {
		$table_chance = round($tbl_chance * min($drp_sum, 1) * 100, 2);
	}
	if ($table_chance == 100) {
		if ($tbl_max == 1) {
			print "Runs once.";
		} else {
			print "Runs $tbl_max times.";
		}
	} else if ($tbl_diff == 0) {
		print "Runs $tbl_min times.";
	} else {
		if ($tbl_min > 1) {
			print "Runs $tbl_min times, plus ${tbl_diff}x${table_chance}% times.";
		} else {
			print "Has a ${table_chance}% to run.";
		}
	}
	if ($drp_min == 0 && $drp_max == 0) {
		print " Each entry rolls independently.";
	} else if ($drp_diff == 0) {
		if ($drp_min == 1) {
			print " Drops $drp_min entry each run.";
		} else {
			print " Drops $drp_min entries each run.";
		}
	} else {
		if ($sum > 1) {
			print " Drops $drp_max entries each run.";
		} else if ($drp_min > 1) {
			print " Drops at least $drp_min and ${sum}% up to $drp_max each run.";
		}
	}

	print "</u></li>";

	$filter = gatefilter(array($tblootdropentries), $expansion);
	$query = "SELECT
		$tbitems.id as id,
		$tbitems.name as name,
		$tbitems.icon as icon,
		$tblootdropentries.multiplier as drp_mult,
		$tblootdropentries.chance/100 as drp_chance
		FROM $tblootdropentries, $tbitems
		WHERE $tbitems.id = $tblootdropentries.item_id
		AND $tblootdropentries.lootdrop_id = $lootdrop_id
		$filter
	;";
	$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$id = $row["id"];
			$name = $row["name"];
			$icon = $row["icon"];
			$drp_mult = $row["drp_mult"];
			$drp_chance = $row["drp_chance"];
			if ($drp_mult < 1) {
				$drp_mult = 1;
			}
			print "<li>";
			print "<img width='20' heigth='20' src='${icons_url}item_$icon.gif' />";
			print "<a href='item.php?id=$id' data-item-id=\"$id\" class=\"item-link\">$name</a>";
			if (($drp_min == 0 && $drp_max == 0) || $drp_mult > 1) {
				$chance = round($drp_chance * 100, 2);
			} else {
				$chance = round($drp_chance / ($sum / 100) * 100, 2);
			}
			if ($drp_mult == 1) {
				print "($chance%)";
			} else {
				$min = $drp_min;
				$max = $drp_mult - $min;
				if ($min > 0) {
					print "(${min}x100% + ${max}x$chance%)";
				} else {
					print "(${max}x$chance%)";
				}
			}
			print "</li>";
		}
		print "<br/>";
	}
}

if ($loottable_id > 0) {
	print "<div class='list-wrapper'>";
	print "<p><strong>When killed, this NPC can drop: </strong></p>";
	print "<ul>";

	$filter = gatefilter(array($tblootdrop, $tblootdropentries), $expansion);
	$query = "SELECT
		$tbloottableentries.lootdrop_id,
		$tbloottableentries.probability/100 as tbl_chance,
		$tbloottableentries.multiplier_min as tbl_min,
		$tbloottableentries.multiplier as tbl_max,
		$tbloottableentries.mindrop as drp_min,
		$tbloottableentries.droplimit as drp_max,
		$tblootdrop.name,
		SUM($tblootdropentries.chance)/100 as drp_sum
		FROM $tbloottableentries, $tblootdrop, $tblootdropentries
		WHERE $tblootdrop.id = $tbloottableentries.lootdrop_id
		AND $tbloottableentries.lootdrop_id = $tblootdropentries.lootdrop_id
		AND $tbloottableentries.loottable_id = $loottable_id
		$filter
		GROUP BY $tblootdropentries.lootdrop_id
	;";
	$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			printLootDrop($row);
		}
	}

	print "</ul>";
	print "</div>";
} else {
	print "<p><strong>No item drops found.</strong></p>";
}

if ($npc["merchant_id"] > 0) {
	$query = "SELECT $tbitems.id,$tbitems.Name,$tbitems.price
				FROM $tbitems,$tbmerchantlist
				WHERE $tbmerchantlist.merchantid=" . $npc["merchant_id"] . "
				AND $tbmerchantlist.item=$tbitems.id
				ORDER BY $tbmerchantlist.slot";
	$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		print "<p><strong>This NPC sells:</strong></p><ul>";
		while ($row = mysqli_fetch_array($result)) {
			print "<li><a href='item.php?id=" . $row["id"] . "'>" . $row["Name"] . "</a> ";
			if ($npc["class"] == 41) {
				print "(" . price($row["price"]) . ")";
			} // NPC is a shopkeeper
			if ($npc["class"] == 61) {
				print "(" . $row["ldonprice"] . " points)";
			} // NPC is a LDON merchant
			print "</li>";
		}
		print "</ul>";
	}
}

print "</div>";

print "<div class='right-col'>";
if ($UseWikiImages) {
	$ImageFile = NpcImage($wiki_server_url, $wiki_root_name, $id);
	if ($ImageFile == "") {
		print "<a href='" . $wiki_server_url . $wiki_root_name . "/index.php?title=Special:Upload&wpDestFile=Npc-" . $id . ".jpg'>Click to add an image for this NPC</a>";
	} else {
		print "<img src='" . $ImageFile . "'/>";
	}
} else {
	if (file_exists($npcs_dir . $id . ".jpg")) {
		print "<img src=" . $npcs_url . $id . ".jpg>";
	}
}

function print_spell($spell, $extra = "")
{
	global $icons_url;
	print "<li>";
	print "<img height='20' width='20' id='spell-icon' src='{$icons_url}{$spell['new_icon']}.gif' alt='{$spell["name"]}' />";
	print "<a href='spell.php?id=" . $spell["id"] . "'>" . $spell["name"] . "</a>";
	if ($extra != "") {
		print "($extra)";
	}
	print "</li>";
}

if ($npc["npc_spells_id"] > 0) {
	$query = "SELECT * FROM $tbnpcspells WHERE id=" . $npc["npc_spells_id"];
	$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		$g = mysqli_fetch_array($result);
		print "<div class='list-wrapper'>";
		if ($g["attack_proc"] > 0) {
			print "<p><strong>This NPC procs:</strong></p>";
			$spellid = $g["attack_proc"];
			$chance = $g["proc_chance"];
			$spell = getspell($spellid);
			print "<ul>";
			print_spell($spell, "$chance%");
			print "</ul>";
		}
		print "<p><strong>This NPC casts the following spells:</strong></p>";
		$in = strval($npc["npc_spells_id"]);
		if ($g["parent_list"] > 0) {
			$in .= ",".$g["parent_list"];
		}
		$query = "SELECT $tbnpcspellsentries.*
					FROM $tbnpcspellsentries
					WHERE $tbnpcspellsentries.npc_spells_id IN ($in)
					AND $tbnpcspellsentries.minlevel<=" . $npc["level"] . "
					AND $tbnpcspellsentries.maxlevel>=" . $npc["level"] . "
					AND ($tbnpcspellsentries.min_expansion = -1 OR $tbnpcspellsentries.min_expansion <= $expansion)
					AND ($tbnpcspellsentries.max_expansion = -1 OR $tbnpcspellsentries.max_expansion >= $expansion)
					ORDER BY $tbnpcspellsentries.priority DESC";
		$result2 = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
		if (mysqli_num_rows($result2) > 0) {
			//print "<p><strong>Listname : </strong>" . ReadableNpcName($g["name"] . "</p>");
			if ($DebugNpc) {
				print " (" . $npc["npc_spells_id"] . ")";
			}
			print "<ul>";
			while ($row = mysqli_fetch_array($result2)) {
				$spell = getspell($row["spellid"]);
				$mana = $row["manacost"] > 0 ? $row["manacost"] : $spell["mana"];
				$recast = $row["recast_delay"] > 0 ? $row["recast_delay"] : round($spell["recast_time"] / 1000, 2);
				print_spell($spell, "Mana: $mana, Recast: ${recast}s");
				if ($DebugNpc) {
					print " (recast=" . $row["recast_delay"] . ", priority= " . $row["priority"] . ")";
				}
				print "</li>";
			}
		}
		print "</ul></div>";
	}
}

// zone list
$query = "SELECT $tbzones.long_name,
				$tbzones.short_name,
				$tbspawn2.x,$tbspawn2.y,$tbspawn2.z,
				$tbspawngroup.name as spawngroup,
				$tbspawngroup.id as spawngroupID,
				$tbspawn2.respawntime
				FROM $tbzones,$tbspawnentry,$tbspawn2,$tbspawngroup
				WHERE $tbspawnentry.npcID=$id
				AND ($tbspawnentry.min_expansion = -1 OR $tbspawnentry.min_expansion <= $expansion)
				AND ($tbspawnentry.max_expansion = -1 OR $tbspawnentry.max_expansion >= $expansion)
				AND ($tbspawn2.min_expansion = -1 OR $tbspawn2.min_expansion <= $expansion)
				AND ($tbspawn2.max_expansion = -1 OR $tbspawn2.max_expansion >= $expansion)
				AND $tbspawnentry.spawngroupID=$tbspawn2.spawngroupID
				AND $tbspawn2.zone=$tbzones.short_name
				AND $tbspawnentry.spawngroupID=$tbspawngroup.id";
foreach ($IgnoreZones as $zid) {
	$query .= " AND $tbzones.short_name!='$zid'";
}
$query .= " ORDER BY $tbzones.long_name,$tbspawngroup.name";
$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
if (mysqli_num_rows($result) > 0) {
	print "<div class='list-wrapper'><p><strong>This NPC spawns in:</strong></p><ul>";
	$z = "";
	$lastSpawnGroup="";
	while ($row = mysqli_fetch_array($result)) {
		if ($z != $row["short_name"]) {
			print "<li><a href='zone.php?name=" . $row["short_name"] . "'>" . $row["long_name"] . "</a></li>";
			$z = $row["short_name"];
			if ($AllowQuestsNPC == TRUE) {
				if (file_exists("$quests_dir$z/" . str_replace("#", "", $npc["name"]) . ".pl")) {
					print "<br/><a href='" . $root_url . "quests/index.php?npc=" . str_replace("#", "", $npc["name"]) . "&zone=" . $z . "&amp;npcid=" . $id . "'>Quest(s) for that NPC</a>";
				}
			}
			print "</li>";
		}
		if ($DisplaySpawnGroupInfo == TRUE) {
			if ($row["spawngroup"] != $lastSpawnGroup) {
				print "<li>";
				print "<a href='spawngroup.php?id=" . $row["spawngroupID"] . "'>" . $row["spawngroup"] . "</a> : ";
				print "Spawns every " . translate_time($row["respawntime"]);
				print "</li>";
				$lastSpawnGroup = $row["spawngroup"];
			}
			print "<li>";
			print floor($row["y"]) . " , " . floor($row["x"]) . " , " . floor($row["z"]);
			print "</li>";
		}
	}
	print "</ul></div>";
}
// factions
$query = "SELECT $tbfactionlist.name,
			$tbfactionlist.id,
			$tbnpcfactionentries.value
			FROM $tbfactionlist,$tbnpcfactionentries
			WHERE $tbnpcfactionentries.npc_faction_id=" . $npc["npc_faction_id"] . "
			AND $tbnpcfactionentries.faction_id=$tbfactionlist.id
			AND $tbnpcfactionentries.value<0
			GROUP BY $tbfactionlist.id";
$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
if (mysqli_num_rows($result) > 0) {
	print "<div class='list-wrapper'><p><strong>Killing this NPC lowers factions with : </strong><ul>";
	while ($row = mysqli_fetch_array($result)) {
		if ($ShowNPCFactionHits == TRUE) {
			print "<li class='bad'><a href=faction.php?id=" . $row["id"] . ">" . $row["name"] . "</a> (" . $row["value"] . ")</li>";
		} else {
			print "<li class='bad'><a href=faction.php?id=" . $row["id"] . ">" . $row["name"] . "</a></li>";
		}
	}
	print "</ul>";
	print "</div>";
}
$query = "SELECT $tbfactionlist.name,
			$tbfactionlist.id,
			$tbnpcfactionentries.value
			FROM $tbfactionlist,$tbnpcfactionentries
			WHERE $tbnpcfactionentries.npc_faction_id=" . $npc["npc_faction_id"] . "
			AND $tbnpcfactionentries.faction_id=$tbfactionlist.id
			AND $tbnpcfactionentries.value>0
			GROUP BY $tbfactionlist.id";
$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
if (mysqli_num_rows($result) > 0) {
	print "<div class='list-wrapper'><p><strong>Killing this NPC raises factions with : </strong><ul>";
	while ($row = mysqli_fetch_array($result)) {
		if ($ShowNPCFactionHits == TRUE) {
			print "<li class='good'><a href=faction.php?id=" . $row["id"] . ">" . $row["name"] . "</a> (" . $row["value"] . ")</li>";
		} else {
			print "<li class='good'><a href=faction.php?id=" . $row["id"] . ">" . $row["name"] . "</a></li>";
		}
	}
	print "</ul>";
	print "</div>";
}
print "</div>";
print "</div>";
print "</div>";
print "</div>";

include($includes_dir . "footers.php");

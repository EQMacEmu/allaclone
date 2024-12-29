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

$Title="";
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

print "<p><strong>Race/Body:</strong> " . $dbiracenames[$npc["race"]] . " / " . $dbbodytypes[$npc["bodytype"]] . "</p>";
$greed="";
if ($npc["class"] == 41) {
	$greed = " (" . $npc["greed"] . ")";
}
print "<p><strong>Class:</strong> " . $dbclasses[$npc["class"]] . " $greed</p>";
print "<p><strong>HP/Mana:</strong> " . $npc["hp"] . " / " . $npc["mana"] . "</p>";
print "<p><strong>Dmg/Delay:</strong> " . $npc["mindmg"] . "-" . $npc["maxdmg"] . " / " . $npc["attack_delay"] . "</p>";
print "<p><strong>AC/ATK:</strong> " . $npc["AC"] . " / " . $npc["ATK"] . "</p>";
$pr = $npc["PR"];
$mr = $npc["MR"];
$dr = $npc["DR"];
$fr = $npc["FR"];
$cr = $npc["CR"];
print "<table id='resists-table'>";
print "<tr><td colspan='5'><strong>Resists:</strong></td></tr>";
print "<tr>";
print "<td><img width='20' height='20' title='Magic Resist' src='{$icons_url}/161.gif' /></td>";
print "<td><img width='20' height='20' title='Fire Resist' src='{$icons_url}/51.gif' /></td>";
print "<td><img width='20' height='20' title='Cold Resist' src='{$icons_url}/56.gif' /></td>";
print "<td><img width='20' height='20' title='Disease Resist' src='{$icons_url}/41.gif' /></td>";
print "<td><img width='20' height='20' title='Poison Resist' src='{$icons_url}/42.gif' /></td>";
print "</tr>";
print "<tr>";
print "<td>$mr</td>";
print "<td>$fr</td>";
print "<td>$cr</td>";
print "<td>$dr</td>";
print "<td>$pr</td>";
print "</tr>";
print "</table>";
print "<p><strong>Special:</strong> " . SpecialAttacks($npc["special_abilities"]) . "</p>";
print "<p><strong>Agro Radius</strong>: " . $npc["aggroradius"] . "</p>";
print "<p><strong>Assist Radius</strong>: " . $npc["assistradius"] . "</p>";
print "<button type='button' class='collapsible' onclick=toggleList('rawdump')>Raw NPC Data</button>";
print "<ul class='collapsible-table' style='display: none;' id='rawdump'>";
foreach ($npc as $key => $value) {
	if(!is_int($key)) {
		print "<li>$key : $value</li>";
	}
}
print "</ul>";
print "</div>"; // secondary-info


$loottable_id = 0;
if (($npc["loottable_id"] > 0) and ((!in_array($npc["class"], $dbmerchants)) or ($MerchantsDontDropStuff == FALSE))) {
	$filter = gatefilter(array($tbloottable));
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
		if ($tbl_min >= 1) {
			$s = ($tbl_min > 1) ? "s" : "";
			print "Runs $tbl_min time{$s}, plus {$tbl_diff}x{$table_chance}% times.";
		} else {
			print "Has a {$table_chance}% to run.";
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
			print " Drops at least $drp_min and {$sum}% up to $drp_max each run.";
		}
	}

	print "</u></li>";

	$filter = gatefilter(array($tblootdropentries));
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
			$item_id = $row["id"];
			$name = $row["name"];
			$icon = $row["icon"];
			$drp_mult = $row["drp_mult"];
			$drp_chance = $row["drp_chance"];
			if ($drp_mult < 1) {
				$drp_mult = 1;
			}
			print "<li>";
			print "<img width='20' heigth='20' src='{$icons_url}item_$icon.gif' />";
			print "<a href='item.php?id=$item_id' data-item-id='$item_id'>$name</a>";
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
					print "({$min}x100% + {$max}x$chance%)";
				} else {
					print "({$max}x$chance%)";
				}
			}
			print "</li>";
		}
		print "<br/>";
	}
}

print "<div class='list-wrapper'>";
if ($loottable_id > 0) {
	print "<p><strong>When killed, this NPC can drop: </strong></p>";
	print "<ul>";

	$filter = gatefilter(array($tblootdrop, $tblootdropentries));
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
} else {
	print "<p><strong>No item drops found.</strong></p>";
}
print "</div>"; // list-wrapper

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
			$item_id = $row["id"];
			print "<li><a href='item.php?id=$item_id' data-item-id='$item_id'>" . $row["Name"] . "</a> ";
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

print "</div>"; // left-col

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

// zone list
// NPCs with the same name have different IDs in different zones. So we'll only ever get 1 zone.
$zonefilter = gatefilter(array($tbzones));
$query = "SELECT
	$tbzones.long_name,
	$tbzones.short_name
	FROM $tbzones
	WHERE
	$tbzones.zoneidnumber = $id DIV 1000
	$zonefilter
	";
foreach ($IgnoreZones as $zid) {
	$query .= " AND $tbzones.short_name!='$zid'";
}
$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));

print "<div class='spawn-wrapper list-wrapper'>";
if (mysqli_num_rows($result) > 0) {
	$spawnentryfilter = gatefilter(array($tbspawnentry, $tbspawn2));
	$spawn2filter = gatefilter(array($tbspawn2));
	$row = mysqli_fetch_array($result);
	$short_name = $row["short_name"];
	$long_name = $row["long_name"];
	print "<p><strong>This NPC spawns in:</strong></p>";
	print "<ul>";
	print "<li class='spawn-zone'><a href='zone.php?name=$short_name'><u>$long_name</u></a></li>";
	print "</ul>";
	if ($DisplaySpawnGroupInfo == TRUE) {
		// Some spawngroups have multiple spawntimes in spawn2. List each set separately
		print "<ul class='spawn-group'>";
		$query = "SELECT
			$tbspawngroup.name as spawngroup,
			$tbspawngroup.id as spawngroupID,
			$tbspawnentry.chance,
			$tbspawn2.respawntime,
			$tbspawn2.variance,
			$tbspawn2.boot_respawntime,
			$tbspawn2.boot_variance
			FROM $tbspawngroup
			JOIN $tbspawnentry ON $tbspawngroup.id = $tbspawnentry.spawngroupID
			JOIN $tbspawn2 ON $tbspawnentry.spawngroupID = $tbspawn2.spawngroupID
			WHERE $tbspawnentry.npcID = $id
			$spawnentryfilter
			GROUP BY $tbspawngroup.id, $tbspawn2.respawntime
			ORDER BY $tbspawngroup.name
			";
		$result = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
		if (mysqli_num_rows($result) > 0) {
			while ($sgrow = mysqli_fetch_array($result)) {
				$spawngroupID = $sgrow["spawngroupID"];
				$chance = $sgrow["chance"];
				$respawntime = $sgrow["respawntime"];
				$variance = $sgrow["variance"];
				$bootspawntime = $sgrow["boot_respawntime"];
				$boot_variance = $sgrow["boot_variance"];
				print "<li>";
				print "<a href='spawngroup.php?id=$spawngroupID'>" . $sgrow["spawngroup"] . "</a> ($chance%)";
				print "</li>";
				print "<li>Boot spawn time: ";
				print translate_time($bootspawntime);
				if ($boot_variance) {
					if ($bootspawntime) {
						print " +/- " . translate_time($boot_variance / 2);
					} else {
						print " to " . translate_time($boot_variance);
					}
				}
				$listid = $spawngroupID + $respawntime;
				print "</li>";
				print "<li>Respawn time: ";
				print translate_time($respawntime);
				if ($variance) {
					print " +/- " . translate_time($variance / 2);
				}
				$listid = $spawngroupID + $respawntime;
				print "</li>";
				print "<li>";
				$query = "SELECT
					$tbspawn2.y,
					$tbspawn2.x,
					$tbspawn2.z
					FROM $tbspawn2
					WHERE $tbspawn2.spawngroupID = $spawngroupID
					AND $tbspawn2.respawntime = $respawntime
					$spawn2filter
				";
				$spawnresult = mysqli_query($db, $query) or message_die('npc.php', 'MYSQL_QUERY', $query, mysqli_error($db));
				$count = mysqli_num_rows($spawnresult);
				if ($count > 1) {
					print "<button type='button' class='collapsible' onclick=toggleList('$listid')>Hide/Show Spawn Locations ($count)</button>";
					$display = "none";
				} else {
					$display = "block";
				}
				print "<ul class='collapsible-table' style='display: $display;' id=$listid>";
				if ($count > 0) {
					while ($spawnrow = mysqli_fetch_array($spawnresult)) {
						print "<li>";
						print " (" . floor($spawnrow["y"]) . ", " . floor($spawnrow["x"]) . ", " . floor($spawnrow["z"]) . ")";
						print "</li>";
					}
				}
				print "</ul>";
				print "</li>";
			}
		} else {
			print "<p>This NPC has no spawn point.</p></ul>";
		}
		print "</ul>";
	}
} else {
	print "<p><strong>This NPC does not spawn during this expansion.</strong></p>";
}
print "</div>"; // spawn-wrapper

function print_spell($spell, $extra = "")
{
	global $icons_url;
	print "<li>";
	print "<img height='20' width='20' id='spell-icon' src='{$icons_url}{$spell['new_icon']}.gif' alt='{$spell["name"]}' />";
	$id = $spell["id"];
	print "<a href='spell.php?id=$id' data-spell-id='$id'>" . $spell["name"] . "</a>";
	if ($extra != "") {
		print " ($extra)";
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
				print_spell($spell, "Mana: $mana, Recast: {$recast}s");
				if ($DebugNpc) {
					print " (recast=" . $row["recast_delay"] . ", priority= " . $row["priority"] . ")";
				}
				print "</li>";
			}
		}
		print "</ul></div>"; // list-wrapper
	}
}

// factions
if ($npc["npc_faction_id"] > 0) {
	$query = "SELECT $tbfactionlist.name,$tbfactionlist.id
				FROM $tbfactionlist,$tbnpcfaction 
				WHERE $tbnpcfaction.id=" . $npc["npc_faction_id"] . " 
				AND $tbnpcfaction.primaryfaction=$tbfactionlist.id";
	$faction = GetRowByQuery($query);
	print "<div class='list-wrapper'>";
	print "<p><strong>Consider faction:</strong></p>";
	print "<p><a href=faction.php?id=" . $faction["id"] . ">" . $faction["name"] . "</a></p>";
	print "</div>";
}

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
	print "</div>"; // list-wrapper
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
	print "</div>"; // list-wrapper
}
print "</div>"; // site-wrapper
print "</div>"; // main
print "</div>"; // npc-info
print "</div>"; // npc-wrapper

include($includes_dir . "footers.php");

<?php

/** Displays the item identified by 'id' if it is specified and a item by this ID exists.
 *  Otherwise queries for the items identified by 'name'. Underscores are considered as spaces, for Wiki compatibility.
 *    If exactly one item is found, displays this item.
 *    Otherwise redirects to the item search page, displaying the results for '%name%'.
 *  If neither 'id' nor 'name' are specified or if 'id' is not a valid item ID, redirects to the item search page.
 */

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');

$id   = (isset($_GET['id']) ? addslashes($_GET['id']) : '');
$name = (isset($_GET['name']) ? addslashes($_GET['name']) : '');

if ($id != "" && is_numeric($id)) {
	if ($DiscoveredItemsOnly == true) {
		$Query = "SELECT * FROM $tbitems, discovered_items WHERE $tbitems.id='" . $id . "' AND discovered_items.item_id=$tbitems.id";
	} else {
		$Query = "SELECT * FROM $tbitems WHERE id='" . $id . "'";
	}
	foreach ($hide_item_id as $hideme) {
		$Query .= " AND $tbitems.id != $hideme"; // Block by ID set in config
	}
	$QueryResult = mysqli_query($db, $Query) or message_die('item.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: items.php");
		exit();
	}
	$ItemRow = mysqli_fetch_array($QueryResult);
	$name = $ItemRow["name"];
} elseif ($name != "") {
	if ($DiscoveredItemsOnly == true) {
		$Query = "SELECT * FROM $tbitems, discovered_items WHERE $tbitems.name like '$name' AND discovered_items.item_id=$tbitems.id";
	} else {
		$Query = "SELECT * FROM $tbitems WHERE name like '$name'";
	}
	$QueryResult = mysqli_query($db, $Query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($QueryResult) == 0) {
		header("Location: items.php?iname=" . $name . "&isearch=true");
		exit();
	} else {
		$ItemRow = mysqli_fetch_array($QueryResult);
		$id = $ItemRow["id"];
		$name = $ItemRow["name"];
	}
} else {
	header("Location: items.php");
	exit();
}

/** Here the following stands :
 *    $id : ID of the item to display
 *    $name : name of the item to display
 *    $ItemRow : row of the item to display extracted from the database
 *    The item actually exists
 */
$XhtmlCompliant = true;
include($includes_dir . 'headers.php');
$item = $ItemRow;
$Tableborder = 0;


echo "<div class='item-content'>";
echo "<div class='item-columns'>";
echo "<div class='item-wrapper'>";
echo "<div class='item-info'>";
echo "<strong>" . $item["Name"] . "</strong>";

if ($item["lore"] != "") {
	echo "<p class='hidden'>(" . $item["lore"] . ") - id : " . $id . "</p>";
} else {
	echo "id : " . $id;
}

echo BuildItemStats($item, 0);

if (file_exists(getcwd() . "/icons/item_" . $item['icon'] . ".gif")) {
	echo "<img src='" . $icons_url . "item_" . $item["icon"] . ".gif' />";
}

echo "</div></div>";
echo "<div class='drop-information'>";

// trade skills for which that item is a component
$query = "SELECT $tbtradeskillrecipe.name,$tbtradeskillrecipe.id,$tbtradeskillrecipe.tradeskill FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id AND $tbtradeskillrecipeentries.item_id=$id AND $tbtradeskillrecipeentries.componentcount>0 GROUP BY $tbtradeskillrecipe.id";
$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
$TradeskillResults = "";

if (mysqli_num_rows($result) > 0) {
	$TradeskillResults .= "<ul><li class='zone'>This item is used in the following tradeskill recipes:</li> ";
	while ($row = mysqli_fetch_array($result)) {
		$TradeskillResults .= "<li><a href='recipe.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a> (" . ucfirstwords($dbskills[$row["tradeskill"]]) . ")</li>";
	}
	$TradeskillResults .= "</ul>";
}
echo $TradeskillResults;

// trade skills which result is the component
$query = "SELECT $tbtradeskillrecipe.name,$tbtradeskillrecipe.id,$tbtradeskillrecipe.tradeskill FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id AND $tbtradeskillrecipeentries.item_id=$id AND $tbtradeskillrecipeentries.successcount>0 GROUP BY $tbtradeskillrecipe.id";
$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
$TradeskillResults = "";

if (mysqli_num_rows($result) > 0) {
	$TradeskillResults .= "<tr class='myline' height='6'><td colspan='2'></td><tr>";
	$TradeskillResults .= "<tr><td nowrap='1'><b>This item is the result of the following tradeskill recipes : </b><ul>";
	while ($row = mysqli_fetch_array($result)) {
		$TradeskillResults .= "<li><a href='recipe.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a> (" . $dbskills[$row["tradeskill"]] . ")</li>";
	}
	$TradeskillResults .= "</ul></td></tr>";
}
echo $TradeskillResults;

if ($AllowQuestsNPC == true) {
	// npcs that use that give that item as reward
	$query = "SELECT * FROM $tbquestitems WHERE item_id=$id AND rewarded>0";
	$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		echo "<tr class='myline' height='6'><td colspan='2'></td><tr>";
		echo "<tr><td nowrap='1'><b>This item is the result of a quest.</b></b><ul>";
		while ($res = mysqli_fetch_array($result)) {
			echo "<li><a href='" . $root_url . "quests/index.php?zone=" . $res["zone"] . "&amp;npc=" . $res["npc"] . "'>" . str_replace("_", " ", $res["npc"]) . "</a>";
			echo ", <a href=$root_url" . "zone.php?name=" . $res["zone"] . ">";
			echo GetFieldByQuery("long_name", "SELECT long_name FROM $tbzones WHERE short_name='" . $res["zone"] . "'") . "</a></li>";
		}
		echo "</ul></td></tr>";
	}

	// npcs that use that give that item as quest item
	$query = "SELECT * FROM $tbquestitems WHERE item_id=$id AND handed>0";
	$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		echo "<tr class='myline' height='6'><td colspan='2'></td><tr>";
		echo "<tr><td nowrap='1'><b>This item is used in quests.</b></b><ul>";
		while ($res = mysqli_fetch_array($result)) {
			echo "<li><a href='" . $root_url . "quests/index.php?zone=" . $res["zone"] . "&amp;npc=" . $res["npc"] . "'>" . str_replace("_", " ", $res["npc"]) . "</a>";
			echo ", <a href=$root_url" . "zone.php?name=" . $res["zone"] . ">";
			echo GetFieldByQuery("long_name", "SELECT long_name FROM $tbzones WHERE short_name='" . $res["zone"] . "'") . "</a></li>";
		}
		echo "</ul></td></tr>";
	}
}

$Separator = "";

if ($ItemFoundInfo) {
	// Check with a quick query before trying the long one
	$IsDropped = GetFieldByQuery("item_id", "SELECT item_id FROM $tblootdropentries WHERE item_id=$id LIMIT 1");

	if ($IsDropped) {

		$query = "
		SELECT nt.id
			, nt.`name`
			, z.short_name
			, z.long_name 
		FROM $tbnpctypes nt 
		JOIN $tbzones z ON z.zoneidnumber = LEFT(nt.id, LENGTH(nt.id) - 3)
		WHERE loottable_id IN
			(SELECT loottable_Id FROM loottable_entries WHERE lootdrop_id IN
				(SELECT lootdrop_id FROM lootdrop_entries WHERE item_id = $id)
			)
		";

		$query .= "
				ORDER BY nt.id
				ASC
		";

		$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));

		if (mysqli_num_rows($result) > 0) {
			$CurrentZone = "";
			$CurrentNPC = "";
			$displayName = "";
			$DroppedList = "<ul>";
			while ($row = mysqli_fetch_array($result)) {
				// var_dump($row);
				if ($CurrentZone != $row["short_name"]) {
					switch ($row["short_name"]) {
						case "poair":
							$displayName = "Eryslai, the Kingdom of Wind";
							break;
						case "poearth":
							$displayName = "Vergarlson, the Earthen Badlands";
							break;
						case "poearthb":
							$displayName = "Ragrax, Stronghold of the Twelve";
							break;
						case "pofire":
							$displayName = "Doomfire, the Burning Lands";
							break;
						case "powater":
							$displayName = "Reef of Coirnav";
							break;
						default:
							$displayName = $row['long_name'];
					}
					$DroppedList .= "
						<li class='zone'>
							<a href='zone.php?name=" . $row["short_name"] . "'>" . $displayName . "</a>
						</li>";
					$CurrentZone = $row["short_name"];
				}
				if ($CurrentNPC != $row["name"]) {
					$DroppedList .= "
						<li>
							<a href='npc.php?id=" . $row["id"] . "'>" . trim(str_replace("_", " ", $row["name"]), '#') . "</a>
						</li>";
					$CurrentNPC = $row["name"];
				}
			}
			$DroppedList .= "</ul>";
			echo $DroppedList;
		}
	}

	// Check with a quick query before trying the long one
	$IsSold = GetFieldByQuery("item", "SELECT item FROM $tbmerchantlist WHERE item=$id LIMIT 1");

	if ($IsSold) {
		// npcs selling this (Very Heavy Query)
		$query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbspawn2.zone,$tbzones.long_name,$tbnpctypes.class
					FROM $tbnpctypes,$tbmerchantlist,$tbspawn2,$tbzones,$tbspawnentry
					WHERE $tbmerchantlist.item=$id
					AND $tbnpctypes.id=$tbspawnentry.npcID
					AND $tbspawnentry.spawngroupID=$tbspawn2.spawngroupID
					AND $tbmerchantlist.merchantid=$tbnpctypes.merchant_id
					AND $tbzones.short_name=$tbspawn2.zone";
		$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
		if (mysqli_num_rows($result) > 0) {
			$MerchantList = "";
			$MerchantList .= $Separator;
			$Separator = "<hr />";
			$MerchantList .= "<ul>";
			$MerchantList .= "<li class='zone'>This item is sold by:</li>";
			$CurrentZone = "";
			while ($row = mysqli_fetch_array($result)) {
				$MerchantList .= "<li><a href='npc.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a>";
				$MerchantList .= " (" . price($item["price"]) . ")";
				$MerchantList .= "</li>";
			}
			$MerchantList .= "</ul>";
			echo $MerchantList;
		}
	}
}


// spawn points if its a ground item
$query = "SELECT $tbgroundspawns.*,$tbzones.short_name,$tbzones.long_name
			FROM $tbgroundspawns,$tbzones
			WHERE item=$id
			AND $tbgroundspawns.zoneid=$tbzones.zoneidnumber";
$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));

if (mysqli_num_rows($result) > 0) {
	echo "<b>This item spawns on the ground in : </b><br><br>\n";
	$CurrentZone = "";
	while ($row = mysqli_fetch_array($result)) {
		if ($CurrentZone != $row["short_name"]) {
			if ($CurrentZone != "") {
				echo "</ul>\n";
			}
			echo "<b><a href='zone.php?name=" . $row["short_name"] . "'>" . $row["long_name"] . "</a> at: </b>\n";
			echo "<ul>\n";
			$CurrentZone = $row["short_name"];
		}
		echo "<li>" . $row["max_y"] . " (Y), " . $row["max_x"] . " (X), " . $row["max_z"] . " (Z)</a></li>";
	}
	echo "</ul>\n";
}

echo "</div></div></div></div>";

include($includes_dir . "footers.php");

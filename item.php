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

if ( $name == "" ) {
  $name = $ItemRow["Name"];
}

if ( $item_icon == "" ) {
  $item_icon = $icons_url . "item_" . $ItemRow["icon"] . ".gif";
}

$XhtmlCompliant = true;

$item = $ItemRow;
$stats=BuildItemStats($item, 0);
include($includes_dir . 'headers.php');

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

echo "<div class='item-stats'>";
echo $stats;

if ($item["color"] > 0)
{
	$hexcolor = sprintf('%06x', $item["color"]);
	echo '<div style="width:120px">';
	echo '<div style="float:right; width:80px; height:24px; background-color: #'.$hexcolor.';"></div>';
	echo '<p><strong>Tint:</strong></p>';
	echo '</div>';
}


echo "</div>";

if (file_exists(getcwd() . "/icons/item_" . $item['icon'] . ".gif")) {
	if ($item["color"])
	{
		$hexcolor = sprintf('%06x', $item["color"]);
		echo "<div class='img-color'; style='background-color: #$hexcolor'></div>";
	}
	echo "<img src='" . $icons_url . "item_" . $item["icon"] . ".gif' />";
}

echo "</div></div>";
echo "<div class='drop-information'>";

// trade skills for which that item is a component
$query = "SELECT $tbtradeskillrecipe.name,$tbtradeskillrecipe.id,$tbtradeskillrecipe.tradeskill FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id AND $tbtradeskillrecipeentries.item_id=$id AND $tbtradeskillrecipeentries.componentcount>0 GROUP BY $tbtradeskillrecipe.id";
$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
$TradeskillResults = "";

if (mysqli_num_rows($result) > 0) {
  $TradeskillResults .= "<h3>Used in:</h3>";
	$TradeskillResults .= "<ul> ";
	while ($row = mysqli_fetch_array($result)) {
		$TradeskillResults .= "<li><a style='display: inline;' href='recipe.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a> (" . ucfirstwords($dbskills[$row["tradeskill"]]) . ")</li>";
	}
	$TradeskillResults .= "</ul>";
}
echo $TradeskillResults;

// trade skills which result is the component
$query = "SELECT $tbtradeskillrecipe.name,$tbtradeskillrecipe.id,$tbtradeskillrecipe.tradeskill FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id AND $tbtradeskillrecipeentries.item_id=$id AND $tbtradeskillrecipeentries.successcount>0 GROUP BY $tbtradeskillrecipe.id";
$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
$TradeskillResults = "";

if (mysqli_num_rows($result) > 0) {
	$TradeskillResults .= "<h3>Result of: </h3><ul>";
	while ($row = mysqli_fetch_array($result)) {
		$TradeskillResults .= "<li><a style='display: inline;' href='recipe.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a> (" . $dbskills[$row["tradeskill"]] . ")</li>";
	}
	$TradeskillResults .= "</ul>";
}
echo $TradeskillResults;

if ($ItemQuestSource) {

	$query = "SELECT * FROM $tbquestitems WHERE item_id=$id AND rewarded > 0";
	$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));

	if (mysqli_num_rows($result) > 0) {
		echo "<p>This item is the result of a quest.</p>";
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
			, lte.probability
			, lte.multiplier
			, lte.multiplier_min
			, lte.mindrop
			, lde.chance
			, lde.multiplier as lde_mult
		FROM $tbnpctypes nt
			, $tbloottableentries lte
			, $tblootdropentries lde
			, $tbzones z
		WHERE nt.loottable_id IN
			(SELECT loottable_Id FROM $tbloottableentries WHERE lootdrop_id IN
				(SELECT lootdrop_id FROM $tblootdropentries WHERE item_id = $id)
			)
		AND nt.loottable_id = lte.loottable_id
		AND lte.lootdrop_id = lde.lootdrop_id
		AND lde.item_id = $id
		AND z.zoneidnumber = nt.id DIV 1000
		ORDER BY nt.id ASC;
		";

		$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));

		if (mysqli_num_rows($result) > 0) {
			$CurrentZone = "";
			$displayName = "";
      			$DroppedList = "<h3>Dropped by:</h3>";
			$DroppedList .= "<ul>";
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
				// Calculate drop chance
				$chance = $row['chance'] / 100;
				$probability = $row['probability'] / 100;
				$min = $row['multiplier_min'];
				$mult = $row['multiplier'] - $min;
				// Calculate the probability of at least 1 dropping by taking "one minus the probability of failure"
				if ($min == 0) {
					// No min drop means each table runs $mult times
					$DropOne = round((1-((1-$chance*$probability)**$mult)) * 100, 2);
				} else {
					// The table gets 100% probability for $min drops, and the remainder get the usual treatment
					$DropOne = round((1-(1-$chance)**$min*(1-($chance*$probability)**$mult)) * 100, 2);
				}
				$DropMsg = "$DropOne";
				// Special loot tables that roll each individual item are marked with mindrop and droplimit as 0
				// These often come with lootdrop multipliers (separate from lootable multipliers)
				if ($row['mindrop'] == 0 && $row['droplimit'] == 0)
				{
					$DropPerKill = $DropOne * $row['lde_mult'];
					$DropMsg = "$DropOne-$DropPerKill";
				}
				// When tables have multiple chances to roll, the drop-per-kill ratio is higher than the "drop at least one" calculation
				// This is interesting when farming tradeskill materials
				if ($mult > 1)
				{
					$DropPerKill = round($chance * ($min + $mult * $probability) * 100, 2);
					$DropMsg = "$DropOne-$DropPerKill";
				}
				$DroppedList .= "
					<li>
						<a href='npc.php?id=" . $row["id"] . "'>" . trim(str_replace("_", " ", $row["name"]), '#') . " (" . $DropMsg . "%)</a>
					</li>";
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
			$MerchantList .= "<h3>Sold by:</h3>";
			$MerchantList .= "<ul>";
			$CurrentZone = "";
      while ($row = mysqli_fetch_array($result)) {
        // var_dump($row);
        if ($CurrentZone != $row["zone"]) {
          $MerchantList .= "
            <li class='zone'>
              <a href='zone.php?name=" . $row["zone"] . "'>" . $row["long_name"] . "</a>
            </li>";
          $CurrentZone = $row["zone"];
        }
				$MerchantList .= "<li><a style='display: inline;' href='npc.php?id=" . $row["id"] . "'>" . str_replace("_", " ", $row["name"]) . "</a>";
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

echo "</div></div>";
// NOT READY
// Query for players that own the item
// $ownerQuery = "SELECT DISTINCT ci.id,cd.name FROM character_inventory ci JOIN character_data cd ON cd.id = ci.id WHERE cd.anon = 0 AND ci.itemid = $id LIMIT 30";
// $ownerResult = mysqli_query($db, $ownerQuery) or message_die('item.php', 'MYSQL_QUERY', $ownerQuery, mysqli_error($db));
// if (!! mysqli_num_rows($ownerResult) && mysqli_num_rows($ownerResult) < 30) {
//   $ownerOutput = "<div id='player-owners' style='margin-top:1rem;'><fieldset><legend>Owners</legend>";
//   $index = 0;
//   while ($row = mysqli_fetch_array($ownerResult)) {
//     $ownerOutput .= "<a style='display: inline;' target='_blank' href='" . $charbrowser_url . "character.php?char=" . $row['name'] . "'>";
//     $ownerOutput .= $row['name'];
//     $ownerOutput .= "</a>";
//     $index++;
//     if ($index < mysqli_num_rows($ownerResult) ) {
//       $ownerOutput .= ", ";
//     }
//   }

//   echo $ownerOutput . "</fieldset></div>";
// }

// echo '<pre>';
// print_r($item);
// echo '</pre>';
echo "</div></div>";

include($includes_dir . "footers.php");

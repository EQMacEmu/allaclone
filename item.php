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

$ItemRow = GetItemRow($id);
$name = $ItemRow["Name"];

/** Here the following stands :
 *    $id : ID of the item to display
 *    $name : name of the item to display
 *    $ItemRow : row of the item to display extracted from the database
 *    The item actually exists
 */

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
echo "<strong>";
echo $item["Name"];
if ($item["itemclass"] == 0 && $item["maxcharges"] > 0 && in_array($item["itemtype"], $stackable)) {
	echo " (stackable)";
}
echo "</strong>";

echo "<div class='item-stats'>";
echo $stats;

// Additional/hidden item information
$focus = getPetFocus($item["id"]);
if (!empty($focus))
	print "$focus\n";

if ($item["material"] > 0) {
	echo "Material: " . $itemmaterial[$item["material"]] . "\n";
}

if ($item["slots"] & $wearable_slots && $item["color"] > 0) {
	$color = $item["color"];
	$r = ($color & (0xff<<16))>>16;
	$g = ($color & (0xff<<8))>>8;
	$b = $color & (0xff);
	$hexprint = sprintf('%d, %d, %d', $r, $g, $b);
	$hexcolor = sprintf('%06x', $item["color"]);
	echo '<p class="item-tint" style="display: inline-flex; align-items: center; gap: 0.5rem">';
	echo "Tint: ($hexprint)";
	echo '<span class="color-block" style="background-color: #'.$hexcolor.'; height: 24px; width: 80px; display: inline-block; border: 1px solid #aaa;"></span>';
	echo "</p>\n";
}

if ($item["light"] > 0) {
	echo "Light: " . $item["light"] . "\n";
}


echo "</div>"; // item-stats

if (file_exists(getcwd() . "/icons/item_" . $item['icon'] . ".gif")) {
	if ($item["slots"] & $wearable_slot && $item["color"] > 0)
	{
		$hexcolor = sprintf('%06x', $item["color"]);
		echo "<div class='img-color'; style='background-color: #$hexcolor'></div>";
	}
	echo "<img src='" . $icons_url . "item_" . $item["icon"] . ".gif' />";
}

echo "</div></div>";
echo "<div class='drop-information'>";

// trade skills for which that item is a component
$filter = gatefilter(array($tbtradeskillrecipe));
$query = "SELECT $tbtradeskillrecipe.name,$tbtradeskillrecipe.id,$tbtradeskillrecipe.tradeskill FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id AND $tbtradeskillrecipeentries.item_id=$id AND $tbtradeskillrecipeentries.componentcount>0 $filter GROUP BY $tbtradeskillrecipe.id";
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

		$dropfilter = gatefilter(array($tbzones, $tbloottable, $tblootdrop, $tblootdropentries));
		$dropentriesfilter = gatefilter(array($tblootdropentries));
		$query = "
		SELECT $tbnpctypes.id,
			$tbzones.short_name,
			$tbzones.long_name,
			$tbloottableentries.probability/100 as tbl_chance,
			$tbloottableentries.multiplier_min as tbl_min,
			$tbloottableentries.multiplier as tbl_max,
			$tbloottableentries.mindrop as drp_min,
			$tbloottableentries.droplimit as drp_max,
			$tblootdropentries.multiplier as drp_mult,
			$tblootdropentries.chance/100 as drp_chance,
			$tblootdropentries.lootdrop_id as ldid,
			$tbnpctypes.name
		FROM $tbzones, $tbloottable, $tblootdrop, $tbnpctypes
		JOIN $tbloottableentries ON $tbnpctypes.loottable_id = $tbloottableentries.loottable_id
		JOIN $tblootdropentries ON $tbloottableentries.lootdrop_id = $tblootdropentries.lootdrop_id
		WHERE $tblootdropentries.item_id = $id
		AND $tbnpctypes.loottable_id = $tbloottableentries.loottable_id
		AND $tblootdrop.id = $tbloottableentries.lootdrop_id
		AND $tbloottable.id = $tbloottableentries.loottable_id
		AND $tbloottableentries.lootdrop_id = $tblootdropentries.lootdrop_id
		AND $tbzones.zoneidnumber = $tbnpctypes.id DIV 1000
		$dropfilter
		ORDER BY $tbnpctypes.id ASC;
		";

		$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));

		if (mysqli_num_rows($result) > 0) {
			$CurrentZone = "";
			$displayName = "";
			$DroppedList = "<h3>Dropped by (***no spawn table):</h3>";
			$DroppedList .= "<ul>";
			while ($row = mysqli_fetch_array($result)) {
				// var_dump($row);
				$nospawn = "";
				if (!HasSpawnTable($row["id"]))
				{
					$nospawn = "***";
				}

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
				$tbl_chance = $row["tbl_chance"];
				$tbl_min = $row["tbl_min"];
				$tbl_max = $row["tbl_max"];
				$tbl_diff = $tbl_max - $tbl_min;

				$drp_chance = $row["drp_chance"];
				$drp_min = $row["drp_min"];
				$drp_max = $row["drp_max"];
				if ($drp_max < $drp_min)
					$drp_max = $drp_min;
				$drp_diff = $drp_max - $drp_min;
				$drp_mult = $row["drp_mult"];
				if ($drp_mult < 1)
					$drp_mult = 1;

				$ldid = $row["ldid"];
				$sum_result = mysqli_query($db, "select SUM(chance)/100 as sum FROM lootdrop_entries where lootdrop_id=$ldid $dropentriesfilter;") or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
				$sum_row = mysqli_fetch_array($sum_result);
				$lde_sum = $sum_row["sum"];

				// Calculate table values
				$table_chance = $tbl_chance * $tbl_diff;
				$table_failure = 0;
				if ( $tbl_min == 0 ) {
					$table_failure = (1-$tbl_chance)**$tbl_diff;
				}

				// Calculate drop values
				$drop_chance = 0;
				$drop_failure = 0;
				if ($drp_min == 0 && $drp_max == 0) {
					$drop_chance = $drp_chance * $drp_mult;
					$drop_failure = (1-$drp_chance)**$drp_mult;
				} else {
					$sum_chance = $drp_chance/$lde_sum;
					if ($lde_sum > 1) {
						$drops = $drp_max;
					} else {
						$drops = $drp_min + $drp_diff*$lde_sum;
					}
					$drop_chance = $drops*$sum_chance*(1+$drp_chance*($drp_mult-1));
					$drop_failure = ((1-($sum_chance))**$drp_min * (1-($drp_chance))**$drp_diff);
				}

				$average_drops = (($tbl_min + $table_chance) * $drop_chance) * 100;
				$dropsone = (1-($table_failure + ((1-$table_failure) * $drop_failure))) * 100;

				if ($average_drops > 10)
					$average_drops = round($average_drops, 0);
				else if ($average_drops > 1)
					$average_drops = round($average_drops, 1);
				else
					$average_drops = round($average_drops, 2);
				if ($dropsone > 10)
					$dropsone = round($dropsone, 0);
				else if ($dropsone > 1)
					$dropsone = round($dropsone, 1);
				else
					$dropsone = round($dropsone, 2);

				if ($tbl_max > 1 || $drp_max > 1 || $drp_mult > 1) {
					$average_drops = $average_drops / 100;
					$DropMsg = "($dropsone%, $average_drops avg drops per kill)";
				} else {
					$DropMsg = "($dropsone%)";
				}
				$DroppedList .= "
					<li>
						<a href='npc.php?id=" . $row["id"] . "'>$nospawn" . trim(str_replace("_", " ", $row["name"]), '#') . " " . $DropMsg . "</a>
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
		$filter = gatefilter(array($tbzones, $tbmerchantlist));
		$query = "SELECT $tbnpctypes.id,$tbnpctypes.name,$tbspawn2.zone,$tbzones.long_name,$tbnpctypes.class
					FROM $tbnpctypes,$tbmerchantlist,$tbspawn2,$tbzones,$tbspawnentry
					WHERE $tbmerchantlist.item=$id
					AND $tbnpctypes.id=$tbspawnentry.npcID
					AND $tbspawnentry.spawngroupID=$tbspawn2.spawngroupID
					AND $tbmerchantlist.merchantid=$tbnpctypes.merchant_id
					AND $tbzones.short_name=$tbspawn2.zone
					$filter";
          
		$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
		if (mysqli_num_rows($result) > 0) {
			$MerchantList = "";
			$MerchantList .= $Separator;
			$Separator = "<hr />";
			$MerchantList .= "<h3>Sold by (***no spawn table):</h3>";
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
				$nospawn = "";
				if (!HasSpawnTable($row["id"]))
				{
					$nospawn = "***";
				}
				$MerchantList .= "<li><a style='display: inline;' href='npc.php?id=" . $row["id"] . "'>$nospawn" . str_replace("_", " ", $row["name"]) . "</a>";
				$MerchantList .= " (" . price($item["price"]) . ")";
				$MerchantList .= "</li>";
			}
			$MerchantList .= "</ul>";
			echo $MerchantList;
		}
	}
}


// Other bard items with this skill
if ($item["bardtype"] > 0) {
	echo "<h3>Other items with this Bard skill.</h3>";
	$query = "SELECT id, name, bardvalue FROM items WHERE bardtype=".$item["bardtype"]." and id!=".$item["id"]." order by bardvalue desc;";
	#echo $query;
	$result = mysqli_query($db, $query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	echo "<ul>";
	while ($row = mysqli_fetch_array($result)) {
		$val = ($row["bardvalue"] * 10) - 100;
		echo "<li><a href=item.php?id=" . $row["id"] . ">" . $row["name"] . " (+$val%)</a>";
	}
	echo "</ul>";
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

<?php

/** If the parameter 'isearch' is set, queries for the items matching the criterias and displays them, along with an item search form.
 *    If only one and only one item is found then this item is displayed.
 *  If 'isearch' is not set, displays a search item form.
 *  If no criteria is set then it is equivalent to searching for all items.
 *  For compatbility with Wikis and multi-word searches, underscores are treated as jokers in 'iname'.
 */
include('./includes/config.php');
include($includes_dir . 'constantes.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');


$isearch       = (isset($_GET['isearch']) ? $_GET['isearch'] : '');
$iname         = (isset($_GET['iname']) ? $_GET['iname'] : '');
$iclass        = (isset($_GET['iclass']) ? addslashes($_GET['iclass']) : -1);
$irace         = (isset($_GET['irace']) ? addslashes($_GET['irace']) : -1);
$islot         = (isset($_GET['islot']) ? addslashes($_GET['islot']) : '');
$istat1        = (isset($_GET['istat1']) ? addslashes($_GET['istat1']) : '');
$istat1comp    = (isset($_GET['istat1comp']) ? addslashes($_GET['istat1comp']) : '');
$istat1value   = (isset($_GET['istat1value']) ? addslashes($_GET['istat1value']) : '');
$istat2        = (isset($_GET['istat2']) ? addslashes($_GET['istat2']) : '');
$istat2comp    = (isset($_GET['istat2comp']) ? addslashes($_GET['istat2comp']) : '');
$istat2value   = (isset($_GET['istat2value']) ? addslashes($_GET['istat2value']) : '');
$iresists      = (isset($_GET['iresists']) ? addslashes($_GET['iresists']) : '');
$iresistscomp  = (isset($_GET['iresistscomp']) ? addslashes($_GET['iresistscomp']) : '');
$iresistsvalue = (isset($_GET['iresistsvalue']) ? addslashes($_GET['iresistsvalue']) : '');
$iheroics      = (isset($_GET['iheroics']) ? addslashes($_GET['iheroics']) : '');
$iheroicscomp  = (isset($_GET['iheroicscomp']) ? addslashes($_GET['iheroicscomp']) : '');
$iheroicsvalue = (isset($_GET['iheroicsvalue']) ? addslashes($_GET['iheroicsvalue']) : '');
$imod          = (isset($_GET['imod']) ? addslashes($_GET['imod']) : '');
$imodcomp      = (isset($_GET['imodcomp']) ? addslashes($_GET['imodcomp']) : '');
$imodvalue     = (isset($_GET['imodvalue']) ? addslashes($_GET['imodvalue']) : '');
$itype         = (isset($_GET['itype']) ? addslashes($_GET['itype']) : -1);
$iaugslot      = (isset($_GET['iaugslot']) ? addslashes($_GET['iaugslot']) : '');
$ieffect       = (isset($_GET['ieffect']) ? addslashes($_GET['ieffect']) : '');
$ireqlevel     = (isset($_GET['ireqlevel']) ? addslashes($_GET['ireqlevel']) : '');
$iminlevel     = (isset($_GET['iminlevel']) ? addslashes($_GET['iminlevel']) : '');
$inodrop       = (isset($_GET['inodrop']) ? addslashes($_GET['inodrop']) : '');
$iavailability = (isset($_GET['iavailability']) ? addslashes($_GET['iavailability']) : '');
$iavailevel    = (isset($_GET['iavailevel']) ? addslashes($_GET['iavailevel']) : '');
$ideity        = (isset($_GET['ideity']) ? addslashes($_GET['ideity']) : '');

if ($isearch != "") {
	$Query  = "SELECT $tbitems.* FROM ($tbitems";

	if ($DiscoveredItemsOnly == TRUE) {
		$Query .= ",discovered_items";
	}

	if ($iavailability == 1) {
		// mob dropped
		$Query .= ",$tblootdropentries,$tbloottableentries,$tbnpctypes";
	}
	$Query  .= ")";
	$s = " WHERE";
	if ($ieffect != "") {
		$effect = "%" . str_replace(',', '%', str_replace(' ', '%', addslashes($ieffect))) . "%";
		$Query .= " LEFT JOIN $tbspells AS proc_s ON proceffect=proc_s.id";
		$Query .= " LEFT JOIN $tbspells AS worn_s ON worneffect=worn_s.id";
		$Query .= " LEFT JOIN $tbspells AS focus_s ON focuseffect=focus_s.id";
		$Query .= " LEFT JOIN $tbspells AS click_s ON clickeffect=click_s.id";
		$Query .= " WHERE (proc_s.name LIKE '$effect'
				OR worn_s.name LIKE '$effect'
				OR focus_s.name LIKE '$effect'
				OR click_s.name LIKE '$effect') ";
		$s = "AND";
	}
	if (($istat1 != "") and ($istat1value != "")) {
		if ($istat1 == "ratio") {
			$Query .= " $s ($tbitems.damage/$tbitems.delay $istat1comp $istat1value) AND ($tbitems.damage>0)";
			$s = "AND";
		} else {
			$Query .= " $s ($tbitems.$istat1 $istat1comp $istat1value)";
			$s = "AND";
		}
	}
	if (($istat2 != "") and ($istat2value != "")) {
		if ($istat2 == "ratio") {
			$Query .= " $s ($tbitems.delay/$tbitems.damage $istat2comp $istat2value) AND ($tbitems.damage>0)";
			$s = "AND";
		} else {
			$Query .= " $s ($tbitems.$istat2 $istat2comp $istat2value)";
			$s = "AND";
		}
	}
	if (($imod != "") and ($imodvalue != "")) {
		$Query .= " $s ($tbitems.$imod $imodcomp $imodvalue)";
		$s = "AND";
	}
	if ($iavailability == 1) // mob dropped
	{
		$Query .= " $s $tblootdropentries.item_id=$tbitems.id
				AND $tbloottableentries.lootdrop_id=$tblootdropentries.lootdrop_id
				AND $tbloottableentries.loottable_id=$tbnpctypes.loottable_id";
		if ($iavaillevel > 0) {
			$Query .= " AND $tbnpctypes.level<=$iavaillevel";
		}
		$s = "AND";
	}
	if ($iavailability == 2) // merchant sold
	{
		$Query .= ",$tbmerchantlist $s $tbmerchantlist.item=$tbitems.id";
		$s = "AND";
	}
	if ($DiscoveredItemsOnly == TRUE) {
		$Query .= " $s discovered_items.item_id=$tbitems.id";
		$s = "AND";
	}
	if ($iname != "") {
		$name = addslashes(str_replace("_", "%", str_replace(" ", "%", $iname)));
		$Query .= " $s ($tbitems.Name like '%" . $name . "%')";
		$s = "AND";
	}
	if ($iclass > 0) {
		$class = 2**$iclass;
		$Query .= " $s ($tbitems.classes & $class) ";
		$s = "AND";
	}
	if ($ideity > 0) {
		$Query .= " $s ($tbitems.deity   & $ideity) ";
		$s = "AND";
	}
	if ($irace >= 0) {
		$race = 2**$irace;
		$Query .= " $s ($tbitems.races   & $race) ";
		$s = "AND";
	}
	if ($itype >= 0) {
		$Query .= " $s ($tbitems.itemtype=$itype) ";
		$s = "AND";
	}
	if ($islot > 0) {
		$slot = 2**$islot;
		$Query .= " $s ($tbitems.slots   & $slot) ";
		$s = "AND";
	}
	if ($iaugslot > 0) {
		$AugSlot = pow(2, $iaugslot) / 2;
		$Query .= " $s ($tbitems.augtype & $AugSlot) ";
		$s = "AND";
	}
	if ($iminlevel > 0) {
		$Query .= " $s ($tbitems.reqlevel>=$iminlevel) ";
		$s = "AND";
	}
	if ($ireqlevel > 0) {
		$Query .= " $s ($tbitems.reqlevel<=$ireqlevel) ";
		$s = "AND";
	}
	if ($inodrop) {
		$Query .= " $s ($tbitems.nodrop=1)";
		$s = "AND";
	}

	foreach ($hide_item_id as $hideme) {
		$Query .= " $s ($tbitems.id != $hideme)";
		$s = "AND"; // Block by ID set in config
	}

	$Query .= " GROUP BY $tbitems.id ORDER BY $tbitems.Name LIMIT " . (LimitToUse($MaxItemsReturned) + 1);
	$QueryResult = mysqli_query($db, $Query);
	//print $Query;
	if (mysqli_num_rows($QueryResult) == 1) {
		$row = mysqli_fetch_array($QueryResult);
		header("Location: item.php?id=" . $row["id"]);
		exit();
	}
} else {
	$iname = "";
}

/** Here the following holds :
 *    $QueryResult : items queried for if any query was issued, otherwise it is not defined
 *    $i* : previously-typed criterias, or empty by default
 *    $isearch is set if a query was issued
 */

$Title = "Item Search";
$XhtmlCompliant = TRUE;
include($includes_dir . 'headers.php');

echo "<div class='container items'>";
echo "<form class='item-refine' method='GET' action='" . $PHP_SELF . "'>";
echo "<input type='text' value=\"$iname\" name='iname' placeholder='Item Name' />";
echo "<div class='select-wrapper onethird'>";
echo SelectIClass("iclass", $iclass);
echo "</div>";
echo "<div class='select-wrapper onethird'>";
echo SelectRace("irace",   $irace);
echo "</div>";
echo "<div class='select-wrapper onethird'>";
echo SelectSlot("islot",   $islot);
echo "</div>";

echo "<div class='form-stat-wrapper'>";
echo "<div class='select-wrapper'>";
echo SelectStats("istat1", $istat1);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "    <select name='istat1comp'>";
echo "      <option value='&gt;='" . ($istat1comp == '>=' ? " selected='1'" : "") . ">&gt;=</option>";
echo "      <option value='&lt;='" . ($istat1comp == '<=' ? " selected='1'" : "") . ">&lt;=</option>";
echo "      <option value='='" . ($istat1comp == '='  ? " selected='1'" : "") . ">=</option>";
echo "      <option value='&lt'" . ($istat1comp == '<' ? " selected='1'" : "") . ">&lt</option>";
echo "    </select>";
echo "</div>";
echo "    <input type='text' size='4' name='istat1value' value='" . $istat1value . "' placeholder='0' />";
echo "</div>";


echo "<div class='form-stat-wrapper'>";
echo "<div class='select-wrapper'>";
echo SelectStats("istat2", $istat2);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "    <select name='istat2comp'>";
echo "      <option value='&gt;='" . ($istat2comp == '>=' ? " selected='1'" : "") . ">&gt;=</option>";
echo "      <option value='&lt;='" . ($istat2comp == '<=' ? " selected='1'" : "") . ">&lt;=</option>";
echo "      <option value='='" . ($istat2comp == '='  ? " selected='1'" : "") . ">=</option>";
echo "      <option value='&lt'" . ($istat2comp == '<' ? " selected='1'" : "") . ">&lt</option>";
echo "    </select>";
echo "</div>";
echo "    <input type='text' size='4' name='istat2value' value='" . $istat2value . "' placeholder='0' />";
echo "</div>";


echo "<div class='form-stat-wrapper'>";
echo "<div class='select-wrapper'>";
echo SelectResists("iresists", $iresists);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "    <select name='iresistscomp'>";
echo "      <option value='&gt;='" . ($iresistscomp == '>=' ? " selected='1'" : "") . ">&gt;=</option>";
echo "      <option value='&lt;='" . ($iresistscomp == '<=' ? " selected='1'" : "") . ">&lt;=</option>";
echo "      <option value='='" . ($iresistscomp == '='  ? " selected='1'" : "") . ">=</option>";
echo "      <option value='&lt'" . ($iresistscomp == '<' ? " selected='1'" : "") . ">&lt</option>";
echo "    </select>";
echo "</div>";
echo "    <input type='text' size='4' name='iresistsvalue' value='" . $iresistsvalue . "' placeholder='0' />";
echo "</div>";



echo "<div class='form-stat-wrapper'>";
echo "<div class='select-wrapper'>";
echo SelectModifiers("imod", $imod);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "    <select name='imodcomp'>";
echo "      <option value='&gt;='" . ($imodcomp == '>=' ? " selected='1'" : "") . ">&gt;=</option>";
echo "      <option value='&lt;='" . ($imodcomp == '<=' ? " selected='1'" : "") . ">&lt;=</option>";
echo "      <option value='='" . ($imodcomp == '='  ? " selected='1'" : "") . ">=</option>";
echo "      <option value='&lt'" . ($imodcomp == '<' ? " selected='1'" : "") . ">&lt</option>";
echo "    </select>";
echo "</div>";
echo "    <input type='text' size='4' name='imodvalue' value='" . $imodvalue . "' placeholder='0' />";
echo "</div>";

echo "<input type='text' value='" . $ieffect . "' size='30' name='ieffect' placeholder='Effect' />";
echo "<div class='select-wrapper half'>";
echo SelectLevel("iminlevel", $ServerMaxLevel, $iminlevel);
echo "</div>";
echo "<div class='select-wrapper half'>";
echo SelectLevel("ireqlevel", $ServerMaxLevel, $ireqlevel);
echo "</div>";
echo "<div class='checkbox'>";
echo "<label class='text' for='inodrop'>Include No Drop</label>";
echo "<label class='checkbox'>";
echo "<input type='checkbox' name='inodrop'" . ($inodrop ? " checked='checked'" : "") . "/><span></span>";
echo "</label>";
echo "</div>";

echo "<div class='select-wrapper'>";
echo "    <select name='iavailability'>";
echo "      <option value='0' " . ($iavailability == 0 ? " selected='1'" : "") . ">Availability</option>";
echo "      <option value='1' " . ($iavailability == 1 ? " selected='1'" : "") . ">Mob Dropped</option>";
echo "      <option value='2' " . ($iavailability == 2 ? " selected='1'" : "") . ">Merchant Sold</option>";
echo "    </select>";
echo "</div>";
echo "<div class='select-wrapper'>";
echo SelectLevel("iavaillevel", $ServerMaxLevel, $iavaillevel);
echo "</div>";
echo "<div class='select-wrapper'>";
echo SelectDeity("ideity", $ideity);
echo "</div>";
echo "<div><input type='submit' value='Search' name='isearch'/> <input type='reset' value='Reset' /></div>";
echo "</form>";

// Print the query results if any
if (isset($QueryResult)) {

	$Tableborder = 0;

	$num_rows = mysqli_num_rows($QueryResult);
	$total_row_count = $num_rows;
	if ($num_rows > LimitToUse($MaxItemsReturned)) {
		$num_rows = LimitToUse($MaxItemsReturned);
	}
	echo "<div>";
	if ($num_rows == 0) {
		echo "<strong>No items found...</strong></div>";
	} else {
		$OutOf = "";
		if ($total_row_count > $num_rows) {
			$OutOf = "Searches are limited to 100 results";
		}
		echo "<p><strong>" . $OutOf . "</strong></p>";
		echo "</div>";

		echo "<div class='search-item-list'><table border='$Tableborder' cellpadding='5' width='100%'>";
		echo "<tr>
					<th class='menuh iicon'>Icon</th>
					<th class='menuh iname'>Name</th>
					<th class='menuh itype'>Type</th>
					<th class='menuh iac'>AC</th>
					<th class='menuh ihp'>HP</th>
					<th class='menuh imana'>Mana</th>
					<th class='menuh idmg'>Damage</th>
					<th class='menuh idly'>Delay</th>
					<th class='menuh iid'>ID</th>
					</tr>";
		$RowClass = "lr";
		for ($count = 1; $count <= $num_rows; $count++) {
			$TableData = "";
			$row = mysqli_fetch_array($QueryResult);
			$TableData .= "<tr valign='top' class='" . $RowClass . "'><td>";
			if (file_exists(getcwd() . "/icons/item_" . $row["icon"] . ".gif")) {
				$TableData .= "<img src='" . $icons_url . "item_" . $row["icon"] . ".gif' align='left'/>";
			} else {
				$TableData .= "<img src='" . $icons_url . "item_.gif' align='left'/>";
			}
			$TableData .= "</td><td>";


			$id = $row["id"];
			$attrs = "data-item-id=\"$id\" class=\"item-link\"";
			$TableData .= "<a href='item.php?id=$id' id='$id' $attrs>" . $row["Name"] . "</a>";

			$TableData .= "</td><td>";
			$TableData .= $dbitypes[$row["itemtype"]];
			$TableData .= "</td><td>";
			$TableData .= $row["ac"];
			$TableData .= "</td><td>";
			$TableData .= $row["hp"];
			$TableData .= "</td><td>";
			$TableData .= $row["mana"];
			$TableData .= "</td><td>";
			$TableData .= $row["damage"];
			$TableData .= "</td><td>";
			$TableData .= $row["delay"];

			$TableData .= "</td><td>";
			$TableData .= $row["id"];
			$TableData .= "</td></tr>";

			if ($RowClass == "lr") {
				$RowClass = "dr";
			} else {
				$RowClass = "lr";
			}

			print $TableData;
		}
		echo "</table></div></div>";
	}
}

echo "</div>";
echo "</div>";

include($includes_dir . "footers.php");

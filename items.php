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
$item_stat_cols = array_keys(ITEM_SEARCH_STATS);
$item_comps = array('>=','<=','=','<','>');
$iclass        = isset($_GET['iclass']) ? sql_int($_GET['iclass'], -1) : -1;
$irace         = isset($_GET['irace']) ? sql_int($_GET['irace'], -1) : -1;
$islot         = isset($_GET['islot']) ? sql_int($_GET['islot'], 0) : 0;
$istat1        = isset($_GET['istat1']) ? sql_ident($_GET['istat1'], $item_stat_cols) : '';
$istat1comp    = isset($_GET['istat1comp']) ? sql_op($_GET['istat1comp'], $item_comps) : '';
$istat1value   = (isset($_GET['istat1value']) && is_numeric($_GET['istat1value']) ? $_GET['istat1value'] + 0 : '');
$istat2        = isset($_GET['istat2']) ? sql_ident($_GET['istat2'], $item_stat_cols) : '';
$istat2comp    = isset($_GET['istat2comp']) ? sql_op($_GET['istat2comp'], $item_comps) : '';
$istat2value   = (isset($_GET['istat2value']) && is_numeric($_GET['istat2value']) ? $_GET['istat2value'] + 0 : '');
$iresists      = sql_ident($_GET['iresists'] ?? '', array_keys(ITEM_RESISTS));
$iresistscomp  = sql_op($_GET['iresistscomp'] ?? '>=', $item_comps);
$resistInput   = $_GET['iresistsvalue'] ?? '';
$iresistsvalue = is_string($resistInput) ? trim($resistInput) : null;
$searchError   = '';
if ($iresistsvalue !== '') {
	if (($_GET['iresists'] ?? '') === 'svcorruption') {
		$searchError = 'Corruption resistance is unavailable in this database. Choose another resistance.';
	} elseif ($iresists === '') {
		$searchError = 'Choose a supported resistance (Magic, Fire, Cold, Poison or Disease).';
	} elseif ($iresistscomp === '' || filter_var($iresistsvalue, FILTER_VALIDATE_FLOAT) === false) {
		$searchError = 'Choose a comparison and enter a number for the resist filter.';
	}
}
// Old search forms submitted empty modifier fields alongside ordinary criteria.
if (($istat1 === '' && ($_GET['istat1'] ?? '') !== '' && ($_GET['istat1value'] ?? '') !== '')
	|| ($istat2 === '' && ($_GET['istat2'] ?? '') !== '' && ($_GET['istat2value'] ?? '') !== '')) {
	$searchError = 'This stat is unavailable as a TAKP item-search filter. Choose a listed stat and search again.';
} elseif (($_GET['imod'] ?? '') !== '' && ($_GET['imodvalue'] ?? '') !== '') {
	$searchError = 'Item modifier searches are unavailable on TAKP. Choose a listed filter and search again.';
} elseif (($_GET['iheroics'] ?? '') !== '' && ($_GET['iheroicsvalue'] ?? '') !== '') {
	$searchError = 'Heroic stat searches are unavailable on TAKP. Choose a listed filter and search again.';
} elseif (isset($_GET['iaugslot']) && (!is_string($_GET['iaugslot']) || sql_int($_GET['iaugslot']) > 0)) {
	$searchError = 'Augment slot searches are unavailable on TAKP. Choose a listed filter and search again.';
}
$itype         = isset($_GET['itype']) ? sql_int($_GET['itype'], -1) : -1;
$ieffect       = (isset($_GET['ieffect']) ? $_GET['ieffect'] : '');
$ireqlevel     = isset($_GET['ireqlevel']) ? sql_int($_GET['ireqlevel'], 0) : 0;
$iminlevel     = isset($_GET['iminlevel']) ? sql_int($_GET['iminlevel'], 0) : 0;
// A bookmarked "Include No Drop" checkbox (inodrop=on) includes all items.
$inodrop = $_GET['inodrop'] ?? 'all';
if (!in_array($inodrop, array('only', 'exclude'), true)) {
	$inodrop = 'all';
}
$iavailability = isset($_GET['iavailability']) ? sql_int($_GET['iavailability'], 0) : 0;
if (!in_array($iavailability, array(0, 1, 2), true)) {
	$iavailability = 0;
}
// Form field is iavaillevel; accept legacy misspelling iavailevel for bookmarks.
$iavaillevel_raw = isset($_GET['iavaillevel']) ? $_GET['iavaillevel'] : (isset($_GET['iavailevel']) ? $_GET['iavailevel'] : 0);
$iavaillevel   = sql_int($iavaillevel_raw, 0);
$ideity        = isset($_GET['ideity']) ? sql_int($_GET['ideity'], 0) : 0;

if ($isearch != "" && $searchError === '') {
	$Query  = "SELECT $tbitems.* FROM ($tbitems";

	if ($DiscoveredItemsOnly == TRUE) {
		$Query .= ",discovered_items";
	}

	if ($iavailability == 1) {
		// mob dropped
		$Query .= ",$tblootdropentries,$tbloottableentries,$tbnpctypes";
	}
	$Query  .= ")";
	if ($iavailability == 2) {
		// merchant sold
		$Query .= " INNER JOIN $tbmerchantlist ON $tbmerchantlist.item=$tbitems.id";
	}
	$s = " WHERE";
	if ($ieffect != "") {
		$effect = "%" . sql_like($db, $ieffect, array(' ' => '%', ',' => '%')) . "%";
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
	$stat_filters = [
		[$istat1, $istat1comp, $istat1value],
		[$istat2, $istat2comp, $istat2value],
	];
	foreach ($stat_filters as [$stat, $comparison, $value]) {
		if ($stat === '' || $value === '') {
			continue;
		}
		if ($stat === 'ratio') {
			$comparison = $comparison ?: '>=';
			// Compare damage / delay without rounding the quotient.
			$Query .= " $s ($tbitems.damage > 0 AND $tbitems.delay > 0";
			$Query .= " AND $tbitems.damage $comparison ($value * $tbitems.delay))";
		} else {
			$Query .= " $s ($tbitems.$stat $comparison $value)";
		}
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
	if ($DiscoveredItemsOnly == TRUE) {
		$Query .= " $s discovered_items.item_id=$tbitems.id";
		$s = "AND";
	}
	if ($iresistsvalue !== '') {
		$Query .= " $s ($tbitems.$iresists $iresistscomp $iresistsvalue)";
		$s = "AND";
	}
	if ($iname != "") {
		$name = sql_like($db, $iname);
		$Query .= " $s ($tbitems.Name like '%" . $name . "%')";
		$s = "AND";
	}
	if ($iclass >= 0) {
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
	if ($iminlevel > 0) {
		$Query .= " $s ($tbitems.reqlevel>=$iminlevel) ";
		$s = "AND";
	}
	if ($ireqlevel > 0) {
		$Query .= " $s ($tbitems.reqlevel<=$ireqlevel) ";
		$s = "AND";
	}
	if ($inodrop !== 'all') {
		$drop_operator = $inodrop === 'only' ? '=' : '<>';
		$Query .= " $s ($tbitems.nodrop $drop_operator 0)";
		$s = "AND";
	}

	foreach ($hide_item_id as $hideme) {
		$Query .= " $s ($tbitems.id != $hideme)";
		$s = "AND"; // Block by ID set in config
	}

	$Query .= " GROUP BY $tbitems.id ORDER BY $tbitems.Name LIMIT " . (LimitToUse($MaxItemsReturned) + 1);
	$QueryResult = mysqli_query($db, $Query);
	if ($QueryResult === false) {
		message_die('items.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
	}
	//print $Query;
	if (mysqli_num_rows($QueryResult) == 1) {
		$row = mysqli_fetch_array($QueryResult);
		header("Location: item.php?id=" . $row["id"]);
		exit();
	}
} elseif ($isearch == "") {
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
echo "<form class='item-refine' method='GET' action='" . html_attr($PHP_SELF) . "'>";
echo "<input type='text' value=\"" . html_attr($iname) . "\" name='iname' placeholder='Item Name' />";
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
echo "    <input type='text' size='4' name='istat1value' value='" . html_attr($istat1value) . "' placeholder='0' />";
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
echo "    <input type='text' size='4' name='istat2value' value='" . html_attr($istat2value) . "' placeholder='0' />";
echo "</div>";


echo "<div class='form-stat-wrapper'>";
echo "<div class='select-wrapper'>";
echo SelectResists("iresists", $iresists);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "    <select name='iresistscomp'>";
foreach ($item_comps as $op) {
	$label = html_attr($op);
	$selected = $iresistscomp === $op ? " selected" : "";
	echo "<option value='$label'$selected>$label</option>";
}
echo "    </select>";
echo "</div>";
echo "    <input type='text' size='4' name='iresistsvalue' value='" . html_attr($iresistsvalue) . "' placeholder='0' />";
echo "</div>";

echo "<input type='text' value='" . html_attr($ieffect) . "' size='30' name='ieffect' placeholder='Effect' />";
echo "<div class='select-wrapper half'>";
echo SelectLevel("iminlevel", $ServerMaxLevel, $iminlevel);
echo "</div>";
echo "<div class='select-wrapper half'>";
echo SelectLevel("ireqlevel", $ServerMaxLevel, $ireqlevel);
echo "</div>";
echo "<div class='select-wrapper'>";
echo "<select name='inodrop' aria-label='No Drop filter'>";
WriteIt("all", "All items", $inodrop);
WriteIt("only", "No Drop only", $inodrop);
WriteIt("exclude", "Exclude No Drop", $inodrop);
echo "</select>";
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

if ($isearch != "" && $searchError !== '') {
	echo "<p>$searchError</p>";
}

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

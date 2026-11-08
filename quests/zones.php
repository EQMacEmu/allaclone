<?php
include('../includes/constantes.php');
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mysql.php');
include('../includes/quest_helpers.php');

if (empty($AllowQuestsNPC)) {
	$Title = "Quests";
	include('../includes/headers.php');
	print "<div class='container quests'><p>Quest browsing is disabled.</p></div></div>";
	include('../includes/footers.php');
	exit;
}

$aZone = isset($_REQUEST['aZone']) ? $_REQUEST['aZone'] : '';
$searchItemRaw = isset($_REQUEST['item_id']) ? trim($_REQUEST['item_id']) : '';
$searchItemId = 0;
$searchItemName = '';
if ($searchItemRaw !== '') {
	if (ctype_digit($searchItemRaw)) {
		$searchItemId = (int)$searchItemRaw;
	} else {
		$searchItemName = $searchItemRaw;
	}
}
$searchName = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$hasItemSearch = ($searchItemId > 0 || $searchItemName !== '');

$Title = "Quests by zone";
include('../includes/headers.php');

print "<div class='container quests'>";

if (!quest_tables_ready($db)) {
	print "<p>Quest data has not been imported yet.</p></div></div>";
	include('../includes/footers.php');
	exit;
}

print "<form class='quest-search' method='get' action='{$root_url}quests/zones.php'>";
print "<label>Zone <select name='aZone'>";
print "<option value=\"\">All zones</option>";
$query = "SELECT DISTINCT qn.zone_short, $tbzones.long_name
	FROM $tbquestnpc qn
	JOIN $tbzones ON $tbzones.short_name = qn.zone_short
	WHERE 1=1 " . quest_visibility_filter('qn.npc_id') . "
	ORDER BY $tbzones.long_name ASC, qn.zone_short ASC";
$result = mysqli_query($db, $query) or message_die('quests/zones.php', 'MYSQL_QUERY', $query, mysqli_error($db));
while ($row = mysqli_fetch_array($result)) {
	$label = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
	print "<option value=\"" . htmlspecialchars($row['zone_short']) . "\"";
	if ($row['zone_short'] == $aZone) {
		print " selected";
	}
	print ">" . htmlspecialchars($label) . "</option>";
}
print "</select></label>";
print "<label>NPC name <input type='text' name='q' value=\"" . htmlspecialchars($searchName) . "\" placeholder='e.g. Lanhern'></label>";
print "<label>Item <input type='text' name='item_id' value=\"" . htmlspecialchars($searchItemRaw) . "\" placeholder='name or ID'></label>";
print "<input type='submit' value='Search'>";
if ($aZone != '' || $searchName != '' || $hasItemSearch) {
	print "<a class='quest-search-reset' href='{$root_url}quests/zones.php'>Clear</a>";
}
print "</form>";

$where = "1=1";
if ($aZone != "") {
	$where .= " AND qn.zone_short='" . addslashes($aZone) . "'";
}
if ($searchName != "") {
	$where .= " AND qn.npc_name LIKE '%" . addslashes(str_replace(' ', '_', $searchName)) . "%'";
}
if ($searchItemId > 0) {
	$where .= " AND qn.npc_id IN (
		SELECT DISTINCT t.npc_id FROM $tbquesttrade t
		JOIN $tbquesttradeitem ti ON ti.trade_id=t.id
		WHERE ti.item_id=$searchItemId)";
} elseif ($searchItemName !== '') {
	$itemLike = addslashes(str_replace('_', '%', str_replace(' ', '%', $searchItemName)));
	$where .= " AND qn.npc_id IN (
		SELECT DISTINCT t.npc_id FROM $tbquesttrade t
		JOIN $tbquesttradeitem ti ON ti.trade_id=t.id
		JOIN $tbitems it ON it.id=ti.item_id
		WHERE it.Name LIKE '%$itemLike%')";
}

$maxRows = 500;
$query = "SELECT qn.npc_id, qn.npc_name, qn.zone_short, $tbzones.long_name,
	(SELECT COUNT(*) FROM $tbquesttrade t WHERE t.quest_npc_id=qn.id) AS trade_count,
	(SELECT COUNT(*) FROM $tbquestsaybranch s WHERE s.quest_npc_id=qn.id) AS say_count
	FROM $tbquestnpc qn
	JOIN $tbzones ON $tbzones.short_name=qn.zone_short
	WHERE $where
	" . quest_visibility_filter('qn.npc_id') . "
	AND (
		EXISTS (SELECT 1 FROM $tbquesttrade t WHERE t.quest_npc_id=qn.id)
		OR EXISTS (SELECT 1 FROM $tbquestsaybranch s WHERE s.quest_npc_id=qn.id)
		OR EXISTS (SELECT 1 FROM $tbquestfactionhit h WHERE h.quest_npc_id=qn.id)
	)
	ORDER BY $tbzones.long_name ASC, qn.npc_name ASC
	LIMIT " . ($maxRows + 1);
$result = mysqli_query($db, $query) or message_die('quests/zones.php', 'MYSQL_QUERY', $query, mysqli_error($db));

$rows = mysqli_num_rows($result);
$truncated = $rows > $maxRows;
if ($truncated) {
	$rows = $maxRows;
}
if ($rows == 0) {
	print "<p>No quest NPCs found. Import Lua quests or broaden the search.</p>";
} else {
	if ($aZone != "") {
		$zn = GetFieldByQuery("long_name", "SELECT long_name FROM $tbzones WHERE short_name='" . addslashes($aZone) . "'");
		print "<h3>" . htmlspecialchars($zn ? $zn : $aZone) . " ($rows NPCs)</h3>";
	} else {
		print "<h3>Quest NPCs ($rows)</h3>";
	}
	if ($truncated) {
		print "<p>Showing the first $maxRows quest NPCs. Pick a zone or search by name or item to narrow the list.</p>";
	}
	print "<ul class='quest-npc-list'>";
	$shown = 0;
	while ($shown < $rows && ($row = mysqli_fetch_assoc($result))) {
		$shown++;
		$meta = array();
		$trades = (int)$row['trade_count'];
		$says = (int)$row['say_count'];
		if ($trades > 0) {
			$meta[] = $trades . ($trades == 1 ? ' turn-in' : ' turn-ins');
		}
		if ($says > 0) {
			$meta[] = $says . ($says == 1 ? ' dialogue line' : ' dialogue lines');
		}
		print "<li><a href='{$root_url}quests/index.php?npc_id=" . (int)$row['npc_id'] . "&zone=" . urlencode($row['zone_short']) . "'>" .
			htmlspecialchars(quest_npc_display_name($row['npc_name'])) . "</a>";
		if ($meta) {
			print "<span class='quest-npc-meta'>" . implode(' · ', $meta) . "</span>";
		}
		if ($aZone == '') {
			print "<span class='quest-npc-zone'>" . htmlspecialchars($row['long_name'] ? $row['long_name'] : $row['zone_short']) . "</span>";
		}
		print "</li>";
	}
	print "</ul>";
}

print "</div></div>"; // container, #main
include('../includes/footers.php');
?>

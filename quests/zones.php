<?php
include('../includes/constantes.php');
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mysql.php');
include('../includes/quest_helpers.php');

if (empty($AllowQuestsNPC)) {
	$Title = "Quests";
	include('../includes/headers.php');
	print "<p>Quest browsing is disabled. Set \$AllowQuestsNPC=TRUE in config.php and run quests/import_lua_quests.php.</p>";
	include('../includes/footers.php');
	exit;
}

$aZone = isset($_REQUEST['aZone']) ? $_REQUEST['aZone'] : '';
$searchItem = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : 0;
$searchName = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

$Title = "Quests by zone";
include('../includes/headers.php');

if (!quest_tables_ready($db)) {
	print "<p>Quest tables are missing. Run: <code>php quests/import_lua_quests.php --quests &lt;quest_root&gt;</code></p>";
	include('../includes/footers.php');
	exit;
}

print "<center><table border=0>";
print "<form method=GET action='$PHP_SELF'>";
print "<tr><td nowrap><b>Zone:</b></td><td>";
print "<select name=aZone>";
print "<option value=\"\">-- all / search --</option>";
$query = "SELECT DISTINCT qn.zone_short, z.long_name
	FROM $tbquestnpc qn
	LEFT JOIN $tbzones z ON z.short_name = qn.zone_short
	ORDER BY z.long_name ASC, qn.zone_short ASC";
$result = mysqli_query($db, $query) or message_die('quests/zones.php', 'MYSQL_QUERY', $query, mysqli_error($db));
while ($row = mysqli_fetch_array($result)) {
	$label = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
	print "<option value=\"" . htmlspecialchars($row['zone_short']) . "\"";
	if ($row['zone_short'] == $aZone) {
		print " selected";
	}
	print ">" . htmlspecialchars($label) . "</option>";
}
print "</select></td></tr>";
print "<tr><td nowrap><b>Search NPC:</b></td><td><input type=text name=q value=\"" . htmlspecialchars($searchName) . "\" class=form></td></tr>";
print "<tr><td nowrap><b>Item ID:</b></td><td><input type=text name=item_id value=\"" . ($searchItem > 0 ? $searchItem : '') . "\" class=form size=8> (hand-in or reward)</td></tr>";
print "<tr align=center><td colspan=2><input type=submit value=Search name=isearch class=form></td></tr>";
print "</form></table></center>";

print "<p><a href='{$root_url}quests/zones.php'>All quest zones</a></p>";

$where = "1=1";
if ($aZone != "") {
	$where .= " AND qn.zone_short='" . addslashes($aZone) . "'";
}
if ($searchName != "") {
	$where .= " AND qn.npc_name LIKE '%" . addslashes(str_replace(' ', '_', $searchName)) . "%'";
}
if ($searchItem > 0) {
	$where .= " AND qn.npc_id IN (
		SELECT DISTINCT t.npc_id FROM $tbquesttrade t
		JOIN $tbquesttradeitem ti ON ti.trade_id=t.id
		WHERE ti.item_id=$searchItem)";
}

$query = "SELECT qn.npc_id, qn.npc_name, qn.zone_short, z.long_name,
	(SELECT COUNT(*) FROM $tbquesttrade t WHERE t.quest_npc_id=qn.id) AS trade_count,
	(SELECT COUNT(*) FROM $tbquestsaybranch s WHERE s.quest_npc_id=qn.id) AS say_count
	FROM $tbquestnpc qn
	LEFT JOIN $tbzones z ON z.short_name=qn.zone_short
	WHERE $where
	AND (
		EXISTS (SELECT 1 FROM $tbquesttrade t WHERE t.quest_npc_id=qn.id)
		OR EXISTS (SELECT 1 FROM $tbquestsaybranch s WHERE s.quest_npc_id=qn.id)
		OR EXISTS (SELECT 1 FROM $tbquestfactionhit h WHERE h.quest_npc_id=qn.id)
	)
	ORDER BY z.long_name ASC, qn.npc_name ASC
	LIMIT 500";
$result = mysqli_query($db, $query) or message_die('quests/zones.php', 'MYSQL_QUERY', $query, mysqli_error($db));

$rows = mysqli_num_rows($result);
if ($rows == 0) {
	print "<p>No quest NPCs found. Import Lua quests or broaden the search.</p>";
} else {
	if ($aZone != "") {
		$zn = GetFieldByQuery("long_name", "SELECT long_name FROM $tbzones WHERE short_name='" . addslashes($aZone) . "'");
		print "<h3>" . htmlspecialchars($zn ? $zn : $aZone) . " ($rows NPCs)</h3>";
	} else {
		print "<h3>Results ($rows)</h3>";
	}
	print "<table border=0 width=100%>";
	$cpt = 0;
	while ($row = mysqli_fetch_assoc($result)) {
		$cpt++;
		if ($cpt == 1) {
			print "<tr>";
		}
		$npcDisp = str_replace('_', ' ', $row['npc_name']);
		$meta = array();
		if ((int)$row['trade_count'] > 0) {
			$meta[] = (int)$row['trade_count'] . ' turn-in(s)';
		}
		if ((int)$row['say_count'] > 0) {
			$meta[] = (int)$row['say_count'] . ' say';
		}
		$metaStr = $meta ? ' <small>(' . implode(', ', $meta) . ')</small>' : '';
		print "<td width=25%><a href='index.php?npc_id=" . (int)$row['npc_id'] . "&zone=" . urlencode($row['zone_short']) . "'>" .
			htmlspecialchars($npcDisp) . "</a>$metaStr<br><small>" .
			htmlspecialchars($row['long_name'] ? $row['long_name'] : $row['zone_short']) . "</small></td>";
		if ($cpt == 4) {
			print "</tr>";
			$cpt = 0;
		}
	}
	if ($cpt > 0) {
		print "</tr>";
	}
	print "</table>";
}

include('../includes/footers.php');
?>

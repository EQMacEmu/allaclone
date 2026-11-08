<?php
include('../includes/constantes.php');
include('../includes/config.php');
include('../includes/functions.php');
include('../includes/mysql.php');
include('../includes/quest_helpers.php');

if (empty($AllowQuestsNPC)) {
	print "<script>document.location=\"../index.php\";</script>";
	exit;
}

$npc_id = isset($_REQUEST['npc_id']) ? (int)$_REQUEST['npc_id'] : 0;
$zone = isset($_REQUEST['zone']) ? $_REQUEST['zone'] : '';
// Legacy name-based links: ?npc=Behroe_Dlexon&zone=qeynos
$npcName = isset($_REQUEST['npc']) ? $_REQUEST['npc'] : '';

if ($npc_id <= 0 && $npcName != '' && $zone != '') {
	$q = "SELECT npc_id FROM $tbquestnpc WHERE zone_short='" . addslashes($zone) . "'
		AND (npc_name='" . addslashes($npcName) . "' OR npc_name='" . addslashes(str_replace(' ', '_', $npcName)) . "')
		LIMIT 1";
	$npc_id = (int)GetFieldByQuery('npc_id', $q);
}

if ($npc_id <= 0) {
	header('Location: zones.php');
	exit;
}

if (!quest_tables_ready($db)) {
	$Title = "Quest";
	include('../includes/headers.php');
	print "<p>Quest tables missing. Run import_lua_quests.php first.</p>";
	include('../includes/footers.php');
	exit;
}

$whereZone = $zone != '' ? " AND zone_short='" . addslashes($zone) . "'" : '';
$qn = GetRowByQuery("SELECT * FROM $tbquestnpc WHERE npc_id=$npc_id $whereZone ORDER BY id LIMIT 1");
if (!$qn) {
	$qn = GetRowByQuery("SELECT * FROM $tbquestnpc WHERE npc_id=$npc_id ORDER BY id LIMIT 1");
}
if (!$qn) {
	header('Location: zones.php');
	exit;
}
$zone = $qn['zone_short'];
$questNpcId = (int)$qn['id'];
$dispName = str_replace('_', ' ', $qn['npc_name']);
$myzone = GetFieldByQuery('long_name', "SELECT long_name FROM $tbzones WHERE short_name='" . addslashes($zone) . "'");

$Title = "Quest: " . htmlspecialchars($dispName);
include('../includes/headers.php');

print "<p class='quest-breadcrumb'>";
print "<a href='zones.php?aZone=" . urlencode($zone) . "'>" . htmlspecialchars($myzone ? $myzone : $zone) . "</a>";
print " — <a href='{$root_url}npc.php?id=$npc_id'>NPC page</a>";
print "</p>";
print "<p><em>Offline extract from Lua quest scripts (approximate).</em></p>";

if (!empty($qn['script_paths'])) {
	print "<p><strong>Scripts:</strong> " . htmlspecialchars($qn['script_paths']) . "</p>";
}

// Primary / required factions
$pf = (int)$qn['primary_faction_id'];
if ($pf > 0) {
	$fname = GetFieldByQuery('name', "SELECT name FROM $tbfactionlist WHERE id=$pf");
	print "<p><strong>NPC primary faction:</strong> <a href='{$root_url}faction.php?id=$pf'>" . htmlspecialchars($fname ? $fname : "#$pf") . "</a></p>";
}

$reqRes = mysqli_query($db, "SELECT r.*, f.name AS faction_name
	FROM $tbquestfactionreq r
	LEFT JOIN $tbfactionlist f ON f.id=r.faction_id
	WHERE r.quest_npc_id=$questNpcId
	ORDER BY r.min_raw DESC");
if ($reqRes && mysqli_num_rows($reqRes) > 0) {
	print "<h3>Faction requirements (heuristic)</h3><ul>";
	while ($r = mysqli_fetch_assoc($reqRes)) {
		$fn = $r['faction_name'] ? $r['faction_name'] : ('Faction #' . $r['faction_id']);
		$fid = (int)$r['faction_id'];
		$label = $r['standing_label'] ? $r['standing_label'] : ('raw >= ' . (int)$r['min_raw']);
		print "<li>";
		if ($fid > 0) {
			print "<a href='{$root_url}faction.php?id=$fid'>" . htmlspecialchars($fn) . "</a>";
		} else {
			print htmlspecialchars($fn);
		}
		print ": " . htmlspecialchars($label) . " (raw &gt;= " . (int)$r['min_raw'] . ")</li>";
	}
	print "</ul>";
	mysqli_free_result($reqRes);
}

// Faction hits summary
$hitRes = mysqli_query($db, "SELECT h.faction_id, h.delta, f.name AS faction_name, SUM(1) AS cnt
	FROM $tbquestfactionhit h
	LEFT JOIN $tbfactionlist f ON f.id=h.faction_id
	WHERE h.quest_npc_id=$questNpcId
	GROUP BY h.faction_id, h.delta, f.name
	ORDER BY h.delta DESC, f.name");
if ($hitRes && mysqli_num_rows($hitRes) > 0) {
	print "<h3>Faction hits</h3><ul>";
	while ($h = mysqli_fetch_assoc($hitRes)) {
		$fn = $h['faction_name'] ? $h['faction_name'] : ('#' . $h['faction_id']);
		$cls = ((int)$h['delta'] >= 0) ? 'good' : 'bad';
		print "<li class='$cls'><a href='{$root_url}faction.php?id=" . (int)$h['faction_id'] . "'>" . htmlspecialchars($fn) . "</a> (" . (int)$h['delta'] . ")</li>";
	}
	print "</ul>";
	mysqli_free_result($hitRes);
}

// Turn-ins
$tradeRes = mysqli_query($db, "SELECT * FROM $tbquesttrade WHERE quest_npc_id=$questNpcId ORDER BY line_no");
if ($tradeRes && mysqli_num_rows($tradeRes) > 0) {
	print "<h3>Turn-ins</h3>";
	while ($t = mysqli_fetch_assoc($tradeRes)) {
		$tid = (int)$t['id'];
		print "<div style='margin-bottom:1em;padding:0.5em;border:1px solid #ccc'>";
		print "<p><strong>Give:</strong> ";
		$gi = mysqli_query($db, "SELECT ti.item_id, it.Name FROM $tbquesttradeitem ti
			LEFT JOIN $tbitems it ON it.id=ti.item_id
			WHERE ti.trade_id=$tid AND ti.role='give' ORDER BY ti.ord");
		$parts = array();
		while ($gi && ($g = mysqli_fetch_assoc($gi))) {
			$iid = (int)$g['item_id'];
			$iname = $g['Name'] ? $g['Name'] : "Item #$iid";
			$parts[] = "<a href='{$root_url}item.php?id=$iid'>" . htmlspecialchars($iname) . "</a>";
		}
		if ($gi) mysqli_free_result($gi);
		print $parts ? implode(', ', $parts) : '(none resolved)';
		print "</p>";

		print "<p><strong>Reward:</strong> ";
		$ri = mysqli_query($db, "SELECT ti.item_id, it.Name FROM $tbquesttradeitem ti
			LEFT JOIN $tbitems it ON it.id=ti.item_id
			WHERE ti.trade_id=$tid AND ti.role='reward' ORDER BY ti.ord");
		$parts = array();
		while ($ri && ($g = mysqli_fetch_assoc($ri))) {
			$iid = (int)$g['item_id'];
			$iname = $g['Name'] ? $g['Name'] : "Item #$iid";
			$parts[] = "<a href='{$root_url}item.php?id=$iid'>" . htmlspecialchars($iname) . "</a>";
		}
		if ($ri) mysqli_free_result($ri);
		$money = quest_format_money((int)$t['copper'], (int)$t['silver'], (int)$t['gold'], (int)$t['platinum']);
		if ($money) {
			$parts[] = $money;
		}
		if ((int)$t['reward_exp'] > 0) {
			$parts[] = (int)$t['reward_exp'] . ' exp';
		}
		if ((int)$t['reward_faction_flag']) {
			$parts[] = 'NPC faction hit list applied';
		}
		print $parts ? implode(', ', $parts) : '(none)';
		print "</p>";
		print "<p><small>" . htmlspecialchars($t['script_file']) . ":" . (int)$t['line_no'] . "</small></p>";
		print "</div>";
	}
	mysqli_free_result($tradeRes);
}

// Say branches
$sayRes = mysqli_query($db, "SELECT * FROM $tbquestsaybranch WHERE quest_npc_id=$questNpcId ORDER BY line_no LIMIT 200");
if ($sayRes && mysqli_num_rows($sayRes) > 0) {
	print "<h3>Dialogue keywords</h3><ul>";
	while ($s = mysqli_fetch_assoc($sayRes)) {
		print "<li><strong>" . htmlspecialchars($s['keyword']) . "</strong>: " .
			htmlspecialchars($s['response_text']) . "</li>";
	}
	print "</ul>";
	mysqli_free_result($sayRes);
}

include('../includes/footers.php');
?>

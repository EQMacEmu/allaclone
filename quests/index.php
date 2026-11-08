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

if ($npc_id <= 0 || in_array($npc_id, array_map('intval', $hide_npc_id))) {
	header('Location: zones.php');
	exit;
}

if (!quest_tables_ready($db)) {
	$Title = "Quest";
	include('../includes/headers.php');
	print "<div class='container quests'><p>Quest data has not been imported yet.</p></div></div>";
	include('../includes/footers.php');
	exit;
}

$questNpcQuery = "SELECT $tbquestnpc.*, $tbzones.long_name
	FROM $tbquestnpc
	JOIN $tbzones ON $tbzones.short_name = $tbquestnpc.zone_short
	WHERE $tbquestnpc.npc_id=$npc_id " . quest_visibility_filter("$tbquestnpc.npc_id");
$qn = null;
if ($zone != '') {
	$qn = GetRowByQuery($questNpcQuery . " AND $tbquestnpc.zone_short='" . addslashes($zone) . "' ORDER BY $tbquestnpc.id LIMIT 1");
}
if (!$qn) {
	$qn = GetRowByQuery($questNpcQuery . " ORDER BY $tbquestnpc.id LIMIT 1");
}
if (!$qn) {
	header('Location: zones.php');
	exit;
}
$zone = $qn['zone_short'];
$questNpcId = (int)$qn['id'];
$dispName = quest_npc_display_name($qn['npc_name']);
$myzone = $qn['long_name'];

$Title = "Quest: " . htmlspecialchars($dispName);
include('../includes/headers.php');

print "<div class='container quests quest-detail'>";
print "<p class='quest-breadcrumb'>";
print "<a href='{$root_url}quests/zones.php?aZone=" . urlencode($zone) . "'>" . htmlspecialchars($myzone ? $myzone : $zone) . " quests</a>";
print " · <a href='{$root_url}npc.php?id=$npc_id'>NPC page</a>";
print "</p>";
print "<p class='quest-note'>Summarized automatically from the server's quest scripts; details may be incomplete.";
if (!empty($qn['script_paths'])) {
	print " Source: " . htmlspecialchars(str_replace(',', ', ', $qn['script_paths']));
}
print "</p>";

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

// Turn-ins (faction hits are per turn-in via trade_id)
$tradeRes = mysqli_query($db, "SELECT * FROM $tbquesttrade WHERE quest_npc_id=$questNpcId ORDER BY line_no");
if ($tradeRes && mysqli_num_rows($tradeRes) > 0) {
	print "<h3>Turn-ins</h3>";
	while ($t = mysqli_fetch_assoc($tradeRes)) {
		$tid = (int)$t['id'];
		print "<div class='quest-trade'>";
		$give = quest_trade_item_links($db, $tid, 'give');
		print "<p><strong>Give:</strong> " . ($give ? implode(', ', $give) : '(none resolved)') . "</p>";
		$rewards = array_merge(quest_trade_item_links($db, $tid, 'reward'), quest_trade_reward_extras($t));
		print "<p><strong>Reward:</strong> " . ($rewards ? implode(', ', $rewards) : '(none extracted)') . "</p>";
		$tradeHits = mysqli_query($db, "SELECT h.faction_id, h.delta, f.name AS faction_name
			FROM $tbquestfactionhit h
			LEFT JOIN $tbfactionlist f ON f.id=h.faction_id
			WHERE h.trade_id=$tid
			ORDER BY h.delta DESC, f.name");
		if ($tradeHits && mysqli_num_rows($tradeHits) > 0) {
			print "<p><strong>Faction:</strong></p><ul>";
			while ($h = mysqli_fetch_assoc($tradeHits)) {
				$fn = $h['faction_name'] ? $h['faction_name'] : ('#' . $h['faction_id']);
				$cls = ((int)$h['delta'] >= 0) ? 'good' : 'bad';
				print "<li class='$cls'><a href='{$root_url}faction.php?id=" . (int)$h['faction_id'] . "'>" . htmlspecialchars($fn) . "</a> (" . (int)$h['delta'] . ")</li>";
			}
			print "</ul>";
			mysqli_free_result($tradeHits);
		}
		print "<p class='quest-source'>" . htmlspecialchars($t['script_file']) . ", line " . (int)$t['line_no'] . "</p>";
		print "</div>";
	}
	mysqli_free_result($tradeRes);
}

// Scripted faction hits not tied to a recognized turn-in
$otherHits = mysqli_query($db, "SELECT h.faction_id, h.delta, f.name AS faction_name
	FROM $tbquestfactionhit h
	LEFT JOIN $tbfactionlist f ON f.id=h.faction_id
	WHERE h.quest_npc_id=$questNpcId AND h.trade_id=0
	ORDER BY h.delta DESC, f.name");
if ($otherHits && mysqli_num_rows($otherHits) > 0) {
	print "<h3>Other scripted faction hits</h3><ul>";
	while ($h = mysqli_fetch_assoc($otherHits)) {
		$fn = $h['faction_name'] ? $h['faction_name'] : ('#' . $h['faction_id']);
		$cls = ((int)$h['delta'] >= 0) ? 'good' : 'bad';
		print "<li class='$cls'><a href='{$root_url}faction.php?id=" . (int)$h['faction_id'] . "'>" . htmlspecialchars($fn) . "</a> (" . (int)$h['delta'] . ")</li>";
	}
	print "</ul>";
	mysqli_free_result($otherHits);
}

// Kill faction (npc_faction_entries) — separate from quest turn-in rewards
$npcFactionId = (int)GetFieldByQuery('npc_faction_id', "SELECT npc_faction_id FROM $tbnpctypes WHERE id=$npc_id");
if ($npcFactionId > 0) {
	$killLower = mysqli_query($db, "SELECT $tbfactionlist.name, $tbfactionlist.id, $tbnpcfactionentries.value
		FROM $tbfactionlist, $tbnpcfactionentries
		WHERE $tbnpcfactionentries.npc_faction_id=$npcFactionId
		AND $tbnpcfactionentries.faction_id=$tbfactionlist.id
		AND $tbnpcfactionentries.value<0
		GROUP BY $tbfactionlist.id");
	if ($killLower && mysqli_num_rows($killLower) > 0) {
		print "<h3>Killing this NPC lowers factions with</h3><ul>";
		while ($row = mysqli_fetch_assoc($killLower)) {
			if (!empty($ShowNPCFactionHits)) {
				print "<li class='bad'><a href='{$root_url}faction.php?id=" . (int)$row['id'] . "'>" . htmlspecialchars($row['name']) . "</a> (" . (int)$row['value'] . ")</li>";
			} else {
				print "<li class='bad'><a href='{$root_url}faction.php?id=" . (int)$row['id'] . "'>" . htmlspecialchars($row['name']) . "</a></li>";
			}
		}
		print "</ul>";
		mysqli_free_result($killLower);
	}
	$killRaise = mysqli_query($db, "SELECT $tbfactionlist.name, $tbfactionlist.id, $tbnpcfactionentries.value
		FROM $tbfactionlist, $tbnpcfactionentries
		WHERE $tbnpcfactionentries.npc_faction_id=$npcFactionId
		AND $tbnpcfactionentries.faction_id=$tbfactionlist.id
		AND $tbnpcfactionentries.value>0
		GROUP BY $tbfactionlist.id");
	if ($killRaise && mysqli_num_rows($killRaise) > 0) {
		print "<h3>Killing this NPC raises factions with</h3><ul>";
		while ($row = mysqli_fetch_assoc($killRaise)) {
			if (!empty($ShowNPCFactionHits)) {
				print "<li class='good'><a href='{$root_url}faction.php?id=" . (int)$row['id'] . "'>" . htmlspecialchars($row['name']) . "</a> (" . (int)$row['value'] . ")</li>";
			} else {
				print "<li class='good'><a href='{$root_url}faction.php?id=" . (int)$row['id'] . "'>" . htmlspecialchars($row['name']) . "</a></li>";
			}
		}
		print "</ul>";
		mysqli_free_result($killRaise);
	}
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

print "</div></div>"; // container, #main
include('../includes/footers.php');
?>

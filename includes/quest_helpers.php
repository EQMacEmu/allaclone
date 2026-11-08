<?php
/**
 * Shared helpers for DB-backed quest display (tool tables from import_lua_quests.php).
 */

function quest_tables_ready($db) {
	global $tbquestnpc;
	static $ready = null;
	if ($ready !== null) {
		return $ready;
	}
	$res = @mysqli_query($db, "SHOW TABLES LIKE '" . addslashes($tbquestnpc) . "'");
	$ready = ($res && mysqli_num_rows($res) > 0);
	if ($res) {
		mysqli_free_result($res);
	}
	return $ready;
}

/**
 * SQL fragment that hides config-blocked NPCs and quests in zones beyond the selected expansion.
 * The query must join the zone table under its own name ($tbzones, no alias).
 */
function quest_visibility_filter($npcIdColumn) {
	global $hide_npc_id, $tbzones;
	$sql = '';
	if (!empty($hide_npc_id)) {
		$ids = array_map('intval', $hide_npc_id);
		$sql .= " AND $npcIdColumn NOT IN (" . implode(',', $ids) . ")";
	}
	if (function_exists('gatefilter')) {
		$sql .= gatefilter(array($tbzones));
	}
	return $sql;
}

function quest_npc_display_name($dbName) {
	return function_exists('ReadableNpcName') ? ReadableNpcName($dbName) : str_replace('_', ' ', $dbName);
}

function quest_format_money($copper, $silver, $gold, $platinum) {
	$parts = array();
	if ($platinum > 0) $parts[] = $platinum . 'p';
	if ($gold > 0) $parts[] = $gold . 'g';
	if ($silver > 0) $parts[] = $silver . 's';
	if ($copper > 0) $parts[] = $copper . 'c';
	return implode(' ', $parts);
}

function quest_npc_detail_url($npcId, $zoneShort) {
	global $root_url;
	$npcId = (int)$npcId;
	$zoneShort = urlencode($zoneShort);
	return $root_url . "quests/index.php?npc_id=$npcId&zone=$zoneShort";
}

/** Link to the quest page when quest browsing is on, else the NPC page. */
function quest_npc_link($npcId, $zoneShort, $label) {
	global $AllowQuestsNPC, $root_url;
	$npcId = (int)$npcId;
	$url = !empty($AllowQuestsNPC) ? quest_npc_detail_url($npcId, $zoneShort) : $root_url . "npc.php?id=$npcId";
	return "<a href='$url'>" . htmlspecialchars($label) . "</a>";
}

/**
 * Item links for a trade role (give|reward), one entry per distinct item with its count ("4x Ogre Swill").
 * $highlightItemId is shown in bold (the item whose page is being viewed).
 */
function quest_trade_item_links($db, $tradeId, $role, $highlightItemId = 0) {
	global $tbquesttradeitem, $tbitems, $root_url, $hide_item_id;

	$tradeId = (int)$tradeId;
	$highlightItemId = (int)$highlightItemId;
	$role = ($role === 'reward') ? 'reward' : 'give';
	$q = "SELECT ti.item_id, it.Name, COUNT(*) AS qty, MIN(ti.ord) AS first_ord
		FROM $tbquesttradeitem ti
		LEFT JOIN $tbitems it ON it.id = ti.item_id
		WHERE ti.trade_id=$tradeId AND ti.role='$role'";
	if (!empty($hide_item_id)) {
		$q .= " AND ti.item_id NOT IN (" . implode(',', array_map('intval', $hide_item_id)) . ")";
	}
	$q .= " GROUP BY ti.item_id, it.Name ORDER BY first_ord";
	$res = mysqli_query($db, $q);
	$parts = array();
	while ($res && ($row = mysqli_fetch_assoc($res))) {
		$iid = (int)$row['item_id'];
		$iname = $row['Name'] ? $row['Name'] : "Item #$iid";
		$link = "<a href='{$root_url}item.php?id=$iid'>" . htmlspecialchars($iname) . "</a>";
		if ($iid === $highlightItemId) {
			$link = "<strong>$link</strong>";
		}
		$qty = (int)$row['qty'];
		$parts[] = ($qty > 1 ? $qty . 'x ' : '') . $link;
	}
	if ($res) {
		mysqli_free_result($res);
	}
	return $parts;
}

/** Money / exp / faction extras from a quest_trade row. */
function quest_trade_reward_extras($tradeRow) {
	$parts = array();
	$money = quest_format_money(
		(int)$tradeRow['copper'],
		(int)$tradeRow['silver'],
		(int)$tradeRow['gold'],
		(int)$tradeRow['platinum']
	);
	if ($money) {
		$parts[] = $money;
	}
	if ((int)$tradeRow['reward_exp'] > 0) {
		$parts[] = (int)$tradeRow['reward_exp'] . ' exp';
	}
	if ((int)$tradeRow['reward_faction_flag']) {
		$parts[] = 'faction hits';
	}
	return $parts;
}

/** Item-page section: NPCs that reward ($role 'reward') or accept ($role 'give') this item, with each trade. */
function quest_print_item_trades($db, $itemId, $role) {
	global $tbquestitems, $tbquesttrade, $tbquesttradeitem, $tbzones, $root_url, $AllowQuestsNPC;

	$itemId = (int)$itemId;
	$flag = ($role === 'reward') ? 'rewarded' : 'handed';
	$q = "SELECT DISTINCT qi.npc_id, qi.npc, qi.zone, $tbzones.long_name
		FROM $tbquestitems qi
		JOIN $tbzones ON $tbzones.short_name = qi.zone
		WHERE qi.item_id=$itemId AND qi.$flag > 0
		" . quest_visibility_filter('qi.npc_id') . "
		ORDER BY qi.zone, qi.npc";
	$res = mysqli_query($db, $q);
	if (!$res || mysqli_num_rows($res) === 0) {
		if ($res) mysqli_free_result($res);
		return;
	}

	echo "<div class='item-quest-sources'>";
	echo ($role === 'reward') ? "<h3>Quest reward from:</h3><ul>" : "<h3>Handed in for quests:</h3><ul>";
	while ($row = mysqli_fetch_assoc($res)) {
		$npcId = (int)$row['npc_id'];
		$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone'];
		echo "<li>" . quest_npc_link($npcId, $row['zone'], quest_npc_display_name($row['npc']));
		if (!empty($AllowQuestsNPC)) {
			echo " (<a href='{$root_url}npc.php?id=$npcId'>NPC</a>)";
		}
		echo " — <a href='{$root_url}zone.php?name=" . urlencode($row['zone']) . "'>" . htmlspecialchars($zoneLabel) . "</a>";

		$tq = "SELECT t.id, t.reward_exp, t.copper, t.silver, t.gold, t.platinum, t.reward_faction_flag
			FROM $tbquesttrade t
			JOIN $tbquesttradeitem ti ON ti.trade_id = t.id AND ti.role='$role' AND ti.item_id=$itemId
			WHERE t.npc_id=$npcId
			GROUP BY t.id, t.reward_exp, t.copper, t.silver, t.gold, t.platinum, t.reward_faction_flag, t.line_no
			ORDER BY t.line_no, t.id";
		$tr = mysqli_query($db, $tq);
		if ($tr && mysqli_num_rows($tr) > 0) {
			echo "<ul class='item-quest-trades'>";
			while ($trade = mysqli_fetch_assoc($tr)) {
				$tid = (int)$trade['id'];
				$give = quest_trade_item_links($db, $tid, 'give', $itemId);
				$rewards = array_merge(quest_trade_item_links($db, $tid, 'reward', $itemId), quest_trade_reward_extras($trade));
				echo "<li><strong>Give:</strong> " . ($give ? implode(', ', $give) : '(none resolved)');
				echo " · <strong>Reward:</strong> ";
				if ($rewards) {
					echo implode(', ', $rewards);
				} elseif (!empty($AllowQuestsNPC)) {
					echo "not extracted — <a href='" . quest_npc_detail_url($npcId, $row['zone']) . "'>see quest details</a>";
				} else {
					echo "not extracted";
				}
				echo "</li>";
			}
			echo "</ul>";
		}
		if ($tr) {
			mysqli_free_result($tr);
		}
		echo "</li>";
	}
	echo "</ul></div>";
	mysqli_free_result($res);
}

function quest_print_item_sources($db, $itemId) {
	global $AllowQuestsNPC, $ItemQuestSource;

	if (empty($ItemQuestSource) && empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}
	quest_print_item_trades($db, $itemId, 'reward');
	quest_print_item_trades($db, $itemId, 'give');
}

function quest_print_npc_section($db, $npcId) {
	global $AllowQuestsNPC, $tbquestnpc, $tbzones;

	if (empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}
	$npcId = (int)$npcId;
	$q = "SELECT $tbquestnpc.zone_short, $tbzones.long_name
		FROM $tbquestnpc
		JOIN $tbzones ON $tbzones.short_name = $tbquestnpc.zone_short
		WHERE $tbquestnpc.npc_id=$npcId
		" . quest_visibility_filter("$tbquestnpc.npc_id") . "
		ORDER BY $tbquestnpc.zone_short";
	$res = mysqli_query($db, $q);
	if (!$res || mysqli_num_rows($res) === 0) {
		if ($res) mysqli_free_result($res);
		return;
	}

	print "<div class='list-wrapper'><p><strong>Quests:</strong></p><ul>";
	while ($qn = mysqli_fetch_assoc($res)) {
		$zone = htmlspecialchars($qn['long_name'] ? $qn['long_name'] : $qn['zone_short']);
		print "<li><a href='" . quest_npc_detail_url($npcId, $qn['zone_short']) . "'>Quest details ($zone)</a></li>";
	}
	mysqli_free_result($res);
	print "</ul></div>";
}

/** One faction-page list ("Raised by quests" etc.), grouping an NPC's hits into a single entry. */
function quest_print_faction_list($db, $query, $cssClass, $heading, $describe) {
	$res = mysqli_query($db, $query);
	if (!$res || mysqli_num_rows($res) === 0) {
		if ($res) mysqli_free_result($res);
		return;
	}
	echo "<div class=\"$cssClass\"><strong>$heading</strong><ul>";
	while ($row = mysqli_fetch_assoc($res)) {
		$npcName = $row['npc_name'] ? quest_npc_display_name($row['npc_name']) : ('NPC #' . $row['npc_id']);
		$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
		echo "<li>" . quest_npc_link((int)$row['npc_id'], $row['zone_short'], $npcName);
		echo $describe($row) . " — " . htmlspecialchars($zoneLabel) . "</li>";
	}
	echo '</ul></div>';
	mysqli_free_result($res);
}

function quest_print_faction_section($db, $factionId) {
	global $AllowQuestsNPC, $tbquestfactionhit, $tbquestfactionreq, $tbquestnpc, $tbzones;

	if (empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}
	$factionId = (int)$factionId;

	$hitQuery = function ($sign) use ($factionId, $tbquestfactionhit, $tbquestnpc, $tbzones) {
		$cmp = ($sign > 0) ? '> 0' : '< 0';
		return "SELECT h.npc_id, h.zone_short, n.npc_name, $tbzones.long_name,
				GROUP_CONCAT(DISTINCT h.delta ORDER BY ABS(h.delta) DESC SEPARATOR ', ') AS deltas
			FROM $tbquestfactionhit h
			LEFT JOIN $tbquestnpc n ON n.id = h.quest_npc_id
			JOIN $tbzones ON $tbzones.short_name = h.zone_short
			WHERE h.faction_id=$factionId AND h.delta $cmp
			" . quest_visibility_filter('h.npc_id') . "
			GROUP BY h.npc_id, h.zone_short, n.npc_name, $tbzones.long_name
			ORDER BY h.zone_short, n.npc_name";
	};
	$describeHits = function ($row) {
		return " (" . htmlspecialchars($row['deltas']) . ")";
	};
	quest_print_faction_list($db, $hitQuery(1), 'raise', 'Raised by quests', $describeHits);
	quest_print_faction_list($db, $hitQuery(-1), 'lower', 'Lowered by quests', $describeHits);

	$reqQuery = "SELECT r.npc_id, r.zone_short, n.npc_name, $tbzones.long_name,
			MAX(r.min_raw) AS min_raw,
			SUBSTRING_INDEX(GROUP_CONCAT(r.standing_label ORDER BY r.min_raw DESC SEPARATOR '|'), '|', 1) AS standing_label
		FROM $tbquestfactionreq r
		LEFT JOIN $tbquestnpc n ON n.id = r.quest_npc_id
		JOIN $tbzones ON $tbzones.short_name = r.zone_short
		WHERE r.faction_id=$factionId
		" . quest_visibility_filter('r.npc_id') . "
		GROUP BY r.npc_id, r.zone_short, n.npc_name, $tbzones.long_name
		ORDER BY r.zone_short, n.npc_name";
	quest_print_faction_list($db, $reqQuery, 'consider', 'Required by quests', function ($row) {
		$label = $row['standing_label'] ? $row['standing_label'] : ('raw >= ' . (int)$row['min_raw']);
		return " — needs " . htmlspecialchars($label);
	});
}

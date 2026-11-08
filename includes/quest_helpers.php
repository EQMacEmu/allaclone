<?php
/**
 * Shared helpers for DB-backed quest display (tool tables from import_lua_quests.php).
 */

function quest_tables_ready($db) {
	static $ready = null;
	if ($ready !== null) {
		return $ready;
	}
	$res = @mysqli_query($db, "SHOW TABLES LIKE 'quest_npc'");
	$ready = ($res && mysqli_num_rows($res) > 0);
	if ($res) {
		mysqli_free_result($res);
	}
	return $ready;
}

function quest_item_name($db, $itemId) {
	global $tbitems;
	$itemId = (int)$itemId;
	$name = GetFieldByQuery("Name", "SELECT Name FROM $tbitems WHERE id=$itemId LIMIT 1");
	if ($name === '' || $name === null) {
		return "Item #$itemId";
	}
	return $name;
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

function quest_print_item_sources($db, $itemId) {
	global $tbquestitems, $AllowQuestsNPC, $ItemQuestSource, $root_url;

	if (empty($ItemQuestSource) && empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}

	$itemId = (int)$itemId;

	// Rewarded by
	$q = "SELECT DISTINCT qi.npc_id, qi.npc, qi.zone, z.long_name
		FROM $tbquestitems qi
		LEFT JOIN zone z ON z.short_name = qi.zone
		WHERE qi.item_id=$itemId AND qi.rewarded > 0
		ORDER BY qi.zone, qi.npc";
	$res = mysqli_query($db, $q);
	if ($res && mysqli_num_rows($res) > 0) {
		echo "<h3>Quest reward from:</h3><ul>";
		while ($row = mysqli_fetch_assoc($res)) {
			$npcName = str_replace('_', ' ', $row['npc']);
			$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone'];
			$npcId = (int)$row['npc_id'];
			echo "<li>";
			if ($npcId > 0) {
				echo "<a href='" . quest_npc_detail_url($npcId, $row['zone']) . "'>" . htmlspecialchars($npcName) . "</a>";
				echo " (<a href='{$root_url}npc.php?id=$npcId'>NPC</a>)";
			} else {
				echo htmlspecialchars($npcName);
			}
			echo " — <a href='{$root_url}zone.php?name=" . urlencode($row['zone']) . "'>" . htmlspecialchars($zoneLabel) . "</a>";
			echo "</li>";
		}
		echo "</ul>";
		mysqli_free_result($res);
	}

	// Handed in for
	$q = "SELECT DISTINCT qi.npc_id, qi.npc, qi.zone, z.long_name
		FROM $tbquestitems qi
		LEFT JOIN zone z ON z.short_name = qi.zone
		WHERE qi.item_id=$itemId AND qi.handed > 0
		ORDER BY qi.zone, qi.npc";
	$res = mysqli_query($db, $q);
	if ($res && mysqli_num_rows($res) > 0) {
		echo "<h3>Handed in for quests:</h3><ul>";
		while ($row = mysqli_fetch_assoc($res)) {
			$npcName = str_replace('_', ' ', $row['npc']);
			$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone'];
			$npcId = (int)$row['npc_id'];
			echo "<li>";
			if ($npcId > 0) {
				echo "<a href='" . quest_npc_detail_url($npcId, $row['zone']) . "'>" . htmlspecialchars($npcName) . "</a>";
				echo " (<a href='{$root_url}npc.php?id=$npcId'>NPC</a>)";
			} else {
				echo htmlspecialchars($npcName);
			}
			echo " — <a href='{$root_url}zone.php?name=" . urlencode($row['zone']) . "'>" . htmlspecialchars($zoneLabel) . "</a>";
			echo "</li>";

			// Peer give-items on the same trades (how to gather the set)
			if ($npcId > 0) {
				$tq = "SELECT DISTINCT ti2.item_id, it.Name
					FROM quest_trade_item ti
					JOIN quest_trade t ON t.id = ti.trade_id
					JOIN quest_trade_item ti2 ON ti2.trade_id = t.id AND ti2.role='give'
					LEFT JOIN items it ON it.id = ti2.item_id
					WHERE ti.item_id=$itemId AND ti.role='give' AND t.npc_id=$npcId AND ti2.item_id != $itemId
					ORDER BY ti2.ord";
				$tr = mysqli_query($db, $tq);
				if ($tr && mysqli_num_rows($tr) > 0) {
					echo "<ul><li>Also required:";
					$parts = array();
					while ($peer = mysqli_fetch_assoc($tr)) {
						$pid = (int)$peer['item_id'];
						$pname = $peer['Name'] ? $peer['Name'] : "Item #$pid";
						$parts[] = "<a href='{$root_url}item.php?id=$pid'>" . htmlspecialchars($pname) . "</a>";
					}
					echo " " . implode(', ', $parts) . " — see each item for drop/vendor/craft sources</li></ul>";
					mysqli_free_result($tr);
				}
			}
		}
		echo "</ul>";
		mysqli_free_result($res);
	}
}

function quest_print_npc_section($db, $npcId) {
	global $AllowQuestsNPC, $root_url, $tbquestnpc;

	if (empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}
	$npcId = (int)$npcId;
	$q = "SELECT * FROM $tbquestnpc WHERE npc_id=$npcId ORDER BY zone_short";
	$res = mysqli_query($db, $q);
	if (!$res || mysqli_num_rows($res) === 0) {
		if ($res) mysqli_free_result($res);
		return;
	}

	print "<div class='list-wrapper'><p><strong>Quests:</strong></p><ul>";
	while ($qn = mysqli_fetch_assoc($res)) {
		$zone = htmlspecialchars($qn['zone_short']);
		print "<li><a href='" . quest_npc_detail_url($npcId, $qn['zone_short']) . "'>Quest details ($zone)</a></li>";
	}
	mysqli_free_result($res);
	print "</ul></div>";
}

function quest_print_faction_section($db, $factionId) {
	global $AllowQuestsNPC, $root_url, $tbquestfactionhit, $tbquestfactionreq, $tbquestnpc;

	if (empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}
	$factionId = (int)$factionId;

	$q = "SELECT DISTINCT h.npc_id, h.zone_short, h.delta, n.npc_name, z.long_name
		FROM $tbquestfactionhit h
		LEFT JOIN $tbquestnpc n ON n.id = h.quest_npc_id
		LEFT JOIN zone z ON z.short_name = h.zone_short
		WHERE h.faction_id=$factionId AND h.delta > 0
		ORDER BY h.zone_short, n.npc_name";
	$res = mysqli_query($db, $q);
	if ($res && mysqli_num_rows($res) > 0) {
		echo '<div class="raise"><strong>Raised by quests</strong><ul>';
		while ($row = mysqli_fetch_assoc($res)) {
			$npcName = str_replace('_', ' ', $row['npc_name'] ? $row['npc_name'] : ('NPC #' . $row['npc_id']));
			$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
			echo "<li><a href='" . quest_npc_detail_url((int)$row['npc_id'], $row['zone_short']) . "'>" . htmlspecialchars($npcName) . "</a>";
			echo " (" . (int)$row['delta'] . ") — " . htmlspecialchars($zoneLabel) . "</li>";
		}
		echo '</ul></div>';
		mysqli_free_result($res);
	}

	$q = "SELECT DISTINCT h.npc_id, h.zone_short, h.delta, n.npc_name, z.long_name
		FROM $tbquestfactionhit h
		LEFT JOIN $tbquestnpc n ON n.id = h.quest_npc_id
		LEFT JOIN zone z ON z.short_name = h.zone_short
		WHERE h.faction_id=$factionId AND h.delta < 0
		ORDER BY h.zone_short, n.npc_name";
	$res = mysqli_query($db, $q);
	if ($res && mysqli_num_rows($res) > 0) {
		echo '<div class="lower"><strong>Lowered by quests</strong><ul>';
		while ($row = mysqli_fetch_assoc($res)) {
			$npcName = str_replace('_', ' ', $row['npc_name'] ? $row['npc_name'] : ('NPC #' . $row['npc_id']));
			$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
			echo "<li><a href='" . quest_npc_detail_url((int)$row['npc_id'], $row['zone_short']) . "'>" . htmlspecialchars($npcName) . "</a>";
			echo " (" . (int)$row['delta'] . ") — " . htmlspecialchars($zoneLabel) . "</li>";
		}
		echo '</ul></div>';
		mysqli_free_result($res);
	}

	$q = "SELECT DISTINCT r.npc_id, r.zone_short, r.min_raw, r.standing_label, n.npc_name, z.long_name
		FROM $tbquestfactionreq r
		LEFT JOIN $tbquestnpc n ON n.id = r.quest_npc_id
		LEFT JOIN zone z ON z.short_name = r.zone_short
		WHERE r.faction_id=$factionId
		ORDER BY r.zone_short, n.npc_name";
	$res = mysqli_query($db, $q);
	if ($res && mysqli_num_rows($res) > 0) {
		echo '<div class="consider"><strong>Required by quests</strong><ul>';
		while ($row = mysqli_fetch_assoc($res)) {
			$npcName = str_replace('_', ' ', $row['npc_name'] ? $row['npc_name'] : ('NPC #' . $row['npc_id']));
			$zoneLabel = $row['long_name'] ? $row['long_name'] : $row['zone_short'];
			$label = $row['standing_label'] ? $row['standing_label'] : ('raw >= ' . (int)$row['min_raw']);
			echo "<li><a href='" . quest_npc_detail_url((int)$row['npc_id'], $row['zone_short']) . "'>" . htmlspecialchars($npcName) . "</a>";
			echo " — needs " . htmlspecialchars($label) . " — " . htmlspecialchars($zoneLabel) . "</li>";
		}
		echo '</ul></div>';
		mysqli_free_result($res);
	}
}

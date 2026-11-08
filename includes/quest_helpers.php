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

/** HTML item links for trade_id + role (give|reward). Optionally skip one item id. */
function quest_trade_item_links($db, $tradeId, $role, $skipItemId = 0) {
	global $tbquesttradeitem, $tbitems, $root_url;

	$tradeId = (int)$tradeId;
	$skipItemId = (int)$skipItemId;
	$role = ($role === 'reward') ? 'reward' : 'give';
	$q = "SELECT ti.item_id, it.Name FROM $tbquesttradeitem ti
		LEFT JOIN $tbitems it ON it.id = ti.item_id
		WHERE ti.trade_id=$tradeId AND ti.role='$role'";
	if ($skipItemId > 0) {
		$q .= " AND ti.item_id != $skipItemId";
	}
	$q .= " ORDER BY ti.ord";
	$res = mysqli_query($db, $q);
	$parts = array();
	while ($res && ($row = mysqli_fetch_assoc($res))) {
		$iid = (int)$row['item_id'];
		$iname = $row['Name'] ? $row['Name'] : "Item #$iid";
		$parts[] = "<a href='{$root_url}item.php?id=$iid'>" . htmlspecialchars($iname) . "</a>";
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

function quest_print_item_sources($db, $itemId) {
	global $tbquestitems, $tbquesttrade, $tbquesttradeitem, $AllowQuestsNPC, $ItemQuestSource, $root_url;

	if (empty($ItemQuestSource) && empty($AllowQuestsNPC)) {
		return;
	}
	if (!quest_tables_ready($db)) {
		return;
	}

	$itemId = (int)$itemId;

	// Rewarded by — NPC + give set / extras that produce this item
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

			if ($npcId > 0) {
				$tq = "SELECT t.id, t.reward_exp, t.copper, t.silver, t.gold, t.platinum, t.reward_faction_flag
					FROM $tbquesttrade t
					JOIN $tbquesttradeitem ti ON ti.trade_id = t.id AND ti.role='reward' AND ti.item_id=$itemId
					WHERE t.npc_id=$npcId
					ORDER BY t.line_no, t.id";
				$tr = mysqli_query($db, $tq);
				if ($tr && mysqli_num_rows($tr) > 0) {
					echo "<ul>";
					while ($trade = mysqli_fetch_assoc($tr)) {
						$tid = (int)$trade['id'];
						$give = quest_trade_item_links($db, $tid, 'give');
						$extras = quest_trade_reward_extras($trade);
						echo "<li>";
						echo "<strong>Give:</strong> " . ($give ? implode(', ', $give) : '(none resolved)');
						if ($extras) {
							echo " — also " . implode(', ', $extras);
						}
						echo "</li>";
					}
					echo "</ul>";
					mysqli_free_result($tr);
				}
			}
			echo "</li>";
		}
		echo "</ul>";
		mysqli_free_result($res);
	}

	// Handed in for — NPC + peer gives + what you receive
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

			if ($npcId > 0) {
				$tq = "SELECT t.id, t.reward_exp, t.copper, t.silver, t.gold, t.platinum, t.reward_faction_flag
					FROM $tbquesttrade t
					JOIN $tbquesttradeitem ti ON ti.trade_id = t.id AND ti.role='give' AND ti.item_id=$itemId
					WHERE t.npc_id=$npcId
					ORDER BY t.line_no, t.id";
				$tr = mysqli_query($db, $tq);
				if ($tr && mysqli_num_rows($tr) > 0) {
					echo "<ul>";
					while ($trade = mysqli_fetch_assoc($tr)) {
						$tid = (int)$trade['id'];
						$peers = quest_trade_item_links($db, $tid, 'give', $itemId);
						$rewards = quest_trade_item_links($db, $tid, 'reward');
						$extras = quest_trade_reward_extras($trade);
						$rewardParts = array_merge($rewards, $extras);
						echo "<li>";
						if ($peers) {
							echo "<strong>Also required:</strong> " . implode(', ', $peers);
							echo " — see each item for drop/vendor/craft sources";
							if ($rewardParts) {
								echo "<br>";
							}
						}
						if ($rewardParts) {
							echo "<strong>Reward:</strong> " . implode(', ', $rewardParts);
						} elseif (!$peers) {
							echo "<strong>Reward:</strong> (none resolved)";
						}
						echo "</li>";
					}
					echo "</ul>";
					mysqli_free_result($tr);
				}
			}
			echo "</li>";
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

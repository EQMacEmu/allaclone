<?php
/**
 * Link + render helpers for Epic 1.0 guides.
 */

function epic_item_name($db, $itemId) {
	global $tbitems;
	$itemId = (int)$itemId;
	$name = GetFieldByQuery('Name', "SELECT Name FROM $tbitems WHERE id=$itemId LIMIT 1");
	return $name !== '' ? $name : ("Item #$itemId");
}

function epic_npc_name($db, $npcId) {
	global $tbnpctypes;
	$npcId = (int)$npcId;
	$name = GetFieldByQuery('name', "SELECT name FROM $tbnpctypes WHERE id=$npcId LIMIT 1");
	if ($name === '') {
		return "NPC #$npcId";
	}
	return function_exists('ReadableNpcName') ? ReadableNpcName($name) : str_replace('_', ' ', $name);
}

function epic_zone_name($db, $zoneShort) {
	global $tbzones;
	$zoneShort = addslashes($zoneShort);
	$name = GetFieldByQuery('long_name', "SELECT long_name FROM $tbzones WHERE short_name='$zoneShort' LIMIT 1");
	return $name !== '' ? $name : $zoneShort;
}

function epic_faction_name($db, $factionId) {
	global $tbfactionlist;
	$factionId = (int)$factionId;
	$name = GetFieldByQuery('name', "SELECT name FROM $tbfactionlist WHERE id=$factionId LIMIT 1");
	return $name !== '' ? $name : ("Faction #$factionId");
}

function epic_item_link($db, $itemId, $label = null) {
	global $root_url;
	$itemId = (int)$itemId;
	if ($label === null) {
		$label = epic_item_name($db, $itemId);
	}
	return "<a href=\"{$root_url}item.php?id=$itemId\">" . htmlspecialchars($label) . "</a>";
}

/**
 * First spawn2 point for an NPC in a zone, else quest_spawn, else null.
 * Returns ['y'=>float,'x'=>float,'z'=>float,'source'=>spawn2|quest].
 */
function epic_npc_spawn_loc($db, $npcId, $zoneShort = '') {
	static $cache = array();
	$npcId = (int)$npcId;
	$zoneShort = (string)$zoneShort;
	$key = $npcId . '|' . $zoneShort;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	$zoneSql = $zoneShort !== '' ? (" AND s2.zone='" . addslashes($zoneShort) . "'") : '';
	$row = GetRowByQuery(
		"SELECT s2.x, s2.y, s2.z, s2.zone FROM spawnentry se
		JOIN spawn2 s2 ON s2.spawngroupID = se.spawngroupID
		WHERE se.npcID=$npcId AND s2.enabled=1 $zoneSql
		ORDER BY s2.id LIMIT 1"
	);
	if ($row) {
		$cache[$key] = array(
			'y' => (float)$row['y'],
			'x' => (float)$row['x'],
			'z' => (float)$row['z'],
			'zone' => $row['zone'],
			'source' => 'spawn2',
		);
		return $cache[$key];
	}

	if (function_exists('quest_tables_ready') && quest_tables_ready($db)) {
		global $tbquestspawn;
		$qsTable = !empty($tbquestspawn) ? $tbquestspawn : 'quest_spawn';
		$qsZone = $zoneShort !== '' ? (" AND zone_short='" . addslashes($zoneShort) . "'") : '';
		$qsRes = @mysqli_query($db, "SELECT x, y, z, zone_short FROM $qsTable WHERE npc_id=$npcId $qsZone ORDER BY id LIMIT 1");
		$qs = ($qsRes && mysqli_num_rows($qsRes) > 0) ? mysqli_fetch_assoc($qsRes) : null;
		if ($qsRes) {
			mysqli_free_result($qsRes);
		}
		if ($qs) {
			$cache[$key] = array(
				'y' => (float)$qs['y'],
				'x' => (float)$qs['x'],
				'z' => (float)$qs['z'],
				'zone' => $qs['zone_short'],
				'source' => 'quest',
			);
			return $cache[$key];
		}
	}

	$cache[$key] = null;
	return null;
}

/** EQ-style loc string: (+Y, +X). */
function epic_format_loc($loc) {
	if (!$loc) {
		return '';
	}
	return sprintf('(%+.0f, %+.0f)', $loc['y'], $loc['x']);
}

/**
 * True when quest_npc has a row for this NPC (optionally scoped to a zone).
 */
function epic_npc_has_quest($db, $npcId, $zoneShort = '') {
	static $cache = array();
	$npcId = (int)$npcId;
	$zoneShort = (string)$zoneShort;
	$key = $npcId . '|' . $zoneShort;
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}
	if ($npcId <= 0 || !function_exists('quest_tables_ready') || !quest_tables_ready($db)) {
		$cache[$key] = false;
		return false;
	}
	global $tbquestnpc;
	$qsTable = !empty($tbquestnpc) ? $tbquestnpc : 'quest_npc';
	$zoneSql = $zoneShort !== '' ? (" AND zone_short='" . addslashes($zoneShort) . "'") : '';
	$res = @mysqli_query($db, "SELECT id FROM $qsTable WHERE npc_id=$npcId $zoneSql LIMIT 1");
	$has = ($res && mysqli_num_rows($res) > 0);
	if ($res) {
		mysqli_free_result($res);
	}
	$cache[$key] = $has;
	return $has;
}

/**
 * NPC link with optional quest page, spawn loc, and zone.
 * $opts: label, with_loc (default true), with_zone (default true)
 */
function epic_npc_link($db, $npcId, $zoneShort = '', $label = null, $opts = array()) {
	global $root_url, $AllowQuestsNPC;
	$npcId = (int)$npcId;
	$withLoc = !isset($opts['with_loc']) || $opts['with_loc'];
	$withZone = !isset($opts['with_zone']) || $opts['with_zone'];
	if ($label === null) {
		$label = epic_npc_name($db, $npcId);
	}
	$hasQuest = !empty($AllowQuestsNPC) && $zoneShort !== '' && epic_npc_has_quest($db, $npcId, $zoneShort);
	if ($hasQuest) {
		$url = $root_url . 'quests/index.php?npc_id=' . $npcId . '&zone=' . urlencode($zoneShort);
	} else {
		$url = $root_url . 'npc.php?id=' . $npcId;
	}
	$html = "<a href=\"$url\">" . htmlspecialchars($label) . "</a>";
	if ($hasQuest) {
		$html .= " (<a href=\"{$root_url}npc.php?id=$npcId\">NPC</a>)";
	}

	$loc = null;
	if ($withLoc || ($withZone && $zoneShort === '')) {
		$loc = epic_npc_spawn_loc($db, $npcId, $zoneShort);
	}
	if ($withLoc && $loc) {
		$html .= ' ' . htmlspecialchars(epic_format_loc($loc));
	}
	$zoneForLink = $zoneShort !== '' ? $zoneShort : ($loc ? $loc['zone'] : '');
	if ($withZone && $zoneForLink !== '') {
		$html .= ' in ' . epic_zone_link($db, $zoneForLink);
	}
	return $html;
}

function epic_zone_link($db, $zoneShort, $label = null) {
	global $root_url;
	if ($label === null) {
		$label = epic_zone_name($db, $zoneShort);
	}
	return "<a href=\"{$root_url}zone.php?name=" . urlencode($zoneShort) . "\">" . htmlspecialchars($label) . "</a>";
}

function epic_faction_link($db, $factionId, $label = null) {
	global $root_url;
	$factionId = (int)$factionId;
	if ($label === null) {
		$label = epic_faction_name($db, $factionId);
	}
	return "<a href=\"{$root_url}faction.php?id=$factionId\">" . htmlspecialchars($label) . "</a>";
}

/** Resolve a typed ref array into HTML. */
function epic_ref_html($db, $ref) {
	$type = isset($ref['type']) ? $ref['type'] : '';
	switch ($type) {
		case 'item':
			return epic_item_link($db, $ref['id'], isset($ref['label']) ? $ref['label'] : null);
		case 'npc':
			return epic_npc_link(
				$db,
				$ref['id'],
				isset($ref['zone']) ? $ref['zone'] : '',
				isset($ref['label']) ? $ref['label'] : null
			);
		case 'zone':
			return epic_zone_link($db, $ref['id'], isset($ref['label']) ? $ref['label'] : null);
		case 'faction':
			return epic_faction_link($db, $ref['id'], isset($ref['label']) ? $ref['label'] : null);
		case 'text':
			return htmlspecialchars(isset($ref['text']) ? $ref['text'] : '');
		default:
			return '';
	}
}

/**
 * Replace {{item:123}} {{npc:456:zone}} {{zone:short}} {{faction:789}} in prose.
 */
function epic_format_text($db, $text) {
	$text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	$text = preg_replace_callback('/\{\{item:(\d+)(?::([^}]+))?\}\}/', function ($m) use ($db) {
		$label = isset($m[2]) ? html_entity_decode($m[2], ENT_QUOTES, 'UTF-8') : null;
		return epic_item_link($db, (int)$m[1], $label);
	}, $text);
	$text = preg_replace_callback('/\{\{npc:(\d+)(?::([a-z0-9_]+))?(?::([^}]+))?\}\}/', function ($m) use ($db) {
		$zone = isset($m[2]) ? $m[2] : '';
		$label = isset($m[3]) ? html_entity_decode($m[3], ENT_QUOTES, 'UTF-8') : null;
		return epic_npc_link($db, (int)$m[1], $zone, $label);
	}, $text);
	$text = preg_replace_callback('/\{\{zone:([a-z0-9_]+)(?::([^}]+))?\}\}/', function ($m) use ($db) {
		$label = isset($m[2]) ? html_entity_decode($m[2], ENT_QUOTES, 'UTF-8') : null;
		return epic_zone_link($db, $m[1], $label);
	}, $text);
	$text = preg_replace_callback('/\{\{faction:(\d+)(?::([^}]+))?\}\}/', function ($m) use ($db) {
		$label = isset($m[2]) ? html_entity_decode($m[2], ENT_QUOTES, 'UTF-8') : null;
		return epic_faction_link($db, (int)$m[1], $label);
	}, $text);
	return $text;
}

function epic_guide_url($slug) {
	global $root_url;
	return $root_url . 'guides/epics/' . rawurlencode($slug) . '.php';
}

function epic_render_prefarm($db, $items) {
	if (empty($items)) {
		return;
	}
	echo "<div class=\"epic-prefarm\">\n";
	echo "<h3>Prefarmable</h3>\n<ul class=\"epic-checklist\">\n";
	foreach ($items as $row) {
		echo "<li><label><input type=\"checkbox\" disabled> ";
		if (isset($row['item'])) {
			echo epic_item_link($db, $row['item']);
		} elseif (isset($row['html'])) {
			echo epic_format_text($db, $row['html']);
		}
		if (!empty($row['note'])) {
			echo " — <span class=\"epic-note\">" . epic_format_text($db, $row['note']) . "</span>";
		}
		echo "</label></li>\n";
	}
	echo "</ul>\n</div>\n";
}

function epic_render_sections($db, $sections) {
	foreach ($sections as $section) {
		$title = isset($section['title']) ? $section['title'] : '';
		echo "<section class=\"epic-section\">\n";
		if ($title !== '') {
			echo "<h3>" . htmlspecialchars($title) . "</h3>\n";
		}
		if (!empty($section['intro'])) {
			echo "<p>" . epic_format_text($db, $section['intro']) . "</p>\n";
		}
		if (!empty($section['callouts'])) {
			foreach ($section['callouts'] as $call) {
				$kind = isset($call['kind']) ? $call['kind'] : 'note';
				echo "<div class=\"epic-callout epic-callout-$kind\">";
				if (!empty($call['title'])) {
					echo "<strong>" . htmlspecialchars($call['title']) . "</strong> ";
				}
				echo epic_format_text($db, $call['text']);
				echo "</div>\n";
			}
		}
		if (!empty($section['steps'])) {
			echo "<ol class=\"epic-steps\">\n";
			foreach ($section['steps'] as $step) {
				echo "<li>";
				if (!empty($step['title'])) {
					echo "<strong>" . htmlspecialchars($step['title']) . "</strong> ";
				}
				echo epic_format_text($db, isset($step['text']) ? $step['text'] : '');
				if (!empty($step['sub'])) {
					echo "<ul>";
					foreach ($step['sub'] as $sub) {
						echo "<li>" . epic_format_text($db, $sub) . "</li>";
					}
					echo "</ul>";
				}
				echo "</li>\n";
			}
			echo "</ol>\n";
		}
		if (!empty($section['html'])) {
			echo epic_format_text($db, $section['html']);
		}
		echo "</section>\n";
	}
}

function epic_render_guide_page($db, $meta, $guide) {
	global $root_url;
	echo "<div class=\"container epics epic-guide\">\n";
	echo "<p class=\"epic-breadcrumb\"><a href=\"{$root_url}guides/epics/\">Epic 1.0 Guides</a>";
	echo " · " . htmlspecialchars($meta['class']) . "</p>\n";

	echo "<div class=\"epic-header\">\n";
	echo "<p class=\"epic-reward\"><strong>Reward:</strong> ";
	$rewards = isset($meta['item_ids']) ? $meta['item_ids'] : array($meta['item_id']);
	$parts = array();
	foreach ($rewards as $iid) {
		$parts[] = epic_item_link($db, $iid);
	}
	echo implode(' / ', $parts);
	echo "</p>\n";
	if (!empty($meta['blurb'])) {
		echo "<p class=\"epic-blurb\">" . htmlspecialchars($meta['blurb']) . "</p>\n";
	}
	echo "</div>\n";

	if (!empty($guide['toc'])) {
		echo "<div class=\"epic-toc\"><strong>Sections:</strong> <ul>";
		foreach ($guide['toc'] as $t) {
			echo "<li>" . htmlspecialchars($t) . "</li>";
		}
		echo "</ul></div>\n";
	}

	if (!empty($guide['prefarm'])) {
		epic_render_prefarm($db, $guide['prefarm']);
	}
	if (!empty($guide['sections'])) {
		epic_render_sections($db, $guide['sections']);
	}
	if (!empty($guide['verified'])) {
		echo "<p class=\"epic-verified\">Verified against local quest scripts: "
			. htmlspecialchars(implode(', ', $guide['verified'])) . ".</p>\n";
	}
	echo "</div>\n";
}

function epic_render_stub_page($db, $meta) {
	global $root_url;
	echo "<div class=\"container epics epic-stub\">\n";
	echo "<p class=\"epic-breadcrumb\"><a href=\"{$root_url}guides/epics/\">Epic 1.0 Guides</a>";
	echo " · " . htmlspecialchars($meta['class']) . "</p>\n";
	echo "<p><strong>Reward:</strong> ";
	$rewards = isset($meta['item_ids']) ? $meta['item_ids'] : array($meta['item_id']);
	$parts = array();
	foreach ($rewards as $iid) {
		$parts[] = epic_item_link($db, $iid);
	}
	echo implode(' / ', $parts);
	echo "</p>\n";
	if (!empty($meta['blurb'])) {
		echo "<p>" . htmlspecialchars($meta['blurb']) . "</p>\n";
	}
	echo "<p class=\"epic-coming-soon\">Full step-by-step guide coming soon. Use the item and quest pages linked from AllaClone while this class guide is being authored.</p>\n";
	echo "</div>\n";
}

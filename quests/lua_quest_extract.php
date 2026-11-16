<?php
/**
 * Pure Lua quest extraction helpers (no DB writes).
 * Used by import_lua_quests.php and quests/tests.
 *
 * Heuristic limits: regex extraction; misses concatenated strings, variable
 * item ids, multiline turn-in tables, deep nesting, and non-literal bindings.
 */

if (!defined('ALLACLONE_LUA_QUEST_EXTRACT')) {
	define('ALLACLONE_LUA_QUEST_EXTRACT', '1.1.0');
}

function normalize_npc_key($name) {
	$s = strtolower(str_replace('_', ' ', $name));
	$s = preg_replace('/^[#~!]+/', '', $s);
	$s = preg_replace('/\s+/', ' ', trim($s));
	return $s;
}

function standing_label_for_raw($minRaw) {
	// Approximate EQ faction bands (raw character_faction_values). Heuristic.
	if ($minRaw >= 1100) return 'Ally+';
	if ($minRaw >= 750) return 'Warmly+';
	if ($minRaw >= 500) return 'Kindly+';
	if ($minRaw >= 100) return 'Amiably+';
	if ($minRaw >= 0) return 'Indifferent+';
	if ($minRaw >= -100) return 'Apprehensive+';
	if ($minRaw >= -500) return 'Dubious+';
	if ($minRaw >= -750) return 'Threateningly+';
	return 'Ready to attack+';
}

/** Parse give-side currency keys from a check_turn_in {...} body. */
function parse_turn_in_give_currency($body) {
	$out = array('copper' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0);
	foreach (array_keys($out) as $coin) {
		if (preg_match('/\b' . $coin . '\s*=\s*(\d+)/i', $body, $m)) {
			$out[$coin] = (int)$m[1];
		}
	}
	return $out;
}

function trade_fingerprint($trade) {
	$g = isset($trade['give_currency']) ? $trade['give_currency'] : array('copper' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0);
	$r = $trade['reward'];
	$giveIds = $trade['give_ids'];
	sort($giveIds);
	$rewardIds = $r['reward_item_ids'];
	sort($rewardIds);
	return implode('|', array(
		$trade['script_file'],
		(int)$trade['line_no'],
		implode(',', $giveIds),
		(int)$g['copper'] . ',' . (int)$g['silver'] . ',' . (int)$g['gold'] . ',' . (int)$g['platinum'],
		implode(',', $rewardIds),
		(int)$r['reward_exp'],
		(int)$r['copper'] . ',' . (int)$r['silver'] . ',' . (int)$r['gold'] . ',' . (int)$r['platinum'],
	));
}

/**
 * Build local NAME => digits map from a Lua script (first assignment wins).
 */
function extract_lua_local_int_consts($text) {
	$map = array();
	if (preg_match_all('/\blocal\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(\d{3,})\b/', $text, $m, PREG_SET_ORDER)) {
		foreach ($m as $hit) {
			if (!isset($map[$hit[1]])) {
				$map[$hit[1]] = (int)$hit[2];
			}
		}
	}
	return $map;
}

/**
 * Literal eq.spawn2 / unique_spawn (and quest::) calls with numeric x,y,z.
 */
function extract_literal_quest_spawns($text, $rel) {
	$locals = extract_lua_local_int_consts($text);
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	$lines = explode("\n", $text);
	$out = array();
	$seen = array();
	$re = '/\b(?:eq\.|quest::)(spawn2|unique_spawn)\s*\(\s*([A-Za-z_][A-Za-z0-9_]*|\d+)\s*,\s*[^,]+,\s*[^,]+,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)(?:\s*,\s*(-?\d+(?:\.\d+)?))?/i';
	foreach ($lines as $i => $line) {
		if (!preg_match_all($re, $line, $matches, PREG_SET_ORDER)) {
			continue;
		}
		foreach ($matches as $m) {
			$callKind = strtolower($m[1]);
			$first = $m[2];
			if (ctype_digit($first)) {
				$npcId = (int)$first;
			} elseif (isset($locals[$first])) {
				$npcId = $locals[$first];
			} else {
				continue;
			}
			if ($npcId <= 0) {
				continue;
			}
			$x = (float)$m[3];
			$y = (float)$m[4];
			$z = (float)$m[5];
			$heading = isset($m[6]) && $m[6] !== '' ? (float)$m[6] : 0.0;
			$key = $npcId . '|' . $x . '|' . $y . '|' . $z;
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$out[] = array(
				'npc_id' => $npcId,
				'x' => $x,
				'y' => $y,
				'z' => $z,
				'heading' => $heading,
				'script_file' => $rel,
				'line_no' => $i + 1,
				'call_kind' => $callKind,
			);
		}
	}
	return $out;
}

/**
 * NPC ids whose events this script handles via eq.register_npc_event.
 */
function extract_registered_npc_ids($text) {
	$ids = array();
	if (preg_match_all('/\beq\.register_npc_event\s*\([^,]+,\s*[^,]+,\s*(\d+)/i', $text, $m)) {
		foreach ($m[1] as $id) {
			$ids[(int)$id] = true;
		}
	}
	$locals = extract_lua_local_int_consts($text);
	foreach ($locals as $name => $id) {
		if (preg_match('/\beq\.register_npc_event\s*\([^,]+,\s*[^,]+,\s*' . preg_quote($name, '/') . '\b/', $text)) {
			$ids[$id] = true;
		}
	}
	return array_keys($ids);
}

function parse_quest_reward($line) {
	$out = array(
		'reward_item_ids' => array(),
		'reward_exp' => 0,
		'reward_faction_flag' => 0,
		'copper' => 0,
		'silver' => 0,
		'gold' => 0,
		'platinum' => 0,
		'faction_hits' => array(),
	);
	if (!preg_match('/e\.other:QuestReward/i', $line)) {
		return null;
	}
	if (preg_match('/items\s*=\s*\{\s*([\d,\s]+)\}/i', $line, $im)) {
		if (preg_match_all('/\d+/', $im[1], $nums)) {
			foreach ($nums[0] as $n) {
				$out['reward_item_ids'][] = (int)$n;
			}
		}
		if (preg_match('/\bexp\s*=\s*(\d+)/i', $line, $em)) {
			$out['reward_exp'] = (int)$em[1];
		}
		foreach (array('copper', 'silver', 'gold', 'platinum') as $coin) {
			if (preg_match('/\b' . $coin . '\s*=\s*(\d+)/i', $line, $cm)) {
				$out[$coin] = (int)$cm[1];
			}
		}
		if (preg_match('/\bfactionid\s*=\s*(\d+)/i', $line, $fm) && preg_match('/\bfactionmod\s*=\s*(-?\d+)/i', $line, $dm)) {
			$out['faction_hits'][] = array('faction_id' => (int)$fm[1], 'delta' => (int)$dm[1]);
		}
		if (preg_match('/\bfaction\s*=\s*true\b/i', $line)) {
			$out['reward_faction_flag'] = 1;
		}
		return $out;
	}
	if (preg_match('/e\.other:QuestReward\s*\(\s*e\.self\s*,\s*([\d,\s]+)\)/i', $line, $m)) {
		$parts = preg_split('/\s*,\s*/', trim($m[1]));
		$nums = array();
		foreach ($parts as $p) {
			if ($p === '' || !ctype_digit($p)) {
				continue;
			}
			$nums[] = (int)$p;
		}
		if (count($nums) < 5) {
			return null;
		}
		$out['copper'] = $nums[0];
		$out['silver'] = $nums[1];
		$out['gold'] = $nums[2];
		$out['platinum'] = $nums[3];
		if ($nums[4] > 0) {
			$out['reward_item_ids'][] = $nums[4];
		}
		if (isset($nums[5])) {
			$out['reward_exp'] = $nums[5];
		}
		if (isset($nums[6]) && $nums[6]) {
			$out['reward_faction_flag'] = 1;
		}
		return $out;
	}
	return null;
}

/**
 * Last line index still belonging to this turn-in's if/elseif branch.
 */
function turn_in_branch_end($lines, $start) {
	$n = count($lines);
	$indent = strlen($lines[$start]) - strlen(ltrim($lines[$start]));
	$end = $n - 1;
	for ($j = $start + 1; $j < $n; $j++) {
		$line = $lines[$j];
		if (preg_match('/item_lib\.check_turn_in\s*\(/i', $line)) {
			$end = $j - 1;
			break;
		}
		$trimmed = ltrim($line);
		if ($trimmed === '') {
			continue;
		}
		$jIndent = strlen($line) - strlen($trimmed);
		if ($jIndent > $indent) {
			continue;
		}
		if (preg_match('/^(elseif|else|end)\b/i', $trimmed)) {
			$end = $j - 1;
			break;
		}
	}
	return $end;
}

function parse_lua_file($path, $rel) {
	$raw = @file_get_contents($path);
	if ($raw === false) {
		return null;
	}
	return parse_lua_text($raw, $rel);
}

/** Parse Lua source text (for fixtures / tests). */
function parse_lua_text($raw, $rel) {
	$raw = str_replace(array("\r\n", "\r"), "\n", $raw);
	$lines = explode("\n", $raw);

	$result = array(
		'trades' => array(),
		'faction_hits' => array(),
		'faction_reqs' => array(),
		'say_branches' => array(),
		'registered_npc_ids' => extract_registered_npc_ids($raw),
		'unsupported_hints' => array(),
	);

	$n = count($lines);
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
		// Multiline turn-in opening: report as unsupported, do not invent.
		if (preg_match('/item_lib\.check_turn_in\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{\s*$/i', $line)) {
			$result['unsupported_hints'][] = array(
				'kind' => 'multiline_turn_in',
				'line_no' => $i + 1,
				'script_file' => $rel,
			);
			continue;
		}
		if (!preg_match('/item_lib\.check_turn_in\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{([^}]+)\}/i', $line, $m)) {
			continue;
		}
		$giveIds = array();
		if (preg_match_all('/item\s*\d+\s*=\s*(\d+)/i', $m[1], $im)) {
			foreach ($im[1] as $id) {
				$giveIds[] = (int)$id;
			}
		}
		$giveCurrency = parse_turn_in_give_currency($m[1]);
		$hasCurrency = ($giveCurrency['copper'] + $giveCurrency['silver'] + $giveCurrency['gold'] + $giveCurrency['platinum']) > 0;
		if (count($giveIds) === 0 && !$hasCurrency) {
			continue;
		}
		$reward = array(
			'reward_item_ids' => array(),
			'reward_exp' => 0,
			'reward_faction_flag' => 0,
			'copper' => 0,
			'silver' => 0,
			'gold' => 0,
			'platinum' => 0,
			'faction_hits' => array(),
		);
		$lim = turn_in_branch_end($lines, $i);
		for ($j = $i; $j <= $lim; $j++) {
			$qr = parse_quest_reward($lines[$j]);
			if ($qr !== null) {
				$reward = $qr;
				break;
			}
			// Non-literal rewards: do not invent values.
			if (preg_match('/e\.other:QuestReward/i', $lines[$j]) && preg_match('/math\.random/i', $lines[$j])) {
				$result['unsupported_hints'][] = array(
					'kind' => 'nonliteral_reward',
					'line_no' => $j + 1,
					'script_file' => $rel,
				);
			}
		}
		$localHits = array();
		for ($j = $i; $j <= $lim; $j++) {
			if (preg_match_all('/e\.other:Faction\s*\(\s*e\.self\s*,\s*(\d+)\s*,\s*(-?\d+)/i', $lines[$j], $fm, PREG_SET_ORDER)) {
				foreach ($fm as $hit) {
					$localHits[] = array(
						'faction_id' => (int)$hit[1],
						'delta' => (int)$hit[2],
						'line_no' => $j + 1,
						'script_file' => $rel,
					);
				}
			}
		}
		foreach ($reward['faction_hits'] as $fh) {
			$localHits[] = array(
				'faction_id' => $fh['faction_id'],
				'delta' => $fh['delta'],
				'line_no' => $i + 1,
				'script_file' => $rel,
			);
		}
		$result['trades'][] = array(
			'line_no' => $i + 1,
			'script_file' => $rel,
			'give_ids' => $giveIds,
			'give_currency' => $giveCurrency,
			'reward' => $reward,
			'faction_hits' => $localHits,
		);
	}

	for ($i = 0; $i < $n; $i++) {
		if (preg_match_all('/e\.other:Faction\s*\(\s*e\.self\s*,\s*(\d+)\s*,\s*(-?\d+)/i', $lines[$i], $fm, PREG_SET_ORDER)) {
			foreach ($fm as $hit) {
				$result['faction_hits'][] = array(
					'faction_id' => (int)$hit[1],
					'delta' => (int)$hit[2],
					'line_no' => $i + 1,
					'script_file' => $rel,
				);
			}
		}
	}

	$seenReq = array();
	for ($i = 0; $i < $n; $i++) {
		if (preg_match_all('/GetFactionValue\s*\(\s*e\.self\s*\)\s*(>=|>)\s*(-?\d+)/i', $lines[$i], $m, PREG_SET_ORDER)) {
			foreach ($m as $hit) {
				$min = (int)$hit[2];
				if ($hit[1] === '>') {
					$min = $min + 1;
				}
				$key = 'v:' . $min;
				if (isset($seenReq[$key])) {
					continue;
				}
				$seenReq[$key] = true;
				$result['faction_reqs'][] = array(
					'faction_id' => 0,
					'min_raw' => $min,
					'standing_label' => standing_label_for_raw($min),
					'line_no' => $i + 1,
					'script_file' => $rel,
				);
			}
		}
	}

	$inSay = false;
	$sayLocals = extract_lua_local_int_consts($raw);
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
		if (preg_match('/^\s*function\s+event_say\b/i', $line)) {
			$inSay = true;
			continue;
		}
		if ($inSay && preg_match('/^\s*function\s+/i', $line)) {
			$inSay = false;
		}
		if (!$inSay) {
			continue;
		}
		if (!preg_match('/^(\s*)(?:if|elseif)\b.*?:findi\s*\(\s*"([^"]{1,64})"\s*\)/i', $line, $bm)) {
			continue;
		}
		$branchLen = strlen($bm[1]);
		$keyword = $bm[2];
		$respKind = '';
		$respText = '';
		$respLine = 0;
		$lim = min($i + 60, $n - 1);
		for ($j = $i + 1; $j <= $lim; $j++) {
			$jl = $lines[$j];
			if (trim($jl) === '') {
				continue;
			}
			$lj = strlen($jl) - strlen(ltrim($jl));
			$trim = ltrim($jl);
			if ($lj <= $branchLen && preg_match('/^(elseif|else)\b/i', $trim)) {
				break;
			}
			if ($respText === '') {
				if (preg_match('/e\.self:Say\s*\(\s*"([^"]{1,400})"/i', $jl, $sm)) {
					$respKind = 'say';
					$respText = $sm[1];
					$respLine = $j + 1;
				} elseif (preg_match('/e\.self:Emote\s*\(\s*"([^"]{1,400})"/i', $jl, $em)) {
					$respKind = 'emote';
					$respText = $em[1];
					$respLine = $j + 1;
				} elseif (preg_match('/e\.self:Say\s*\(\s*string\.format\s*\(\s*"([^"]{1,400})"/i', $jl, $sm)) {
					$respKind = 'say_fmt';
					$respText = $sm[1];
					$respLine = $j + 1;
				}
			}
			if (preg_match('/\b(?:eq\.|quest::)(unique_spawn|spawn2)\s*\(\s*([A-Za-z_][A-Za-z0-9_]*|\d+)\s*,\s*[^,]+,\s*[^,]+,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/i', $jl, $spm)) {
				$spawnId = ctype_digit($spm[2]) ? (int)$spm[2] : 0;
				if ($spawnId <= 0 && isset($sayLocals[$spm[2]])) {
					$spawnId = $sayLocals[$spm[2]];
				}
				if ($spawnId > 0) {
					$sx = (float)$spm[3];
					$sy = (float)$spm[4];
					$sz = (float)$spm[5];
					$spawnBit = "spawns NPC $spawnId at (" . round($sy) . ', ' . round($sx) . ', ' . round($sz) . ')';
					if ($respText === '') {
						$respKind = strtolower($spm[1]);
						$respText = $spawnBit;
						$respLine = $j + 1;
					} elseif (strpos($respText, "spawns NPC $spawnId") === false) {
						$respText .= '; ' . $spawnBit;
					}
				}
			} elseif (preg_match('/\b(?:eq\.|quest::)(unique_spawn|spawn2)\s*\(\s*(\d+)/i', $jl, $spm)) {
				$spawnId = (int)$spm[2];
				$spawnBit = "spawns NPC $spawnId";
				if ($respText === '') {
					$respKind = strtolower($spm[1]);
					$respText = $spawnBit;
					$respLine = $j + 1;
				} elseif (strpos($respText, $spawnBit) === false) {
					$respText .= '; ' . $spawnBit;
				}
			}
			if (preg_match('/\b(?:eq\.|quest::)depop(?:_with_timer)?\s*\(/i', $jl)) {
				if ($respText === '') {
					$respKind = 'depop';
					$respText = 'despawns';
					$respLine = $j + 1;
				} elseif (stripos($respText, 'despawn') === false) {
					$respText .= '; despawns';
				}
			}
		}
		if ($respText !== '') {
			$result['say_branches'][] = array(
				'keyword' => $keyword,
				'response_kind' => $respKind,
				'response_text' => $respText,
				'line_no' => $respLine ? $respLine : ($i + 1),
				'script_file' => $rel,
			);
		}
	}

	return $result;
}

/** NPC ids belong to a zone by id range or spawn point. */
function npc_in_zone($npcId, $zoneShort, $zoneIds, $spawnZones) {
	$zoneShort = strtolower($zoneShort);
	if (isset($zoneIds[$zoneShort]) && intdiv($npcId, 1000) === $zoneIds[$zoneShort]) {
		return true;
	}
	return isset($spawnZones[$npcId][$zoneShort]);
}

/**
 * Resolve which npc_types rows a script file belongs to.
 * Numeric stems and register_npc_event ids must also pass npc_in_zone.
 */
function resolve_script_npcs($stem, $zoneShort, $registeredIds, $npcMaps, $zoneIds, $spawnZones) {
	$ids = array();
	if (ctype_digit($stem)) {
		$id = (int)$stem;
		if (npc_in_zone($id, $zoneShort, $zoneIds, $spawnZones)) {
			$ids[] = $id;
		}
	} else {
		$candidates = isset($npcMaps['exact'][$stem]) ? $npcMaps['exact'][$stem] : array();
		$inZone = array();
		foreach ($candidates as $id) {
			if (npc_in_zone($id, $zoneShort, $zoneIds, $spawnZones)) {
				$inZone[] = $id;
			}
		}
		if (count($inZone) === 0) {
			$key = normalize_npc_key($stem);
			$candidates = ($key !== '' && isset($npcMaps['norm'][$key])) ? $npcMaps['norm'][$key] : array();
			foreach ($candidates as $id) {
				if (npc_in_zone($id, $zoneShort, $zoneIds, $spawnZones)) {
					$inZone[] = $id;
				}
			}
		}
		$ids = $inZone;
	}
	foreach ($registeredIds as $id) {
		$id = (int)$id;
		if (npc_in_zone($id, $zoneShort, $zoneIds, $spawnZones)) {
			$ids[] = $id;
		}
	}

	$targets = array();
	foreach ($ids as $id) {
		if (isset($npcMaps['by_id'][$id])) {
			$targets[$id] = $npcMaps['by_id'][$id];
		}
	}
	return $targets;
}

function list_zone_dirs($questRoot, $zoneFilter) {
	$exclude = array('global' => true, 'templates' => true, 'lua_modules' => true, 'plugins' => true, '.' => true, '..' => true);
	$dirs = array();
	$dh = opendir($questRoot);
	if (!$dh) {
		return $dirs;
	}
	while (($entry = readdir($dh)) !== false) {
		if (isset($exclude[$entry])) {
			continue;
		}
		$path = $questRoot . '/' . $entry;
		if (!is_dir($path)) {
			continue;
		}
		if ($zoneFilter && !isset($zoneFilter[strtolower($entry)])) {
			continue;
		}
		$dirs[] = array('name' => $entry, 'path' => $path);
	}
	closedir($dh);
	usort($dirs, function ($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	});
	return $dirs;
}

function collect_lua_files($zonePath) {
	$files = array();
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($zonePath, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) {
		if (!$f->isFile()) {
			continue;
		}
		if (strtolower($f->getExtension()) !== 'lua') {
			continue;
		}
		$full = str_replace('\\', '/', $f->getPathname());
		$rel = substr($full, strlen(rtrim(str_replace('\\', '/', $zonePath), '/')) + 1);
		$files[] = array('full' => $full, 'rel' => $rel, 'stem' => pathinfo($f->getFilename(), PATHINFO_FILENAME));
	}
	return $files;
}

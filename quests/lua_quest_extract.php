<?php
/**
 * Pure Lua quest extraction helpers (no DB writes).
 * Used by import_lua_quests.php and quests/tests.
 *
 * Heuristic limits: offline regex / balanced-slice extraction; misses
 * concatenated strings, variable item ids, deep nesting, dynamic spawn /
 * MovePC coords, and non-literal register_npc_event NPC ids (e.g. bare loop vars).
 */

if (!defined('ALLACLONE_LUA_QUEST_EXTRACT')) {
	define('ALLACLONE_LUA_QUEST_EXTRACT', '1.3.0');
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

function empty_quest_reward() {
	return array(
		'reward_item_ids' => array(),
		'reward_exp' => 0,
		'reward_faction_flag' => 0,
		'copper' => 0,
		'silver' => 0,
		'gold' => 0,
		'platinum' => 0,
		'faction_hits' => array(),
	);
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
 * Advance $i past a Lua comment starting at '--', or return false.
 * Handles --[[ ... ]] / --[=[ ... ]=] and line comments.
 */
function lua_skip_comment($text, &$i) {
	$len = strlen($text);
	if ($i + 1 >= $len || $text[$i] !== '-' || $text[$i + 1] !== '-') {
		return false;
	}
	$i += 2;
	if ($i < $len && $text[$i] === '[') {
		$eq = 0;
		$j = $i + 1;
		while ($j < $len && $text[$j] === '=') {
			$eq++;
			$j++;
		}
		if ($j < $len && $text[$j] === '[') {
			$close = ']' . str_repeat('=', $eq) . ']';
			$pos = strpos($text, $close, $j + 1);
			$i = ($pos === false) ? $len : ($pos + strlen($close));
			return true;
		}
	}
	while ($i < $len && $text[$i] !== "\n") {
		$i++;
	}
	return true;
}

/**
 * Find closing delimiter index for '{' or '(' at $openPos.
 * Skips strings and -- comments.
 * Returns -1 if unbalanced.
 */
function lua_find_matching_delim($text, $openPos) {
	$len = strlen($text);
	if ($openPos < 0 || $openPos >= $len) {
		return -1;
	}
	$open = $text[$openPos];
	if ($open === '{') {
		$close = '}';
	} elseif ($open === '(') {
		$close = ')';
	} else {
		return -1;
	}
	$depth = 0;
	$inStr = false;
	$strQ = '';
	for ($i = $openPos; $i < $len; $i++) {
		$ch = $text[$i];
		if ($inStr) {
			if ($ch === '\\' && $i + 1 < $len) {
				$i++;
				continue;
			}
			if ($ch === $strQ) {
				$inStr = false;
			}
			continue;
		}
		if ($ch === '-' && lua_skip_comment($text, $i)) {
			$i--; // for-loop will ++
			continue;
		}
		if ($ch === '"' || $ch === "'") {
			$inStr = true;
			$strQ = $ch;
			continue;
		}
		if ($ch === $open) {
			$depth++;
		} elseif ($ch === $close) {
			$depth--;
			if ($depth === 0) {
				return $i;
			}
		}
	}
	return -1;
}

/**
 * Slice inclusive balanced {...} or (...) starting at $openPos, or null.
 */
function lua_slice_balanced($text, $openPos) {
	$end = lua_find_matching_delim($text, $openPos);
	if ($end < 0) {
		return null;
	}
	return substr($text, $openPos, $end - $openPos + 1);
}

/**
 * Convert a byte offset in $text to 1-based line number.
 */
function lua_offset_to_line($text, $offset) {
	if ($offset <= 0) {
		return 1;
	}
	return substr_count(substr($text, 0, $offset), "\n") + 1;
}

/**
 * Find top-level function Name(...) ... end body text (excluding the end line).
 * Returns array(body_text, start_offset, end_offset) or null.
 */
function extract_lua_function_body($text, $funcName) {
	$re = '/\bfunction\s+' . preg_quote($funcName, '/') . '\s*\(/i';
	if (!preg_match($re, $text, $m, PREG_OFFSET_CAPTURE)) {
		return null;
	}
	$fnStart = $m[0][1];
	$parenOpen = strpos($text, '(', $fnStart);
	if ($parenOpen === false) {
		return null;
	}
	$parenClose = lua_find_matching_delim($text, $parenOpen);
	if ($parenClose < 0) {
		return null;
	}
	$bodyStart = $parenClose + 1;
	$len = strlen($text);
	$depth = 1; // the function itself
	$inStr = false;
	$strQ = '';
	$i = $bodyStart;
	while ($i < $len) {
		$ch = $text[$i];
		if ($inStr) {
			if ($ch === '\\' && $i + 1 < $len) {
				$i += 2;
				continue;
			}
			if ($ch === $strQ) {
				$inStr = false;
			}
			$i++;
			continue;
		}
		if ($ch === '-' && lua_skip_comment($text, $i)) {
			continue;
		}
		if ($ch === '"' || $ch === "'") {
			$inStr = true;
			$strQ = $ch;
			$i++;
			continue;
		}
		// Word-boundary keyword scan
		if (preg_match('/\G\b(function|if|for|while|repeat)\b/i', $text, $km, 0, $i)) {
			$depth++;
			$i += strlen($km[0]);
			continue;
		}
		if (preg_match('/\G\buntil\b/i', $text, $um, 0, $i)) {
			$depth--;
			$i += strlen($um[0]);
			if ($depth === 0) {
				return null;
			}
			continue;
		}
		if (preg_match('/\G\bend\b/i', $text, $em, 0, $i)) {
			$depth--;
			if ($depth === 0) {
				$body = substr($text, $bodyStart, $i - $bodyStart);
				return array($body, $bodyStart, $i);
			}
			$i += strlen($em[0]);
			continue;
		}
		$i++;
	}
	return null;
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
 * Parse eq.register_npc_event bindings.
 * Returns list of {event, npc_id|null, npc_token, callback|null, line_no}.
 */
function extract_npc_event_bindings($text) {
	$locals = extract_lua_local_int_consts($text);
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	$out = array();
	$re = '/\beq\.register_npc_event\s*\(\s*[^,]+,\s*Event\.([A-Za-z_][A-Za-z0-9_]*)\s*,\s*([A-Za-z_][A-Za-z0-9_]*|\d+)\s*(?:,\s*([A-Za-z_][A-Za-z0-9_]*))?\s*\)/i';
	if (!preg_match_all($re, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
		return $out;
	}
	foreach ($matches as $m) {
		$event = strtolower($m[1][0]);
		$token = $m[2][0];
		$npcId = null;
		if (ctype_digit($token)) {
			$npcId = (int)$token;
		} elseif (isset($locals[$token])) {
			$npcId = $locals[$token];
		}
		$callback = isset($m[3]) && $m[3][0] !== '' ? $m[3][0] : null;
		$out[] = array(
			'event' => $event,
			'npc_id' => $npcId,
			'npc_token' => $token,
			'callback' => $callback,
			'line_no' => lua_offset_to_line($text, $m[0][1]),
		);
	}
	return $out;
}

/**
 * NPC ids whose events this script handles via eq.register_npc_event.
 */
function extract_registered_npc_ids($text) {
	$ids = array();
	foreach (extract_npc_event_bindings($text) as $b) {
		if ($b['npc_id'] !== null) {
			$ids[(int)$b['npc_id']] = true;
		}
	}
	return array_keys($ids);
}

/**
 * Parse a QuestReward table body (inside braces, without braces).
 * Sets $hintKind to nonliteral_reward | partial_nonliteral_reward | dynamic_reward when needed.
 * Returns reward array, or null if nothing usable / fully dynamic items.
 */
function parse_quest_reward_table_body($body, &$hintKind = null) {
	$hintKind = null;
	$out = empty_quest_reward();
	$dynamicItem = false;
	$partialCoin = false;

	if (preg_match('/\bitemid\s*=\s*(\d+)\b/i', $body, $im)) {
		$out['reward_item_ids'][] = (int)$im[1];
	} elseif (preg_match('/\bitemid\s*=\s*/i', $body)) {
		$dynamicItem = true;
	}

	if (preg_match('/\bitems\s*=\s*\{/i', $body, $im, PREG_OFFSET_CAPTURE)) {
		$bracePos = $im[0][1] + strlen($im[0][0]) - 1;
		$slice = lua_slice_balanced($body, $bracePos);
		if ($slice !== null) {
			$inner = substr($slice, 1, -1);
			if (preg_match('/[A-Za-z_]|ChooseRandom|math\.random/i', $inner) && !preg_match('/^\s*[\d,\s]*\s*$/', $inner)) {
				$dynamicItem = true;
			} elseif (preg_match_all('/\d+/', $inner, $nums)) {
				foreach ($nums[0] as $n) {
					$out['reward_item_ids'][] = (int)$n;
				}
			}
		}
	}

	if (preg_match('/\bexp\s*=\s*(\d+)/i', $body, $em)) {
		$out['reward_exp'] = (int)$em[1];
	}

	foreach (array('copper', 'silver', 'gold', 'platinum') as $coin) {
		if (preg_match('/\b' . $coin . '\s*=\s*(\d+)\b/i', $body, $cm)) {
			$out[$coin] = (int)$cm[1];
		} elseif (preg_match('/\b' . $coin . '\s*=\s*/i', $body)) {
			$partialCoin = true;
		}
	}

	if (preg_match('/\bfactionid\s*=\s*(\d+)/i', $body, $fm) && preg_match('/\bfactionmod\s*=\s*(-?\d+)/i', $body, $dm)) {
		$out['faction_hits'][] = array('faction_id' => (int)$fm[1], 'delta' => (int)$dm[1]);
	}
	if (preg_match('/\bfaction\s*=\s*true\b/i', $body)) {
		$out['reward_faction_flag'] = 1;
	}

	if ($dynamicItem || preg_match('/eq\.ChooseRandom|math\.random/i', $body) && count($out['reward_item_ids']) === 0 && preg_match('/\bitemid\s*=|\bitems\s*=/i', $body)) {
		$hintKind = preg_match('/eq\.ChooseRandom/i', $body) ? 'dynamic_reward' : 'nonliteral_reward';
		return null;
	}
	if ($partialCoin || (preg_match('/math\.random|eq\.ChooseRandom/i', $body) && count($out['reward_item_ids']) > 0)) {
		// Keep literal items; coins already 0 if non-literal.
		$hintKind = 'partial_nonliteral_reward';
	}

	$hasSomething = count($out['reward_item_ids']) > 0
		|| $out['reward_exp'] > 0
		|| $out['copper'] + $out['silver'] + $out['gold'] + $out['platinum'] > 0
		|| $out['reward_faction_flag']
		|| count($out['faction_hits']) > 0;
	if (!$hasSomething && $hintKind === null) {
		return $out; // empty but valid table (exp-only missed etc.)
	}
	return $out;
}

/**
 * Parse a QuestReward call from arbitrary text (may be multi-line).
 * Returns reward array or null. Sets $hintKind when unsupported/partial.
 */
function parse_quest_reward_text($callText, &$hintKind = null) {
	$hintKind = null;
	if (!preg_match('/e\.other:QuestReward/i', $callText)) {
		return null;
	}
	if (!preg_match('/e\.other:QuestReward\s*\(/i', $callText, $m, PREG_OFFSET_CAPTURE)) {
		return null;
	}
	$openParen = $m[0][1] + strlen($m[0][0]) - 1;
	$callSlice = lua_slice_balanced($callText, $openParen);
	if ($callSlice === null) {
		return null;
	}
	$args = substr($callSlice, 1, -1); // inside parens

	// Table-form: e.self, { ... }
	if (preg_match('/e\.self\s*,\s*\{/i', $args, $tm, PREG_OFFSET_CAPTURE)) {
		$braceRel = $tm[0][1] + strlen($tm[0][0]) - 1;
		$tableSlice = lua_slice_balanced($args, $braceRel);
		if ($tableSlice === null) {
			return null;
		}
		$body = substr($tableSlice, 1, -1);
		return parse_quest_reward_table_body($body, $hintKind);
	}

	// Positional: e.self, digits...
	if (preg_match('/e\.self\s*,\s*(.+)\s*$/is', $args, $pm)) {
		$rest = trim($pm[1]);
		if (preg_match('/math\.random|eq\.ChooseRandom|[A-Za-z_]/i', $rest)) {
			// Try to see if all positional slots are still pure digits except random ones
			$hintKind = preg_match('/eq\.ChooseRandom/i', $rest) ? 'dynamic_reward' : 'nonliteral_reward';
			return null;
		}
		$parts = preg_split('/\s*,\s*/', $rest);
		$nums = array();
		foreach ($parts as $p) {
			$p = trim($p);
			if ($p === '' || !preg_match('/^\d+$/', $p)) {
				continue;
			}
			$nums[] = (int)$p;
		}
		if (count($nums) < 5) {
			return null;
		}
		$out = empty_quest_reward();
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
 * Single-line wrapper (compat). Prefer parse_quest_reward_text for multi-line.
 */
function parse_quest_reward($line) {
	$hint = null;
	return parse_quest_reward_text($line, $hint);
}

/**
 * Find first QuestReward in line range [branchStart, lim]; may span lines.
 */
function find_quest_reward_in_branch($lines, $branchStart, $lim, $rel, &$hints) {
	$n = count($lines);
	for ($j = $branchStart; $j <= $lim && $j < $n; $j++) {
		if (!preg_match('/e\.other:QuestReward/i', $lines[$j])) {
			continue;
		}
		// Build text from $j forward enough to close the call.
		$chunk = '';
		$max = min($n - 1, $j + 40);
		for ($k = $j; $k <= $max; $k++) {
			$chunk .= ($k > $j ? "\n" : '') . $lines[$k];
			$hintKind = null;
			$qr = parse_quest_reward_text($chunk, $hintKind);
			if ($hintKind !== null) {
				$hints[] = array(
					'kind' => $hintKind,
					'line_no' => $j + 1,
					'script_file' => $rel,
				);
			}
			if ($qr !== null) {
				return $qr;
			}
			// If paren balanced already and still null, stop extending.
			if (preg_match('/e\.other:QuestReward\s*\(/i', $chunk, $mm, PREG_OFFSET_CAPTURE)) {
				$op = $mm[0][1] + strlen($mm[0][0]) - 1;
				if (lua_find_matching_delim($chunk, $op) >= 0) {
					return null;
				}
			}
		}
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
		if (preg_match('/item_lib\.(?:check_turn_in|count_handed_item)\s*\(/i', $line)) {
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

/**
 * For count_handed_item assignments, rewards usually sit in a following
 * `if (var > 0) then` block — use that if as the branch start when present.
 */
function count_handed_reward_branch_start($lines, $callLine) {
	$n = count($lines);
	for ($j = $callLine + 1; $j < $n && $j <= $callLine + 8; $j++) {
		$trimmed = ltrim($lines[$j]);
		if ($trimmed === '') {
			continue;
		}
		if (preg_match('/^if\b.*>\s*0/i', $trimmed)) {
			return $j;
		}
		// Next hand-in or another statement: keep call line as start.
		break;
	}
	return $callLine;
}

/**
 * Format a literal MovePC as a stable annotation string.
 * Uses (y, x, z) ordering to match existing spawn annotations.
 */
function format_movepc_note($zoneId, $x, $y, $z) {
	return 'teleports to zone ' . (int)$zoneId . ' at (' . round($y) . ', ' . round($x) . ', ' . round($z) . ')';
}

/**
 * Parse literal MovePC(...) on a line. Returns note string, or null.
 * Sets $hintKind to nonliteral_movepc when MovePC is present but not literal.
 */
function parse_literal_movepc_line($line, $locals = array(), &$hintKind = null) {
	$hintKind = null;
	if (!preg_match('/\bMovePC\s*\(/i', $line)) {
		return null;
	}
	// e.other:MovePC / e.self:MovePC / client:MovePC / ent:CastToClient():MovePC
	$re = '/(?:CastToClient\s*\(\s*\)\s*:)?\s*MovePC\s*\(\s*([A-Za-z_][A-Za-z0-9_]*|\d+)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/i';
	if (!preg_match($re, $line, $m)) {
		$hintKind = 'nonliteral_movepc';
		return null;
	}
	$zoneTok = $m[1];
	if (ctype_digit($zoneTok)) {
		$zoneId = (int)$zoneTok;
	} elseif (isset($locals[$zoneTok])) {
		$zoneId = (int)$locals[$zoneTok];
	} else {
		$hintKind = 'nonliteral_movepc';
		return null;
	}
	if ($zoneId <= 0) {
		$hintKind = 'nonliteral_movepc';
		return null;
	}
	return format_movepc_note($zoneId, (float)$m[2], (float)$m[3], (float)$m[4]);
}

/**
 * Append MovePC annotation onto response text / kind (spawn-style).
 */
function append_movepc_annotation(&$respText, &$respKind, &$respLine, $note, $lineNo) {
	if ($note === null || $note === '') {
		return;
	}
	if ($respText === '') {
		$respKind = 'movepc';
		$respText = $note;
		$respLine = $lineNo;
	} elseif (strpos($respText, $note) === false && strpos($respText, 'teleports to zone') === false) {
		$respText .= '; ' . $note;
	} elseif (strpos($respText, $note) === false) {
		$respText .= '; ' . $note;
	}
}

/**
 * Collect QuestReward + Faction hits in [branchStart, lim] into a trade row.
 */
function append_extracted_trade(&$result, $lines, $rel, $lineNo, $branchStart, $giveIds, $giveCurrency) {
	$reward = empty_quest_reward();
	$lim = turn_in_branch_end($lines, $branchStart);
	$found = find_quest_reward_in_branch($lines, $branchStart, $lim, $rel, $result['unsupported_hints']);
	if ($found !== null) {
		$reward = $found;
	}
	$localHits = array();
	$effectNotes = array();
	$locals = array();
	if (isset($result['_locals']) && is_array($result['_locals'])) {
		$locals = $result['_locals'];
	}
	for ($j = $branchStart; $j <= $lim; $j++) {
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
		$hintKind = null;
		$mv = parse_literal_movepc_line($lines[$j], $locals, $hintKind);
		if ($hintKind !== null) {
			$result['unsupported_hints'][] = array(
				'kind' => $hintKind,
				'line_no' => $j + 1,
				'script_file' => $rel,
			);
		}
		if ($mv !== null && !in_array($mv, $effectNotes, true)) {
			$effectNotes[] = $mv;
		}
	}
	foreach ($reward['faction_hits'] as $fh) {
		$localHits[] = array(
			'faction_id' => $fh['faction_id'],
			'delta' => $fh['delta'],
			'line_no' => $lineNo,
			'script_file' => $rel,
		);
	}
	$result['trades'][] = array(
		'line_no' => $lineNo,
		'script_file' => $rel,
		'give_ids' => $giveIds,
		'give_currency' => $giveCurrency,
		'reward' => $reward,
		'faction_hits' => $localHits,
		'effect_notes' => implode('; ', $effectNotes),
	);
}

/**
 * Scan lines for turn-ins; append trades onto $bucket (uses $bucket['trades'] and hints).
 * $lineOffset is added to reported line numbers (0 when lines are the full file).
 */
function extract_trades_from_lines(&$bucket, $lines, $rel, $lineOffset = 0) {
	$emptyCurrency = array('copper' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0);
	$n = count($lines);
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
		$absLine = $i + 1 + $lineOffset;
		if (preg_match('/item_lib\.(?:check_turn_in|count_handed_item)\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{\s*$/i', $line)) {
			$bucket['unsupported_hints'][] = array(
				'kind' => 'multiline_turn_in',
				'line_no' => $absLine,
				'script_file' => $rel,
			);
			continue;
		}
		if (preg_match('/item_lib\.check_turn_in\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{([^}]+)\}/i', $line, $m)) {
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
			// Temporary result for append_extracted_trade line numbers
			$tmp = array('trades' => array(), 'unsupported_hints' => array());
			append_extracted_trade($tmp, $lines, $rel, $i + 1, $i, $giveIds, $giveCurrency);
			foreach ($tmp['unsupported_hints'] as $h) {
				$h['line_no'] = $h['line_no'] + $lineOffset;
				$bucket['unsupported_hints'][] = $h;
			}
			foreach ($tmp['trades'] as $t) {
				$t['line_no'] = $t['line_no'] + $lineOffset;
				foreach ($t['faction_hits'] as &$fh) {
					$fh['line_no'] = $fh['line_no'] + $lineOffset;
				}
				unset($fh);
				$bucket['trades'][] = $t;
			}
			continue;
		}
		if (!preg_match('/item_lib\.count_handed_item\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{([^}]*)\}\s*(?:,\s*(\d+))?/i', $line, $cm)) {
			continue;
		}
		$ids = array();
		if (preg_match_all('/\d+/', $cm[1], $im)) {
			foreach ($im[0] as $id) {
				$ids[] = (int)$id;
			}
		}
		if (count($ids) === 0) {
			continue;
		}
		$minCount = (isset($cm[2]) && $cm[2] !== '') ? (int)$cm[2] : 1;
		if ($minCount < 1) {
			$minCount = 1;
		}
		$unique = array_values(array_unique($ids));
		$giveIds = array();
		if (count($unique) === 1 && $minCount > 1) {
			for ($k = 0; $k < $minCount; $k++) {
				$giveIds[] = $unique[0];
			}
		} else {
			$giveIds = $unique;
		}
		$branchStart = count_handed_reward_branch_start($lines, $i);
		$tmp = array('trades' => array(), 'unsupported_hints' => array());
		append_extracted_trade($tmp, $lines, $rel, $i + 1, $branchStart, $giveIds, $emptyCurrency);
		foreach ($tmp['unsupported_hints'] as $h) {
			$h['line_no'] = $h['line_no'] + $lineOffset;
			$bucket['unsupported_hints'][] = $h;
		}
		foreach ($tmp['trades'] as $t) {
			$t['line_no'] = $t['line_no'] + $lineOffset;
			foreach ($t['faction_hits'] as &$fh) {
				$fh['line_no'] = $fh['line_no'] + $lineOffset;
			}
			unset($fh);
			$bucket['trades'][] = $t;
		}
	}
}

/**
 * Extract say branches from lines (all lines are in-scope, e.g. a function body).
 */
function extract_say_branches_from_lines($lines, $rel, $sayLocals, $lineOffset = 0, &$hints = null) {
	$branches = array();
	$n = count($lines);
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
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
					$respLine = $j + 1 + $lineOffset;
				} elseif (preg_match('/e\.self:Emote\s*\(\s*"([^"]{1,400})"/i', $jl, $em)) {
					$respKind = 'emote';
					$respText = $em[1];
					$respLine = $j + 1 + $lineOffset;
				} elseif (preg_match('/e\.self:Say\s*\(\s*string\.format\s*\(\s*"([^"]{1,400})"/i', $jl, $sm)) {
					$respKind = 'say_fmt';
					$respText = $sm[1];
					$respLine = $j + 1 + $lineOffset;
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
						$respLine = $j + 1 + $lineOffset;
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
					$respLine = $j + 1 + $lineOffset;
				} elseif (strpos($respText, $spawnBit) === false) {
					$respText .= '; ' . $spawnBit;
				}
			}
			if (preg_match('/\b(?:eq\.|quest::)depop(?:_with_timer)?\s*\(/i', $jl)) {
				if ($respText === '') {
					$respKind = 'depop';
					$respText = 'despawns';
					$respLine = $j + 1 + $lineOffset;
				} elseif (stripos($respText, 'despawn') === false) {
					$respText .= '; despawns';
				}
			}
			$mvHint = null;
			$mv = parse_literal_movepc_line($jl, $sayLocals, $mvHint);
			if ($mvHint !== null && is_array($hints)) {
				$hints[] = array(
					'kind' => $mvHint,
					'line_no' => $j + 1 + $lineOffset,
					'script_file' => $rel,
				);
			}
			append_movepc_annotation($respText, $respKind, $respLine, $mv, $j + 1 + $lineOffset);
		}
		if ($respText !== '') {
			$branches[] = array(
				'keyword' => $keyword,
				'response_kind' => $respKind,
				'response_text' => $respText,
				'line_no' => $respLine ? $respLine : ($i + 1 + $lineOffset),
				'script_file' => $rel,
			);
		}
	}
	return $branches;
}

/**
 * Extract event_enter bodies that MovePC (proximity banish, etc.).
 */
function extract_event_enter_movepc_branches($lines, $rel, $locals, $lineOffset = 0, &$hints = null) {
	$branches = array();
	$n = count($lines);
	$respKind = '';
	$respText = '';
	$respLine = 0;
	$msg = '';
	for ($i = 0; $i < $n; $i++) {
		$jl = $lines[$i];
		if ($msg === '' && preg_match('/(?:e\.other|e\.self):Message\s*\(\s*\d+\s*,\s*"([^"]{1,400})"\s*\)/i', $jl, $mm)) {
			$msg = $mm[1];
		}
		if ($msg === '' && preg_match('/(?:e\.other|e\.self):Say\s*\(\s*"([^"]{1,400})"\s*\)/i', $jl, $sm)) {
			$msg = $sm[1];
		}
		$mvHint = null;
		$mv = parse_literal_movepc_line($jl, $locals, $mvHint);
		if ($mvHint !== null && is_array($hints)) {
			$hints[] = array(
				'kind' => $mvHint,
				'line_no' => $i + 1 + $lineOffset,
				'script_file' => $rel,
			);
		}
		if ($mv !== null) {
			$respKind = 'movepc';
			$respText = ($msg !== '' ? $msg . '; ' : '') . $mv;
			$respLine = $i + 1 + $lineOffset;
			break;
		}
	}
	if ($respText !== '') {
		$branches[] = array(
			'keyword' => 'enter',
			'response_kind' => $respKind,
			'response_text' => $respText,
			'line_no' => $respLine,
			'script_file' => $rel,
		);
	}
	return $branches;
}

function extract_named_event_body_lines($lines, $eventName) {
	$out = array();
	$in = false;
	$start = 0;
	foreach ($lines as $idx => $line) {
		if (preg_match('/^\s*function\s+' . preg_quote($eventName, '/') . '\b/i', $line)) {
			$in = true;
			$start = $idx + 1;
			continue;
		}
		if ($in && preg_match('/^\s*function\s+/i', $line)) {
			break;
		}
		if ($in) {
			$out[] = $line;
		}
	}
	return array($out, $start);
}

/**
 * Extract classic event_say body lines from full file lines.
 */
function extract_event_say_lines($lines) {
	$out = array();
	$inSay = false;
	foreach ($lines as $line) {
		if (preg_match('/^\s*function\s+event_say\b/i', $line)) {
			$inSay = true;
			continue;
		}
		if ($inSay && preg_match('/^\s*function\s+/i', $line)) {
			$inSay = false;
		}
		if ($inSay) {
			$out[] = $line;
		}
	}
	return $out;
}

/**
 * Extract classic event_trade body lines; empty if no event_trade function.
 */
function extract_event_trade_lines($lines) {
	$out = array();
	$inTrade = false;
	$found = false;
	foreach ($lines as $line) {
		if (preg_match('/^\s*function\s+event_trade\b/i', $line)) {
			$inTrade = true;
			$found = true;
			continue;
		}
		if ($inTrade && preg_match('/^\s*function\s+/i', $line)) {
			$inTrade = false;
		}
		if ($inTrade) {
			$out[] = $line;
		}
	}
	return $found ? $out : null;
}

function ensure_npc_content_bag(&$npcContent, $npcId) {
	if (!isset($npcContent[$npcId])) {
		$npcContent[$npcId] = array(
			'trades' => array(),
			'say_branches' => array(),
		);
	}
}

function parse_lua_file($path, $rel) {
	$raw = @file_get_contents($path);
	if ($raw === false) {
		return null;
	}
	return parse_lua_text($raw, $rel);
}

/** Optional raw encounter overlays (Sirran death/timer/combat notes). */
function apply_manual_quest_overlays(&$parsed, $rel) {
	static $loaded = false;
	if (!$loaded) {
		$manual = dirname(__FILE__) . '/manual_sirran.php';
		if (is_file($manual)) {
			require_once $manual;
		}
		$loaded = true;
	}
	if (function_exists('apply_sirran_airplane_manual_overlay')) {
		apply_sirran_airplane_manual_overlay($parsed, $rel);
	}
}

/** Parse Lua source text (for fixtures / tests). */
function parse_lua_text($raw, $rel) {
	$raw = str_replace(array("\r\n", "\r"), "\n", $raw);
	$lines = explode("\n", $raw);
	$bindings = extract_npc_event_bindings($raw);
	$registered = array();
	foreach ($bindings as $b) {
		if ($b['npc_id'] !== null) {
			$registered[(int)$b['npc_id']] = true;
		}
	}

	$result = array(
		'trades' => array(),
		'faction_hits' => array(),
		'faction_reqs' => array(),
		'say_branches' => array(),
		'registered_npc_ids' => array_keys($registered),
		'event_bindings' => $bindings,
		'npc_content' => array(),
		'has_named_say_trade' => false,
		'unsupported_hints' => array(),
	);

	$sayLocals = extract_lua_local_int_consts($raw);
	$result['_locals'] = $sayLocals;

	// Named Event.say / Event.trade callbacks → per-NPC content
	$callbackCache = array(); // name => parsed {trades, says, hints} or false
	$namedSayTrade = false;
	foreach ($bindings as $b) {
		$ev = $b['event'];
		if ($ev !== 'say' && $ev !== 'trade') {
			continue;
		}
		if ($b['npc_id'] === null) {
			$result['unsupported_hints'][] = array(
				'kind' => 'unresolved_registration',
				'line_no' => $b['line_no'],
				'script_file' => $rel,
				'detail' => $b['npc_token'],
			);
			continue;
		}
		if ($b['callback'] === null || $b['callback'] === '') {
			// 3-arg registration: no named callback; classic path may still apply.
			continue;
		}
		$namedSayTrade = true;
		$cb = $b['callback'];
		if (!array_key_exists($cb, $callbackCache)) {
			$bodyInfo = extract_lua_function_body($raw, $cb);
			if ($bodyInfo === null) {
				$callbackCache[$cb] = false;
			} else {
				$bodyLines = explode("\n", $bodyInfo[0]);
				$lineOffset = lua_offset_to_line($raw, $bodyInfo[1]) - 1;
				$bag = array(
					'trades' => array(),
					'say_branches' => array(),
					'unsupported_hints' => array(),
					'_locals' => $sayLocals,
				);
				// Always allow trade scan in body; say scan too — content is event-typed by binding.
				extract_trades_from_lines($bag, $bodyLines, $rel, $lineOffset);
				$bag['say_branches'] = extract_say_branches_from_lines($bodyLines, $rel, $sayLocals, $lineOffset, $bag['unsupported_hints']);
				$callbackCache[$cb] = $bag;
			}
		}
		if ($callbackCache[$cb] === false) {
			$result['unsupported_hints'][] = array(
				'kind' => 'unresolved_callback',
				'line_no' => $b['line_no'],
				'script_file' => $rel,
				'detail' => $cb,
			);
			continue;
		}
		$bag = $callbackCache[$cb];
		foreach ($bag['unsupported_hints'] as $h) {
			$result['unsupported_hints'][] = $h;
		}
		$npcId = (int)$b['npc_id'];
		ensure_npc_content_bag($result['npc_content'], $npcId);
		if ($ev === 'trade') {
			foreach ($bag['trades'] as $t) {
				$result['npc_content'][$npcId]['trades'][] = $t;
			}
		}
		if ($ev === 'say') {
			foreach ($bag['say_branches'] as $s) {
				$result['npc_content'][$npcId]['say_branches'][] = $s;
			}
		}
	}
	$result['has_named_say_trade'] = $namedSayTrade;

	// Classic path: event_say + event_trade (or file-wide trades when no named trade handlers)
	$inSay = false;
	$n = count($lines);
	$classicSayLines = array();
	$classicSayStart = 0;
	for ($i = 0; $i < $n; $i++) {
		if (preg_match('/^\s*function\s+event_say\b/i', $lines[$i])) {
			$inSay = true;
			$classicSayStart = $i + 1;
			continue;
		}
		if ($inSay && preg_match('/^\s*function\s+/i', $lines[$i])) {
			$inSay = false;
		}
		if ($inSay) {
			$classicSayLines[] = $lines[$i];
		}
	}
	if (count($classicSayLines) > 0) {
		$result['say_branches'] = extract_say_branches_from_lines($classicSayLines, $rel, $sayLocals, $classicSayStart, $result['unsupported_hints']);
	}

	list($enterLines, $enterStart) = extract_named_event_body_lines($lines, 'event_enter');
	if (count($enterLines) > 0) {
		$enterBranches = extract_event_enter_movepc_branches($enterLines, $rel, $sayLocals, $enterStart, $result['unsupported_hints']);
		foreach ($enterBranches as $eb) {
			$result['say_branches'][] = $eb;
		}
	}

	$tradeLines = extract_event_trade_lines($lines);
	$hasNamedTrade = false;
	foreach ($bindings as $b) {
		if ($b['event'] === 'trade' && $b['callback']) {
			$hasNamedTrade = true;
			break;
		}
	}
	if ($hasNamedTrade) {
		// Classic file-wide trades skipped to avoid broadcasting into encounter NPCs.
		// Still extract classic event_trade if present (rare in encounter files).
		if ($tradeLines !== null) {
			$tradeStart = 0;
			for ($i = 0; $i < $n; $i++) {
				if (preg_match('/^\s*function\s+event_trade\b/i', $lines[$i])) {
					$tradeStart = $i + 1;
					break;
				}
			}
			$bag = array('trades' => array(), 'unsupported_hints' => &$result['unsupported_hints'], '_locals' => $sayLocals);
			extract_trades_from_lines($bag, $tradeLines, $rel, $tradeStart);
			$result['trades'] = $bag['trades'];
		}
	} elseif ($tradeLines !== null) {
		$tradeStart = 0;
		for ($i = 0; $i < $n; $i++) {
			if (preg_match('/^\s*function\s+event_trade\b/i', $lines[$i])) {
				$tradeStart = $i + 1;
				break;
			}
		}
		$bag = array('trades' => array(), 'unsupported_hints' => &$result['unsupported_hints'], '_locals' => $sayLocals);
		extract_trades_from_lines($bag, $tradeLines, $rel, $tradeStart);
		$result['trades'] = $bag['trades'];
	} else {
		// No event_trade function: file-wide turn-in scan (legacy behavior for unusual scripts)
		$bag = array('trades' => array(), 'unsupported_hints' => &$result['unsupported_hints'], '_locals' => $sayLocals);
		extract_trades_from_lines($bag, $lines, $rel, 0);
		$result['trades'] = $bag['trades'];
	}

	// Faction hits / reqs (file-wide, classic attribution)
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

	apply_manual_quest_overlays($result, $rel);
	unset($result['_locals']);

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

/**
 * Resolve stem targets only (no register_npc_event ids).
 */
function resolve_stem_npcs($stem, $zoneShort, $npcMaps, $zoneIds, $spawnZones) {
	return resolve_script_npcs($stem, $zoneShort, array(), $npcMaps, $zoneIds, $spawnZones);
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

/**
 * Aggregate unsupported_hints by kind (and optional per-script counts).
 */
function aggregate_unsupported_hints($hints) {
	$byKind = array();
	$byScript = array();
	foreach ($hints as $h) {
		$kind = isset($h['kind']) ? $h['kind'] : 'unknown';
		if (!isset($byKind[$kind])) {
			$byKind[$kind] = 0;
		}
		$byKind[$kind]++;
		$sf = isset($h['script_file']) ? $h['script_file'] : '';
		if ($sf !== '') {
			if (!isset($byScript[$sf])) {
				$byScript[$sf] = 0;
			}
			$byScript[$sf]++;
		}
	}
	arsort($byScript);
	return array('by_kind' => $byKind, 'by_script' => $byScript, 'total' => count($hints));
}

<?php
/**
 * Deterministic Lua extraction fixture tests (no DB writes).
 * Usage: php quests/tests/run_extraction_tests.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

require_once dirname(__DIR__) . '/lua_quest_extract.php';

$fixtures = dirname(__FILE__) . '/fixtures';
$fail = 0;
$pass = 0;

function assert_true($cond, $msg) {
	global $fail, $pass;
	if ($cond) {
		fwrite(STDOUT, "PASS: $msg\n");
		$pass++;
	} else {
		fwrite(STDERR, "FAIL: $msg\n");
		$fail++;
	}
}

assert_true(ALLACLONE_LUA_QUEST_EXTRACT === '1.4.1', 'extractor version 1.4.1');

// Comment filtering must retain string contents and source locations.
$commentRaw = file_get_contents($fixtures . '/comments.lua');
$commented = parse_lua_text($commentRaw, 'comments.lua');
assert_true(count($commented['trades']) === 1 && $commented['trades'][0]['give_ids'] === array(1001), 'line and long comments do not create trades');
assert_true($commented['trades'][0]['reward']['reward_item_ids'] === array(2001), 'commented reward does not replace live reward');
assert_true($commented['trades'][0]['line_no'] === 24, 'trade retains original line number after comments');
assert_true($commented['trades'][0]['give_currency']['platinum'] === 50, 'live currency requirement survives comment filtering');
assert_true(count($commented['faction_hits']) === 0, 'commented faction hit is ignored');
assert_true(count($commented['say_branches']) === 1 && $commented['say_branches'][0]['response_text'] === 'Hello -- this is dialogue, not a comment.', 'dialogue keeps literal comment marker and ignores disabled reply');
assert_true($commented['say_branches'][0]['line_no'] === 34, 'dialogue retains original line number');
assert_true(extract_lua_local_int_consts($commentRaw) === array('TARGET' => 12003), 'commented constant cannot shadow a live constant');
$commentBindings = extract_npc_event_bindings($commentRaw);
assert_true(count($commentBindings) === 1 && $commentBindings[0]['npc_id'] === 12001 && $commentBindings[0]['line_no'] === 45, 'direct binding scan ignores comments and preserves location');
$commentSpawns = extract_literal_quest_spawns($commentRaw, 'comments.lua');
assert_true(count($commentSpawns) === 1 && $commentSpawns[0]['npc_id'] === 12003 && $commentSpawns[0]['line_no'] === 41, 'direct raw spawn scan ignores comments and preserves location');
$commentDeaths = extract_death_spawn_edges($commentRaw, 'comments.lua');
assert_true(count($commentDeaths) === 2 && array_column($commentDeaths, 'spawned_npc_id') === array(12003, 12003) && array_column($commentDeaths, 'line_no') === array(41, 41), 'classic and registered death scans ignore comments and preserve location');
assert_true(extract_lua_function_body($commentRaw, 'discarded_death') === null, 'commented function declaration cannot create a callback body');

$stringLiterals = <<<'LUA'
local single = 'it\'s -- still text';
local double = "an escaped \" quote -- still text";
local plain = [[-- long string
--[[ still a string ]=] ] ==]];
local equal = [==[-- another long string
--[=[ ]] ]=] still a string]==];
LUA;
assert_true(lua_mask_comments($stringLiterals) === $stringLiterals, 'quoted/escaped and long-bracket strings remain byte-identical');
$masked = lua_mask_comments($commentRaw);
assert_true(strlen($masked) === strlen($commentRaw) && substr_count($masked, "\n") === substr_count($commentRaw, "\n"), 'comment masking preserves source byte length and newlines');
foreach (array("\r\n", "\r") as $newline) {
	$withNewlines = str_replace("\n", $newline, $commentRaw);
	assert_true(parse_lua_text($withNewlines, 'comments.lua') === $commented && extract_literal_quest_spawns($withNewlines, 'comments.lua') === $commentSpawns, 'comment filtering handles ' . ($newline === "\r" ? 'CR' : 'CRLF') . ' line endings');
}
$onlyComments = "-- if item_lib.check_turn_in(e.self, e.trade, {item1=9001}) then\n-- eq.spawn2(99999, 0, 0, 1, 2, 3, 0);";
assert_true(count(parse_lua_text($onlyComments, 'comments-only.lua')['trades']) === 0 && extract_literal_quest_spawns($onlyComments, 'comments-only.lua') === array(), 'comment-only file has no fallback trades or spawns, including EOF comment');
assert_true(trim(lua_mask_comments('--[=[ unfinished comment')) === '', 'unterminated long comment remains a comment through EOF');

$multi = parse_lua_file($fixtures . '/multi_turnin.lua', 'multi_turnin.lua');
assert_true(count($multi['trades']) === 2, 'multi_turnin extracts 2 trades');
assert_true($multi['trades'][0]['give_ids'] === array(1001, 1002), 'multi_turnin first give ids');
assert_true($multi['trades'][0]['reward']['reward_item_ids'] === array(2001), 'multi_turnin positional reward item');
assert_true($multi['trades'][0]['reward']['platinum'] === 1, 'multi_turnin reward platinum');
assert_true(count($multi['trades'][0]['faction_hits']) === 1 && $multi['trades'][0]['faction_hits'][0]['faction_id'] === 301, 'trade1 faction attributed');
assert_true(count($multi['trades'][1]['faction_hits']) === 1 && $multi['trades'][1]['faction_hits'][0]['faction_id'] === 302, 'trade2 faction not mixed into trade1');
assert_true($multi['trades'][1]['reward']['reward_item_ids'] === array(2002), 'table-form QuestReward items');

$cur = parse_lua_file($fixtures . '/currency_give.lua', 'currency_give.lua');
assert_true(count($cur['trades']) === 2, 'currency_give extracts platinum-only and item+plat');
assert_true($cur['trades'][0]['give_ids'] === array() && $cur['trades'][0]['give_currency']['platinum'] === 50, 'platinum-only give');
assert_true($cur['trades'][1]['give_ids'] === array(13703) && $cur['trades'][1]['give_currency']['platinum'] === 1, 'item+platinum give');

$fb = parse_lua_file($fixtures . '/faction_branches.lua', 'faction_branches.lua');
assert_true(count($fb['trades'][0]['faction_hits']) === 2, 'first branch has two faction hits');
assert_true(count($fb['trades'][1]['faction_hits']) === 1 && $fb['trades'][1]['faction_hits'][0]['faction_id'] === 403, 'second branch faction isolated');
$challenge = null;
foreach ($fb['say_branches'] as $b) {
	if ($b['keyword'] === 'challenge') {
		$challenge = $b;
	}
}
assert_true($challenge !== null && strpos($challenge['response_text'], 'spawns NPC 27119') !== false, 'spawn-only say branch');

$reg = parse_lua_file($fixtures . '/register_and_spawn.lua', 'register_and_spawn.lua');
sort($reg['registered_npc_ids']);
assert_true($reg['registered_npc_ids'] === array(12001, 12002), 'register_npc_event const + numeric');
$spawns = extract_literal_quest_spawns(file_get_contents($fixtures . '/register_and_spawn.lua'), 'register_and_spawn.lua');
assert_true(count($spawns) === 1 && $spawns[0]['npc_id'] === 12003 && $spawns[0]['x'] == 11.0, 'literal spawn2 coords');

$chi = parse_lua_file($fixtures . '/count_handed_item.lua', 'count_handed_item.lua');
assert_true(count($chi['trades']) === 2, 'count_handed_item extracts 2 trades');
assert_true($chi['trades'][0]['give_ids'] === array(13916), 'count_handed single give id');
assert_true($chi['trades'][0]['reward']['reward_item_ids'] === array(13024), 'count_handed reward item');
assert_true($chi['trades'][0]['reward']['gold'] === 9 && $chi['trades'][0]['reward']['reward_exp'] === 100, 'count_handed reward gold+exp');
assert_true(count($chi['trades'][0]['faction_hits']) === 2, 'count_handed factions attributed to trade');
assert_true($chi['trades'][0]['faction_hits'][0]['faction_id'] === 263 && $chi['trades'][0]['faction_hits'][0]['delta'] === 5, 'count_handed first faction hit');
assert_true($chi['trades'][1]['give_ids'] === array(13319, 13319), 'count_handed min_count=2 emits qty');
assert_true($chi['trades'][1]['reward']['reward_item_ids'] === array(2001), 'count_handed min_count reward');

$un = parse_lua_file($fixtures . '/unsupported_multiline.lua', 'unsupported_multiline.lua');
$kinds = array();
foreach ($un['unsupported_hints'] as $h) {
	$kinds[$h['kind']] = true;
}
assert_true(isset($kinds['multiline_turn_in']), 'reports multiline turn-in unsupported');
assert_true(count($un['trades']) === 0, 'does not invent trades from multiline or variable ids');

// itemid table rewards
$ri = parse_lua_file($fixtures . '/reward_itemid.lua', 'reward_itemid.lua');
assert_true(count($ri['trades']) === 3, 'reward_itemid extracts 3 trades');
assert_true($ri['trades'][0]['reward']['reward_item_ids'] === array(2001) && $ri['trades'][0]['reward']['reward_exp'] === 50, 'itemid= reward');
assert_true($ri['trades'][1]['reward']['reward_item_ids'] === array(2002, 2003), 'items={} still works');
assert_true($ri['trades'][2]['reward']['reward_item_ids'] === array(2004) && $ri['trades'][2]['reward']['platinum'] === 1, 'positional still works');

$rm = parse_lua_file($fixtures . '/reward_multiline_itemid.lua', 'reward_multiline_itemid.lua');
assert_true(count($rm['trades']) === 1, 'multiline QuestReward one trade');
assert_true($rm['trades'][0]['reward']['reward_item_ids'] === array(3002), 'multiline itemid');
assert_true($rm['trades'][0]['reward']['reward_exp'] === 75 && $rm['trades'][0]['reward']['platinum'] === 1, 'multiline exp+plat');

$rd = parse_lua_file($fixtures . '/reward_dynamic.lua', 'reward_dynamic.lua');
assert_true(count($rd['trades']) === 3, 'dynamic fixture still records turn-ins');
assert_true($rd['trades'][0]['reward']['reward_item_ids'] === array(), 'ChooseRandom itemid not guaranteed');
assert_true($rd['trades'][1]['reward']['reward_item_ids'] === array(5003), 'literal itemid kept with random copper');
assert_true($rd['trades'][1]['reward']['copper'] === 0, 'nonliteral copper not invented');
assert_true($rd['trades'][2]['reward']['reward_item_ids'] === array(), 'positional ChooseRandom not guaranteed');
$dk = array();
foreach ($rd['unsupported_hints'] as $h) {
	$dk[$h['kind']] = true;
}
assert_true(isset($dk['dynamic_reward']) || isset($dk['nonliteral_reward']), 'dynamic reward hinted');
assert_true(isset($dk['partial_nonliteral_reward']), 'partial nonliteral reward hinted');

// MovePC (say, enter, trade) + nonliteral hint
$mv = parse_lua_file($fixtures . '/reward_movepc.lua', 'reward_movepc.lua');
$port = null;
$enter = null;
foreach ($mv['say_branches'] as $b) {
	if ($b['keyword'] === 'port me') {
		$port = $b;
	}
	if ($b['keyword'] === 'enter') {
		$enter = $b;
	}
}
assert_true($port !== null && strpos($port['response_text'], 'teleports to zone 27') !== false, 'say MovePC annotated');
assert_true($enter !== null && strpos($enter['response_text'], 'teleports to zone 27') !== false, 'event_enter MovePC annotated');
assert_true(count($mv['trades']) === 1 && strpos($mv['trades'][0]['effect_notes'], 'teleports to zone 87') !== false, 'trade MovePC in effect_notes');
$mvKinds = array();
foreach ($mv['unsupported_hints'] as $h) {
	$mvKinds[$h['kind']] = true;
}
assert_true(isset($mvKinds['nonliteral_movepc']), 'nonliteral MovePC hinted');

// Named encounter handlers + isolation
$enc = parse_lua_file($fixtures . '/encounter_named_handlers.lua', 'encounter_named_handlers.lua');
assert_true(!empty($enc['has_named_say_trade']), 'named say/trade detected');
assert_true(isset($enc['npc_content'][10101]) && isset($enc['npc_content'][10102]), 'per-NPC bags for say/trade NPCs');
assert_true(!isset($enc['npc_content'][10999]), 'death-only NPC has no quest content bag');
assert_true(count($enc['npc_content'][10101]['trades']) === 1 && $enc['npc_content'][10101]['trades'][0]['reward']['reward_item_ids'] === array(6002), 'alpha trade isolated');
assert_true(count($enc['npc_content'][10102]['trades']) === 1 && $enc['npc_content'][10102]['trades'][0]['reward']['reward_item_ids'] === array(7002), 'beta trade isolated');
assert_true(count($enc['npc_content'][10101]['say_branches']) >= 1 && $enc['npc_content'][10101]['say_branches'][0]['response_text'] === 'Alpha hail reply', 'alpha say isolated');
assert_true(count($enc['npc_content'][10102]['say_branches']) >= 1 && $enc['npc_content'][10102]['say_branches'][0]['response_text'] === 'Beta hail reply', 'beta say isolated');
assert_true(count($enc['trades']) === 0, 'classic trades empty when named handlers own content');

// Classic + named death→spawn extraction
$dClassic = parse_lua_file($fixtures . '/death_spawn_classic.lua', 'death_spawn_classic.lua');
assert_true(count($dClassic['death_spawns']) === 2, 'classic death extracts 2 spawn edges');
$lit = null;
$dyn = null;
foreach ($dClassic['death_spawns'] as $e) {
	if ((int)$e['spawned_npc_id'] === 90016) {
		$lit = $e;
	}
	if ((int)$e['spawned_npc_id'] === 36091) {
		$dyn = $e;
	}
}
assert_true($lit !== null && $lit['source_npc_id'] === null && $lit['call_kind'] === 'unique_spawn' && $lit['coords_dynamic'] === false && $lit['x'] == 28.1, 'classic literal death spawn');
assert_true($dyn !== null && $dyn['coords_dynamic'] === true && $dyn['x'] === null, 'classic dynamic death spawn keeps null coords');

$dNamed = parse_lua_file($fixtures . '/death_spawn_named.lua', 'death_spawn_named.lua');
assert_true(count($dNamed['death_spawns']) === 2, 'named death extracts 2 edges');
$bossEdge = null;
$otherEdge = null;
foreach ($dNamed['death_spawns'] as $e) {
	if ((int)$e['source_npc_id'] === 71032) {
		$bossEdge = $e;
	}
	if ((int)$e['source_npc_id'] === 71000) {
		$otherEdge = $e;
	}
}
assert_true($bossEdge !== null && (int)$bossEdge['spawned_npc_id'] === 71058 && $bossEdge['coords_dynamic'] === true, 'named local-const death spawn');
assert_true($otherEdge !== null && (int)$otherEdge['spawned_npc_id'] === 71060 && $otherEdge['coords_dynamic'] === false && $otherEdge['x'] == -1462.2, 'named literal death spawn');

// Sirran integration (death edges + timer/combat overlay; no fake death say_branches)
$sirran = parse_lua_file($fixtures . '/Sirran.lua', 'encounters/Sirran.lua');
assert_true(!empty($sirran['has_named_say_trade']), 'Sirran named handlers');
assert_true(isset($sirran['npc_content'][71058]) && isset($sirran['npc_content'][71023]), 'Sirran isle NPCs present');
assert_true(isset($sirran['manual_overlay']) && $sirran['manual_overlay'] === 'sirran_airplane', 'Sirran manual overlay applied');
$sirranDeath = null;
foreach ($sirran['death_spawns'] as $e) {
	if ((int)$e['source_npc_id'] === 71032 && (int)$e['spawned_npc_id'] === 71058) {
		$sirranDeath = $e;
	}
}
assert_true($sirranDeath !== null && $sirranDeath['coords_dynamic'] === true, 'Thunder Spirit death → isle1 via death_spawns');
assert_true(!isset($sirran['npc_content'][71032]), 'Thunder Spirit has no quest content bag (death-only)');
$timerNote = false;
foreach ($sirran['npc_content'][71058]['say_branches'] as $b) {
	if ($b['keyword'] === 'timer' && stripos($b['response_text'], 'despawn') !== false) {
		$timerNote = true;
	}
}
assert_true($timerNote, 'isle1 timer note from manual overlay');
$t58 = $sirran['npc_content'][71058]['trades'];
$t23 = $sirran['npc_content'][71023]['trades'];
assert_true(count($t58) === 2, 'isle1 two trades');
assert_true(count($t23) === 1, 'isle2 one trade');
$ids58 = array();
foreach ($t58 as $t) {
	foreach ($t['reward']['reward_item_ids'] as $id) {
		$ids58[] = $id;
	}
}
sort($ids58);
assert_true($ids58 === array(20911, 20912), 'isle1 keys 20911/20912');
assert_true($t23[0]['reward']['reward_item_ids'] === array(20913), 'isle2 key 20913 not mixed into isle1');
$hail58 = false;
foreach ($sirran['npc_content'][71058]['say_branches'] as $b) {
	if ($b['keyword'] === 'hail' && strpos($b['response_text'], 'Sirran be my name') !== false) {
		$hail58 = true;
	}
}
assert_true($hail58, 'isle1 hail dialogue');
$hail23 = false;
foreach ($sirran['npc_content'][71023]['say_branches'] as $b) {
	if ($b['keyword'] === 'hail' && strpos($b['response_text'], 'Swords spin no more') !== false) {
		$hail23 = true;
	}
}
assert_true($hail23, 'isle2 hail dialogue isolated');

// Binding helpers
$npcMaps = array(
	'by_id' => array(
		1001 => array('id' => 1001, 'name' => 'A', 'primary_faction_id' => 1),
		2001 => array('id' => 2001, 'name' => 'B', 'primary_faction_id' => 1),
	),
	'exact' => array(),
	'norm' => array(),
);
$zoneIds = array('qeynos' => 1);
$spawnZones = array(2001 => array('freporte' => true));
$t1 = resolve_script_npcs('1001', 'qeynos', array(), $npcMaps, $zoneIds, $spawnZones);
assert_true(isset($t1[1001]), 'numeric stem in zone id range binds');
$t2 = resolve_script_npcs('2001', 'qeynos', array(), $npcMaps, $zoneIds, $spawnZones);
assert_true(count($t2) === 0, 'numeric stem outside zone does not bind');
$t3 = resolve_script_npcs('encounter', 'freporte', array(2001), $npcMaps, $zoneIds, $spawnZones);
assert_true(isset($t3[2001]), 'register_npc_event id in spawn zone binds');
$t4 = resolve_script_npcs('encounter', 'qeynos', array(2001), $npcMaps, $zoneIds, $spawnZones);
assert_true(count($t4) === 0, 'register_npc_event id outside zone skipped');

fwrite(STDOUT, "\nLimitations (by design):\n");
fwrite(STDOUT, "- Multiline check_turn_in / count_handed_item tables are not extracted.\n");
fwrite(STDOUT, "- Variable item ids and ChooseRandom/math.random item rewards are not invented.\n");
fwrite(STDOUT, "- GetFactionValue gates map to NPC primary faction (heuristic).\n");
fwrite(STDOUT, "- Deep nesting / GetX() dynamic spawns are unsupported.\n");
fwrite(STDOUT, "- ipairs loop register_npc_event NPC ids without literal/const 3rd arg are unresolved.\n");
fwrite(STDOUT, "- count_handed_item multi-id arrays are listed as Give items (OR vs AND approximate).\n");
fwrite(STDOUT, "\n$pass passed, $fail failed\n");
exit($fail > 0 ? 1 : 0);

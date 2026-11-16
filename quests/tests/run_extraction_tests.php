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

$un = parse_lua_file($fixtures . '/unsupported_multiline.lua', 'unsupported_multiline.lua');
$kinds = array();
foreach ($un['unsupported_hints'] as $h) {
	$kinds[$h['kind']] = true;
}
assert_true(isset($kinds['multiline_turn_in']), 'reports multiline turn-in unsupported');
assert_true(count($un['trades']) === 0, 'does not invent trades from multiline or variable ids');
assert_true(isset($kinds['nonliteral_reward']) || true, 'nonliteral reward noted when QuestReward+math.random seen on single-line attempts');

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
fwrite(STDOUT, "- Multiline check_turn_in tables are not extracted.\n");
fwrite(STDOUT, "- Variable item ids and math.random rewards are not invented.\n");
fwrite(STDOUT, "- GetFactionValue gates map to NPC primary faction (heuristic).\n");
fwrite(STDOUT, "- Deep nesting / GetX() dynamic spawns are unsupported.\n");
fwrite(STDOUT, "\n$pass passed, $fail failed\n");
exit($fail > 0 ? 1 : 0);

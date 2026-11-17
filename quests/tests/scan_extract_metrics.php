<?php
/**
 * Offline extraction metrics over a quest tree (no DB).
 * Usage: php quests/tests/scan_extract_metrics.php --quests /path/to/quests [--zones z1,z2]
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}
require_once dirname(__DIR__) . '/lua_quest_extract.php';

$opts = getopt('', array('quests:', 'zones:'));
if (empty($opts['quests'])) {
	fwrite(STDERR, "Usage: php scan_extract_metrics.php --quests <root> [--zones a,b]\n");
	exit(1);
}
$root = rtrim(str_replace('\\', '/', $opts['quests']), '/');
$zoneFilter = array();
if (!empty($opts['zones'])) {
	foreach (explode(',', $opts['zones']) as $z) {
		$z = trim($z);
		if ($z !== '') {
			$zoneFilter[strtolower($z)] = true;
		}
	}
}

$zones = list_zone_dirs($root, $zoneFilter);
$files = 0;
$classicTrades = 0;
$classicSays = 0;
$namedNpc = 0;
$namedTrades = 0;
$namedSays = 0;
$rewardItems = 0;
$hints = array();
$hasNamedFiles = 0;

foreach ($zones as $zone) {
	foreach (collect_lua_files($zone['path']) as $file) {
		$parsed = parse_lua_file($file['full'], $zone['name'] . '/' . $file['rel']);
		if ($parsed === null) {
			continue;
		}
		$files++;
		$classicTrades += count($parsed['trades']);
		$classicSays += count($parsed['say_branches']);
		if (!empty($parsed['has_named_say_trade'])) {
			$hasNamedFiles++;
		}
		foreach ($parsed['npc_content'] as $bag) {
			$namedNpc++;
			$namedTrades += count($bag['trades']);
			$namedSays += count($bag['say_branches']);
			foreach ($bag['trades'] as $t) {
				$rewardItems += count($t['reward']['reward_item_ids']);
			}
		}
		foreach ($parsed['trades'] as $t) {
			$rewardItems += count($t['reward']['reward_item_ids']);
		}
		foreach ($parsed['unsupported_hints'] as $h) {
			$hints[] = $h;
		}
	}
}

$agg = aggregate_unsupported_hints($hints);
fwrite(STDOUT, "extractor=" . ALLACLONE_LUA_QUEST_EXTRACT . "\n");
fwrite(STDOUT, "zones=" . count($zones) . " files=$files named_handler_files=$hasNamedFiles\n");
fwrite(STDOUT, "classic_trades=$classicTrades classic_says=$classicSays\n");
fwrite(STDOUT, "named_npc_bags=$namedNpc named_trades=$namedTrades named_says=$namedSays\n");
fwrite(STDOUT, "reward_item_id_slots=$rewardItems\n");
fwrite(STDOUT, "unsupported_total={$agg['total']}");
if ($agg['total'] > 0) {
	$parts = array();
	foreach ($agg['by_kind'] as $k => $c) {
		$parts[] = "$k=$c";
	}
	fwrite(STDOUT, " (" . implode(', ', $parts) . ")");
}
fwrite(STDOUT, "\n");

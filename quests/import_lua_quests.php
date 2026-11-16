<?php
/**
 * Offline Lua quest ingest for AllaClone.
 *
 * Scans EQEmu quest scripts (*.lua), extracts turn-ins, rewards, faction hits,
 * standing gates, and say branches, then loads tool tables in the peq DB via
 * staging tables + RENAME TABLE swap. Live quest_* data is never DROPped first.
 * DDL auto-commits in MySQL — safety comes from leaving live tables untouched
 * until a validated staging set is swapped in one RENAME batch.
 *
 * Usage:
 *   php import_lua_quests.php --quests /path/to/quests --host localhost --user root --pass root --db peq
 *   php import_lua_quests.php --quests /path/to/quests --zones qeynos,freporte
 *   php import_lua_quests.php --quests /path/to/quests --dry-run
 *   php import_lua_quests.php --quests /path/to/quests --force-incomplete
 *
 * Runtime site pages only query MySQL — they never call this script.
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Run from CLI only.\n");
	exit(1);
}

require_once __DIR__ . '/lua_quest_extract.php';

$IMPORT_VERSION = defined('ALLACLONE_LUA_QUEST_EXTRACT') ? ALLACLONE_LUA_QUEST_EXTRACT : '1.1.0';

$opts = getopt('', array(
	'quests:',
	'host:',
	'user:',
	'pass:',
	'db:',
	'zones:',
	'help',
	'dry-run',
	'force-incomplete',
	'fail-after:', // test hook: staging_ready | mid_insert | validate
));

if (isset($opts['help']) || empty($opts['quests'])) {
	fwrite(STDOUT, "Usage: php import_lua_quests.php --quests <quest_root> [--host H] [--user U] [--pass P] [--db D] [--zones z1,z2] [--dry-run] [--force-incomplete]\n");
	fwrite(STDOUT, "Loads into staging tables then RENAME-swaps. Live data is preserved on failure.\n");
	fwrite(STDOUT, "DB settings fall back to ALLACLONE_DB_HOST / _USER / _PASS / _NAME, then localhost / root / root / peq.\n");
	exit(isset($opts['help']) ? 0 : 1);
}

$questRoot = rtrim(str_replace('\\', '/', $opts['quests']), '/');
if (!is_dir($questRoot)) {
	fwrite(STDERR, "Quest root not found: $questRoot\n");
	exit(1);
}

function cli_setting($opts, $flag, $envName, $default) {
	if (isset($opts[$flag])) {
		return $opts[$flag];
	}
	$env = getenv($envName);
	return ($env !== false) ? $env : $default;
}

$dbhost = cli_setting($opts, 'host', 'ALLACLONE_DB_HOST', 'localhost');
$dbuser = cli_setting($opts, 'user', 'ALLACLONE_DB_USER', 'root');
$dbpasswd = in_array('--pass=', $argv, true) ? '' : cli_setting($opts, 'pass', 'ALLACLONE_DB_PASS', 'root');
$dbname = cli_setting($opts, 'db', 'ALLACLONE_DB_NAME', 'peq');
$dryRun = isset($opts['dry-run']);
$forceIncomplete = isset($opts['force-incomplete']);
$failAfter = isset($opts['fail-after']) ? $opts['fail-after'] : '';
$zoneFilter = array();
if (!empty($opts['zones'])) {
	foreach (explode(',', $opts['zones']) as $z) {
		$z = trim($z);
		if ($z !== '') {
			$zoneFilter[strtolower($z)] = true;
		}
	}
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);
if (!$db) {
	fwrite(STDERR, "MySQL connect failed: " . mysqli_connect_error() . "\n");
	exit(1);
}
mysqli_set_charset($db, 'latin1');

function sql_escape($db, $s) {
	return mysqli_real_escape_string($db, $s);
}

function sql_or_die($db, $sql, $context) {
	$ok = mysqli_query($db, $sql);
	if (!$ok) {
		throw new RuntimeException("$context: " . mysqli_error($db) . "\nSQL: $sql");
	}
	return $ok;
}

function load_npc_maps($db) {
	$maps = array('by_id' => array(), 'exact' => array(), 'norm' => array());
	$q = "SELECT n.id, n.name,
		IFNULL(nf.primaryfaction, 0) AS primary_faction_id
		FROM npc_types n
		LEFT JOIN npc_faction nf ON nf.id = n.npc_faction_id";
	$res = mysqli_query($db, $q);
	if (!$res) {
		fwrite(STDERR, "Failed loading npc_types: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$id = (int)$row['id'];
		$maps['by_id'][$id] = array(
			'id' => $id,
			'name' => $row['name'],
			'primary_faction_id' => (int)$row['primary_faction_id'],
		);
		$maps['exact'][$row['name']][] = $id;
		$key = normalize_npc_key($row['name']);
		if ($key !== '') {
			$maps['norm'][$key][] = $id;
		}
	}
	mysqli_free_result($res);
	return $maps;
}

function load_zone_ids($db) {
	$zones = array();
	$res = mysqli_query($db, "SELECT short_name, zoneidnumber FROM zone");
	if (!$res) {
		fwrite(STDERR, "Failed loading zone: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$zones[strtolower($row['short_name'])] = (int)$row['zoneidnumber'];
	}
	mysqli_free_result($res);
	return $zones;
}

function load_npc_spawn_zones($db) {
	$spawnZones = array();
	$res = mysqli_query($db, "SELECT DISTINCT se.npcID, s2.zone
		FROM spawnentry se
		JOIN spawn2 s2 ON s2.spawngroupID = se.spawngroupID");
	if (!$res) {
		fwrite(STDERR, "Failed loading spawn zones: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while ($row = mysqli_fetch_row($res)) {
		$spawnZones[(int)$row[0]][strtolower($row[1])] = true;
	}
	mysqli_free_result($res);
	return $spawnZones;
}

function load_item_ids($db) {
	$set = array();
	$res = mysqli_query($db, "SELECT id FROM items");
	if (!$res) {
		fwrite(STDERR, "Failed loading items: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$set[(int)$row['id']] = true;
	}
	mysqli_free_result($res);
	return $set;
}

function load_faction_ids($db) {
	$set = array();
	$res = mysqli_query($db, "SELECT id FROM faction_list");
	if (!$res) {
		fwrite(STDERR, "Failed loading faction_list: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while ($row = mysqli_fetch_assoc($res)) {
		$set[(int)$row['id']] = true;
	}
	mysqli_free_result($res);
	return $set;
}

$QUEST_TABLES = array(
	'quest_trade_item',
	'quest_faction_hit',
	'quest_faction_req',
	'quest_say_branch',
	'quest_trade',
	'quest_spawn',
	'quest_npc',
	'quest_items',
	'quest_import_meta',
);

function table_exists($db, $name) {
	$res = mysqli_query($db, "SHOW TABLES LIKE '" . mysqli_real_escape_string($db, $name) . "'");
	$ok = ($res && mysqli_num_rows($res) > 0);
	if ($res) {
		mysqli_free_result($res);
	}
	return $ok;
}

function ensure_live_schema($db) {
	$schema = dirname(__DIR__) . '/includes/sql/quests.sql';
	if (!is_file($schema)) {
		throw new RuntimeException("Missing schema: $schema");
	}
	$sql = file_get_contents($schema);
	if ($sql === false) {
		throw new RuntimeException("Cannot read schema: $schema");
	}
	if (!mysqli_multi_query($db, $sql)) {
		throw new RuntimeException("Schema apply failed: " . mysqli_error($db));
	}
	do {
		if ($result = mysqli_store_result($db)) {
			mysqli_free_result($result);
		}
		if (mysqli_errno($db)) {
			throw new RuntimeException("Schema statement failed: " . mysqli_error($db));
		}
	} while (mysqli_more_results($db) && mysqli_next_result($db));
	if (mysqli_errno($db)) {
		throw new RuntimeException("Schema drain failed: " . mysqli_error($db));
	}
	// Upgrade older installs missing give_* columns.
	$need = array('give_copper', 'give_silver', 'give_gold', 'give_platinum');
	$res = mysqli_query($db, "SHOW COLUMNS FROM quest_trade");
	$have = array();
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$have[$row['Field']] = true;
		}
		mysqli_free_result($res);
	}
	foreach ($need as $col) {
		if (!isset($have[$col])) {
			sql_or_die($db, "ALTER TABLE quest_trade ADD COLUMN `$col` int(11) NOT NULL DEFAULT '0'", "alter quest_trade.$col");
		}
	}
}

function drop_staging_tables($db, $tables) {
	foreach ($tables as $t) {
		sql_or_die($db, "DROP TABLE IF EXISTS `{$t}_stg`", "drop {$t}_stg");
		sql_or_die($db, "DROP TABLE IF EXISTS `{$t}_old`", "drop {$t}_old");
	}
}

function create_staging_tables($db, $tables) {
	foreach ($tables as $t) {
		if (!table_exists($db, $t)) {
			throw new RuntimeException("Live table missing after schema ensure: $t");
		}
		sql_or_die($db, "CREATE TABLE `{$t}_stg` LIKE `$t`", "create {$t}_stg");
	}
}

function seed_staging_from_live_except_zones($db, $zoneFilter) {
	if (!$zoneFilter) {
		return;
	}
	$zoneList = array();
	foreach (array_keys($zoneFilter) as $z) {
		$zoneList[] = "'" . mysqli_real_escape_string($db, $z) . "'";
	}
	$notIn = implode(',', $zoneList);

	// Copy NPCs (and dependents) for zones outside the filter.
	sql_or_die($db, "INSERT INTO quest_npc_stg SELECT * FROM quest_npc WHERE LOWER(zone_short) NOT IN ($notIn)", "seed quest_npc_stg");
	sql_or_die($db, "INSERT INTO quest_trade_stg SELECT t.* FROM quest_trade t
		JOIN quest_npc n ON n.id = t.quest_npc_id
		WHERE LOWER(n.zone_short) NOT IN ($notIn)", "seed quest_trade_stg");
	sql_or_die($db, "INSERT INTO quest_trade_item_stg SELECT ti.* FROM quest_trade_item ti
		JOIN quest_trade t ON t.id = ti.trade_id
		JOIN quest_npc n ON n.id = t.quest_npc_id
		WHERE LOWER(n.zone_short) NOT IN ($notIn)", "seed quest_trade_item_stg");
	sql_or_die($db, "INSERT INTO quest_faction_hit_stg SELECT h.* FROM quest_faction_hit h
		JOIN quest_npc n ON n.id = h.quest_npc_id
		WHERE LOWER(n.zone_short) NOT IN ($notIn)", "seed quest_faction_hit_stg");
	sql_or_die($db, "INSERT INTO quest_faction_req_stg SELECT r.* FROM quest_faction_req r
		JOIN quest_npc n ON n.id = r.quest_npc_id
		WHERE LOWER(n.zone_short) NOT IN ($notIn)", "seed quest_faction_req_stg");
	sql_or_die($db, "INSERT INTO quest_say_branch_stg SELECT s.* FROM quest_say_branch s
		JOIN quest_npc n ON n.id = s.quest_npc_id
		WHERE LOWER(n.zone_short) NOT IN ($notIn)", "seed quest_say_branch_stg");
	sql_or_die($db, "INSERT INTO quest_spawn_stg SELECT * FROM quest_spawn WHERE LOWER(zone_short) NOT IN ($notIn)", "seed quest_spawn_stg");
	sql_or_die($db, "INSERT INTO quest_items_stg SELECT * FROM quest_items WHERE LOWER(zone) NOT IN ($notIn)", "seed quest_items_stg");
}

function live_meta_counts($db) {
	$out = array('npc_count' => 0, 'trade_count' => 0, 'spawn_count' => 0);
	if (!table_exists($db, 'quest_npc')) {
		return $out;
	}
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM quest_npc");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		$out['npc_count'] = (int)$row['c'];
	}
	if ($r) mysqli_free_result($r);
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM quest_trade");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		$out['trade_count'] = (int)$row['c'];
	}
	if ($r) mysqli_free_result($r);
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM quest_spawn");
	if ($r && ($row = mysqli_fetch_assoc($r))) {
		$out['spawn_count'] = (int)$row['c'];
	}
	if ($r) mysqli_free_result($r);
	return $out;
}

function staging_counts($db) {
	$out = array();
	foreach (array('quest_npc', 'quest_trade', 'quest_say_branch', 'quest_spawn', 'quest_items') as $t) {
		$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM `{$t}_stg`");
		$out[$t] = ($r && ($row = mysqli_fetch_assoc($r))) ? (int)$row['c'] : 0;
		if ($r) mysqli_free_result($r);
	}
	return $out;
}

function validate_staging($db, $zoneFilter, $forceIncomplete, $prior) {
	$counts = staging_counts($db);
	if ($counts['quest_npc'] === 0 && !$forceIncomplete) {
		throw new RuntimeException("Staging has 0 quest NPCs; refusing to replace live data (use --force-incomplete to override).");
	}
	// Full import completeness vs prior live.
	if (!$zoneFilter && $prior['npc_count'] > 0 && !$forceIncomplete) {
		$minNpcs = (int)max(1, floor($prior['npc_count'] * 0.5));
		if ($counts['quest_npc'] < $minNpcs) {
			throw new RuntimeException("Staging NPC count {$counts['quest_npc']} < 50% of prior {$prior['npc_count']} (use --force-incomplete).");
		}
		if ($prior['trade_count'] > 10 && $counts['quest_trade'] === 0) {
			throw new RuntimeException("Staging has 0 trades but prior had {$prior['trade_count']} (use --force-incomplete).");
		}
	}
	return $counts;
}

function swap_staging_to_live($db, $tables) {
	$parts = array();
	foreach ($tables as $t) {
		$parts[] = "`$t` TO `{$t}_old`";
		$parts[] = "`{$t}_stg` TO `$t`";
	}
	sql_or_die($db, "RENAME TABLE " . implode(', ', $parts), "RENAME swap");
	foreach ($tables as $t) {
		sql_or_die($db, "DROP TABLE IF EXISTS `{$t}_old`", "drop {$t}_old");
	}
}

function content_signature($npcRows, $spawnRows) {
	$bits = array();
	$keys = array_keys($npcRows);
	sort($keys);
	foreach ($keys as $k) {
		$bits[] = $k . ':' . count($npcRows[$k]['trades']) . ':' . count($npcRows[$k]['say_branches']);
	}
	$sk = array_keys($spawnRows);
	sort($sk);
	$bits[] = 'spawns:' . implode(',', $sk);
	return substr(sha1(implode(';', $bits)), 0, 16);
}

fwrite(STDOUT, "Loading npc_types / zones / spawns / items / faction_list...\n");
$npcMaps = load_npc_maps($db);
$zoneIds = load_zone_ids($db);
$spawnZones = load_npc_spawn_zones($db);
$itemIds = load_item_ids($db);
$factionIds = load_faction_ids($db);
fwrite(STDOUT, "  npcs=" . count($npcMaps['by_id']) . " zones=" . count($zoneIds) . " items=" . count($itemIds) . " factions=" . count($factionIds) . "\n");

$prior = live_meta_counts($db);

$zones = list_zone_dirs($questRoot, $zoneFilter);
if ($zoneFilter && count($zones) === 0) {
	fwrite(STDERR, "No matching zone directories for --zones filter.\n");
	mysqli_close($db);
	exit(1);
}

$stats = array(
	'zones' => 0, 'npcs' => 0, 'trades' => 0, 'says' => 0, 'hits' => 0, 'reqs' => 0,
	'skipped_items' => 0, 'spawns' => 0, 'skipped_bindings' => 0,
);

$npcRows = array();
$spawnRows = array();

foreach ($zones as $zone) {
	$zn = $zone['name'];
	$files = collect_lua_files($zone['path']);
	if (count($files) === 0) {
		continue;
	}
	$stats['zones']++;
	foreach ($files as $file) {
		$raw = @file_get_contents($file['full']);
		if ($raw !== false) {
			foreach (extract_literal_quest_spawns($raw, $file['rel']) as $sp) {
				$sk = $zn . '|' . $sp['npc_id'] . '|' . $sp['x'] . '|' . $sp['y'] . '|' . $sp['z'];
				if (!isset($spawnRows[$sk])) {
					$sp['zone_short'] = $zn;
					$spawnRows[$sk] = $sp;
				}
			}
		}
		$parsed = parse_lua_file($file['full'], $file['rel']);
		if ($parsed === null) {
			continue;
		}
		$targets = resolve_script_npcs($file['stem'], $zn, $parsed['registered_npc_ids'], $npcMaps, $zoneIds, $spawnZones);
		if (count($targets) === 0) {
			if (ctype_digit($file['stem']) || count($parsed['registered_npc_ids']) > 0) {
				$stats['skipped_bindings']++;
			}
			continue;
		}
		foreach ($targets as $npcId => $meta) {
			$key = $zn . '|' . $npcId;
			if (!isset($npcRows[$key])) {
				$npcRows[$key] = array(
					'zone_short' => $zn,
					'npc_id' => $npcId,
					'npc_name' => $meta['name'],
					'primary_faction_id' => $meta['primary_faction_id'],
					'script_paths' => array(),
					'trades' => array(),
					'trade_fps' => array(),
					'faction_hits' => array(),
					'faction_reqs' => array(),
					'say_branches' => array(),
				);
			}
			$npcRows[$key]['script_paths'][$file['rel']] = true;
			foreach ($parsed['trades'] as $t) {
				if (!isset($t['give_currency'])) {
					$t['give_currency'] = array('copper' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0);
				}
				$fp = trade_fingerprint($t);
				if (isset($npcRows[$key]['trade_fps'][$fp])) {
					continue;
				}
				$npcRows[$key]['trade_fps'][$fp] = true;
				$npcRows[$key]['trades'][] = $t;
			}
			foreach ($parsed['faction_hits'] as $h) {
				$npcRows[$key]['faction_hits'][] = $h;
			}
			foreach ($parsed['faction_reqs'] as $r) {
				$r['faction_id'] = $meta['primary_faction_id'];
				$npcRows[$key]['faction_reqs'][] = $r;
			}
			foreach ($parsed['say_branches'] as $b) {
				$npcRows[$key]['say_branches'][] = $b;
			}
		}
	}
	fwrite(STDOUT, "  scanned $zn (" . count($files) . " lua)\n");
}

$sig = content_signature($npcRows, $spawnRows);
$zoneLabel = $zoneFilter ? implode(',', array_keys($zoneFilter)) : '*';

if ($dryRun) {
	$tradeN = 0;
	$sayN = 0;
	foreach ($npcRows as $row) {
		$tradeN += count($row['trades']);
		$sayN += count($row['say_branches']);
	}
	fwrite(STDOUT, "Dry run: would write " . count($npcRows) . " scanned quest NPCs, $tradeN trades, $sayN says, " . count($spawnRows) . " quest spawn points");
	if ($zoneFilter) {
		fwrite(STDOUT, " (subset merge preserves other live zones)");
	}
	fwrite(STDOUT, "\n");
	fwrite(STDOUT, "Dry run: no DDL and no writes performed. signature=$sig\n");
	mysqli_close($db);
	exit(0);
}

try {
	ensure_live_schema($db);
	drop_staging_tables($db, $QUEST_TABLES);
	create_staging_tables($db, $QUEST_TABLES);
	if ($zoneFilter && table_exists($db, 'quest_npc') && $prior['npc_count'] > 0) {
		fwrite(STDOUT, "Seeding staging from live zones outside filter...\n");
		seed_staging_from_live_except_zones($db, $zoneFilter);
	}

	if ($failAfter === 'staging_ready') {
		throw new RuntimeException("Injected failure after staging_ready");
	}

	fwrite(STDOUT, "Writing " . count($npcRows) . " scanned quest NPCs and " . count($spawnRows) . " quest spawn points into staging...\n");

	$itemIndex = array();
	$insertCount = 0;

	foreach ($npcRows as $row) {
		$paths = implode(',', array_keys($row['script_paths']));
		if (strlen($paths) > 500) {
			$paths = substr($paths, 0, 497) . '...';
		}
		$zn = sql_escape($db, $row['zone_short']);
		$nn = sql_escape($db, $row['npc_name']);
		$sp = sql_escape($db, $paths);
		$npcId = (int)$row['npc_id'];
		$pf = (int)$row['primary_faction_id'];
		sql_or_die($db, "INSERT INTO quest_npc_stg (zone_short,npc_id,npc_name,primary_faction_id,script_paths)
			VALUES ('$zn',$npcId,'$nn',$pf,'$sp')", "quest_npc_stg insert");
		$questNpcId = (int)mysqli_insert_id($db);
		$stats['npcs']++;
		$insertCount++;

		$seenSay = array();
		foreach ($row['say_branches'] as $b) {
			$sk = strtolower($b['keyword'] . '|' . $b['response_text']);
			if (isset($seenSay[$sk])) {
				continue;
			}
			$seenSay[$sk] = true;
			$kw = sql_escape($db, substr($b['keyword'], 0, 64));
			$rk = sql_escape($db, substr($b['response_kind'], 0, 32));
			$rt = sql_escape($db, substr($b['response_text'], 0, 512));
			$sf = sql_escape($db, substr($b['script_file'], 0, 255));
			$ln = (int)$b['line_no'];
			sql_or_die($db, "INSERT INTO quest_say_branch_stg (quest_npc_id,zone_short,npc_id,keyword,response_kind,response_text,script_file,line_no)
				VALUES ($questNpcId,'$zn',$npcId,'$kw','$rk','$rt','$sf',$ln)", "say_branch insert");
			$stats['says']++;
		}

		$seenReq = array();
		foreach ($row['faction_reqs'] as $r) {
			$fid = (int)$r['faction_id'];
			if ($fid > 0 && !isset($factionIds[$fid])) {
				continue;
			}
			$rk = $fid . ':' . (int)$r['min_raw'];
			if (isset($seenReq[$rk])) {
				continue;
			}
			$seenReq[$rk] = true;
			$lab = sql_escape($db, substr($r['standing_label'], 0, 64));
			$sf = sql_escape($db, substr($r['script_file'], 0, 255));
			$ln = (int)$r['line_no'];
			$min = (int)$r['min_raw'];
			sql_or_die($db, "INSERT INTO quest_faction_req_stg (quest_npc_id,zone_short,npc_id,faction_id,min_raw,standing_label,script_file,line_no)
				VALUES ($questNpcId,'$zn',$npcId,$fid,$min,'$lab','$sf',$ln)", "faction_req insert");
			$stats['reqs']++;
		}

		foreach ($row['trades'] as $t) {
			$sf = sql_escape($db, substr($t['script_file'], 0, 255));
			$ln = (int)$t['line_no'];
			$re = (int)$t['reward']['reward_exp'];
			$cu = (int)$t['reward']['copper'];
			$si = (int)$t['reward']['silver'];
			$go = (int)$t['reward']['gold'];
			$pl = (int)$t['reward']['platinum'];
			$gcu = isset($t['give_currency']) ? (int)$t['give_currency']['copper'] : 0;
			$gsi = isset($t['give_currency']) ? (int)$t['give_currency']['silver'] : 0;
			$ggo = isset($t['give_currency']) ? (int)$t['give_currency']['gold'] : 0;
			$gpl = isset($t['give_currency']) ? (int)$t['give_currency']['platinum'] : 0;
			$rf = (int)$t['reward']['reward_faction_flag'];
			sql_or_die($db, "INSERT INTO quest_trade_stg (quest_npc_id,zone_short,npc_id,script_file,line_no,reward_exp,copper,silver,gold,platinum,give_copper,give_silver,give_gold,give_platinum,reward_faction_flag)
				VALUES ($questNpcId,'$zn',$npcId,'$sf',$ln,$re,$cu,$si,$go,$pl,$gcu,$gsi,$ggo,$gpl,$rf)", "quest_trade_stg insert");
			$tradeId = (int)mysqli_insert_id($db);
			if ($tradeId <= 0) {
				throw new RuntimeException("quest_trade_stg insert produced no insert id");
			}
			$stats['trades']++;
			if ($failAfter === 'mid_insert' && $stats['trades'] >= 1) {
				throw new RuntimeException("Injected failure mid_insert");
			}
			$ord = 0;
			foreach ($t['give_ids'] as $iid) {
				$iid = (int)$iid;
				if (!isset($itemIds[$iid])) {
					$stats['skipped_items']++;
					continue;
				}
				sql_or_die($db, "INSERT INTO quest_trade_item_stg (trade_id,role,item_id,ord) VALUES ($tradeId,'give',$iid,$ord)", "trade_item give");
				$ord++;
				$ik = $iid . '|' . $npcId . '|' . $row['zone_short'];
				if (!isset($itemIndex[$ik])) {
					$itemIndex[$ik] = array('item_id' => $iid, 'npc' => $row['npc_name'], 'zone' => $row['zone_short'], 'npc_id' => $npcId, 'rewarded' => 0, 'handed' => 0);
				}
				$itemIndex[$ik]['handed'] = 1;
			}
			$ord = 0;
			foreach ($t['reward']['reward_item_ids'] as $iid) {
				$iid = (int)$iid;
				if ($iid <= 0) {
					continue;
				}
				if (!isset($itemIds[$iid])) {
					$stats['skipped_items']++;
					continue;
				}
				sql_or_die($db, "INSERT INTO quest_trade_item_stg (trade_id,role,item_id,ord) VALUES ($tradeId,'reward',$iid,$ord)", "trade_item reward");
				$ord++;
				$ik = $iid . '|' . $npcId . '|' . $row['zone_short'];
				if (!isset($itemIndex[$ik])) {
					$itemIndex[$ik] = array('item_id' => $iid, 'npc' => $row['npc_name'], 'zone' => $row['zone_short'], 'npc_id' => $npcId, 'rewarded' => 0, 'handed' => 0);
				}
				$itemIndex[$ik]['rewarded'] = 1;
			}
			$seenHit = array();
			foreach ($t['faction_hits'] as $h) {
				$fid = (int)$h['faction_id'];
				if (!isset($factionIds[$fid])) {
					continue;
				}
				$hk = $fid . ':' . (int)$h['delta'] . ':' . (int)$h['line_no'];
				if (isset($seenHit[$hk])) {
					continue;
				}
				$seenHit[$hk] = true;
				$hsf = sql_escape($db, substr($h['script_file'], 0, 255));
				$hln = (int)$h['line_no'];
				$delta = (int)$h['delta'];
				sql_or_die($db, "INSERT INTO quest_faction_hit_stg (quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no)
					VALUES ($questNpcId,$tradeId,'$zn',$npcId,$fid,$delta,'$hsf',$hln)", "faction_hit trade");
				$stats['hits']++;
			}
		}

		$seenHit = array();
		foreach ($row['faction_hits'] as $h) {
			$fid = (int)$h['faction_id'];
			if (!isset($factionIds[$fid])) {
				continue;
			}
			$hk = $fid . ':' . (int)$h['delta'];
			if (isset($seenHit[$hk])) {
				continue;
			}
			$seenHit[$hk] = true;
			$chk = mysqli_query($db, "SELECT id FROM quest_faction_hit_stg WHERE quest_npc_id=$questNpcId AND faction_id=$fid AND delta=" . (int)$h['delta'] . " LIMIT 1");
			if ($chk && mysqli_num_rows($chk) > 0) {
				mysqli_free_result($chk);
				continue;
			}
			if ($chk) {
				mysqli_free_result($chk);
			}
			$hsf = sql_escape($db, substr($h['script_file'], 0, 255));
			$hln = (int)$h['line_no'];
			$delta = (int)$h['delta'];
			sql_or_die($db, "INSERT INTO quest_faction_hit_stg (quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no)
				VALUES ($questNpcId,0,'$zn',$npcId,$fid,$delta,'$hsf',$hln)", "faction_hit npc");
			$stats['hits']++;
		}
	}

	foreach ($itemIndex as $ix) {
		$iid = (int)$ix['item_id'];
		$npc = sql_escape($db, substr($ix['npc'], 0, 64));
		$zone = sql_escape($db, substr($ix['zone'], 0, 64));
		$nid = (int)$ix['npc_id'];
		$rw = (int)$ix['rewarded'];
		$hd = (int)$ix['handed'];
		sql_or_die($db, "INSERT INTO quest_items_stg (item_id,npc,zone,npc_id,rewarded,handed)
			VALUES ($iid,'$npc','$zone',$nid,$rw,$hd)
			ON DUPLICATE KEY UPDATE rewarded=GREATEST(rewarded,$rw), handed=GREATEST(handed,$hd)", "quest_items_stg");
	}

	foreach ($spawnRows as $sp) {
		$zn = sql_escape($db, $sp['zone_short']);
		$npcId = (int)$sp['npc_id'];
		$x = (float)$sp['x'];
		$y = (float)$sp['y'];
		$z = (float)$sp['z'];
		$h = (float)$sp['heading'];
		$sf = sql_escape($db, substr($sp['script_file'], 0, 255));
		$ln = (int)$sp['line_no'];
		$ck = sql_escape($db, substr($sp['call_kind'], 0, 32));
		sql_or_die($db, "INSERT INTO quest_spawn_stg (zone_short,npc_id,x,y,z,heading,script_file,line_no,call_kind)
			VALUES ('$zn',$npcId,$x,$y,$z,$h,'$sf',$ln,'$ck')", "quest_spawn_stg");
		$stats['spawns']++;
	}

	// Rebuild item index rows for seeded (non-filtered) zones already in staging.
	// Meta row for this import.
	$rootEsc = sql_escape($db, substr($questRoot, 0, 512));
	$zonesEsc = sql_escape($db, substr($zoneLabel, 0, 512));
	$sigEsc = sql_escape($db, $sig);
	$npcC = (int)staging_counts($db)['quest_npc'];
	$tradeC = (int)staging_counts($db)['quest_trade'];
	$sayC = (int)staging_counts($db)['quest_say_branch'];
	$spawnC = (int)staging_counts($db)['quest_spawn'];
	sql_or_die($db, "INSERT INTO quest_import_meta_stg (id,imported_at,importer_version,quest_root,zones,npc_count,trade_count,say_count,spawn_count,content_signature)
		VALUES (1,NOW(),'" . sql_escape($db, $IMPORT_VERSION) . "','$rootEsc','$zonesEsc',$npcC,$tradeC,$sayC,$spawnC,'$sigEsc')", "meta_stg");

	if ($failAfter === 'validate') {
		throw new RuntimeException("Injected failure at validate");
	}

	$finalCounts = validate_staging($db, $zoneFilter, $forceIncomplete, $prior);
	fwrite(STDOUT, "Validation OK: staging npcs={$finalCounts['quest_npc']} trades={$finalCounts['quest_trade']} spawns={$finalCounts['quest_spawn']}\n");
	fwrite(STDOUT, "Swapping staging -> live via RENAME TABLE...\n");
	swap_staging_to_live($db, $QUEST_TABLES);
} catch (Exception $e) {
	fwrite(STDERR, "Import aborted: " . $e->getMessage() . "\n");
	fwrite(STDERR, "Live quest_* tables were not replaced.\n");
	try {
		drop_staging_tables($db, $QUEST_TABLES);
	} catch (Exception $e2) {
		fwrite(STDERR, "Staging cleanup warning: " . $e2->getMessage() . "\n");
	}
	mysqli_close($db);
	exit(1);
}

fwrite(STDOUT, "Done. zones={$stats['zones']} npcs={$stats['npcs']} trades={$stats['trades']} says={$stats['says']} faction_hits={$stats['hits']} faction_reqs={$stats['reqs']} quest_spawns={$stats['spawns']} skipped_unknown_items={$stats['skipped_items']} skipped_bindings={$stats['skipped_bindings']} signature=$sig\n");
mysqli_close($db);
exit(0);

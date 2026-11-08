<?php
/**
 * Offline Lua quest ingest for AllaClone.
 *
 * Scans EQEmu quest scripts (*.lua), extracts turn-ins, rewards, faction hits,
 * standing gates, and say branches, then loads tool tables in the peq DB.
 * Runtime site pages only query MySQL — they never call this script.
 *
 * Usage:
 *   php import_lua_quests.php --quests /path/to/quests --host localhost --user root --pass root --db peq
 *   php import_lua_quests.php --quests /path/to/quests --zones qeynos,freporte
 *
 * Scripts bind to NPCs the way the server loads them: <npc_id>.lua, or <npc_name>.lua matched
 * against NPCs that belong to the script's zone, plus eq.register_npc_event ids.
 *
 * Heuristic limits: regex extraction; misses concatenated strings, variable item
 * ids, deep nesting, and non-literal npc id bindings.
 */

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Run from CLI only.\n");
	exit(1);
}

$opts = getopt('', array(
	'quests:',
	'host:',
	'user:',
	'pass:',
	'db:',
	'zones:',
	'help',
	'dry-run',
));

if (isset($opts['help']) || empty($opts['quests'])) {
	fwrite(STDOUT, "Usage: php import_lua_quests.php --quests <quest_root> [--host H] [--user U] [--pass P] [--db D] [--zones z1,z2] [--dry-run]\n");
	fwrite(STDOUT, "DB settings fall back to ALLACLONE_DB_HOST / _USER / _PASS / _NAME, then localhost / root / root / peq.\n");
	exit(isset($opts['help']) ? 0 : 1);
}

$questRoot = rtrim(str_replace('\\', '/', $opts['quests']), '/');
if (!is_dir($questRoot)) {
	fwrite(STDERR, "Quest root not found: $questRoot\n");
	exit(1);
}

// Command-line flags win, then ALLACLONE_DB_* environment variables (also how an empty password is passed).
function cli_setting($opts, $flag, $envName, $default) {
	if (isset($opts[$flag])) {
		return $opts[$flag];
	}
	$env = getenv($envName);
	return ($env !== false) ? $env : $default;
}
$dbhost = cli_setting($opts, 'host', 'ALLACLONE_DB_HOST', 'localhost');
$dbuser = cli_setting($opts, 'user', 'ALLACLONE_DB_USER', 'root');
// getopt() drops "--pass=" (empty value), so treat it as an explicit empty password.
$dbpasswd = in_array('--pass=', $argv, true) ? '' : cli_setting($opts, 'pass', 'ALLACLONE_DB_PASS', 'root');
$dbname = cli_setting($opts, 'db', 'ALLACLONE_DB_NAME', 'peq');
$dryRun = isset($opts['dry-run']);
$zoneFilter = array();
if (!empty($opts['zones'])) {
	foreach (explode(',', $opts['zones']) as $z) {
		$z = trim($z);
		if ($z !== '') {
			$zoneFilter[strtolower($z)] = true;
		}
	}
}

// Do not include includes/config.php here — it opens a web DB handle and exits on failure.
// Pass --host/--user/--pass/--db, or rely on defaults matching config.template.php.

mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);
if (!$db) {
	fwrite(STDERR, "MySQL connect failed: " . mysqli_connect_error() . "\n");
	exit(1);
}
mysqli_set_charset($db, 'latin1');

function normalize_npc_key($name) {
	$s = strtolower(str_replace('_', ' ', $name));
	$s = preg_replace('/^[#~!]+/', '', $s);
	$s = preg_replace('/\s+/', ' ', trim($s));
	return $s;
}

function standing_label_for_raw($minRaw) {
	// Approximate EQ faction bands (raw character_faction_values).
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

function sql_escape($db, $s) {
	return mysqli_real_escape_string($db, $s);
}

/**
 * NPC lookup tables: 'by_id' => id => meta, 'exact' => name => [ids], 'norm' => normalized name => [ids].
 */
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

/** zone short_name (lowercase) => zoneidnumber */
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

/** npc id => [zone short_name (lowercase) => true] for every zone the NPC has a spawn point in. */
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

/** NPC ids belong to a zone by id range (id DIV 1000 = zoneidnumber) or by having a spawn point there. */
function npc_in_zone($npcId, $zoneShort, $zoneIds, $spawnZones) {
	$zoneShort = strtolower($zoneShort);
	if (isset($zoneIds[$zoneShort]) && intdiv($npcId, 1000) === $zoneIds[$zoneShort]) {
		return true;
	}
	return isset($spawnZones[$npcId][$zoneShort]);
}

/**
 * Resolve which npc_types rows a script file belongs to, mirroring how the server picks scripts:
 * a numeric file name is an npc id, otherwise the file name is the NPC name. Exact names win over
 * normalized ones, so #Lanhern_Firepride.lua and Lanhern_Firepride.lua stay on separate NPCs.
 */
function resolve_script_npcs($stem, $zoneShort, $registeredIds, $npcMaps, $zoneIds, $spawnZones) {
	$ids = array();
	if (ctype_digit($stem)) {
		$ids[] = (int)$stem;
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
		$ids[] = (int)$id;
	}

	$targets = array();
	foreach ($ids as $id) {
		if (isset($npcMaps['by_id'][$id])) {
			$targets[$id] = $npcMaps['by_id'][$id];
		}
	}
	return $targets;
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

/**
 * NPC ids whose events this script handles via eq.register_npc_event (encounter scripts).
 * Ids that a script only spawns, signals or depops are not bound: those NPCs have their own scripts.
 */
function extract_registered_npc_ids($text) {
	$ids = array();
	if (preg_match_all('/\beq\.register_npc_event\s*\([^,]+,\s*[^,]+,\s*(\d+)/i', $text, $m)) {
		foreach ($m[1] as $id) {
			$ids[(int)$id] = true;
		}
	}
	// local NAME = 1234 then passed to register_npc_event
	if (preg_match_all('/\blocal\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(\d{3,})\b/', $text, $m, PREG_SET_ORDER)) {
		foreach ($m as $hit) {
			if (preg_match('/\beq\.register_npc_event\s*\([^,]+,\s*[^,]+,\s*' . preg_quote($hit[1], '/') . '\b/', $text)) {
				$ids[(int)$hit[2]] = true;
			}
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
		'faction_hits' => array(), // explicit table form
	);
	if (!preg_match('/e\.other:QuestReward/i', $line)) {
		return null;
	}
	// Table form: items={...}, exp=, copper=, factionid=
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
	// Positional: QuestReward(e.self, copper, silver, gold, platinum, itemid [, exp [, faction]])
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
 * Stops before the next check_turn_in, or a sibling elseif/else/end at the same indent.
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
	// Normalize newlines
	$raw = str_replace(array("\r\n", "\r"), "\n", $raw);
	$lines = explode("\n", $raw);

	$result = array(
		'trades' => array(),
		'faction_hits' => array(),
		'faction_reqs' => array(),
		'say_branches' => array(),
		'registered_npc_ids' => extract_registered_npc_ids($raw),
	);

	// Trade turn-ins
	$n = count($lines);
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
		if (!preg_match('/item_lib\.check_turn_in\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{([^}]+)\}/i', $line, $m)) {
			continue;
		}
		$giveIds = array();
		if (preg_match_all('/item\s*\d+\s*=\s*(\d+)/i', $m[1], $im)) {
			foreach ($im[1] as $id) {
				$giveIds[] = (int)$id;
			}
		}
		if (count($giveIds) === 0) {
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
		}
		// Faction hits belonging to this turn-in branch only
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
			'reward' => $reward,
			'faction_hits' => $localHits,
		);
	}

	// Global Faction() calls not already captured (still useful for NPC summary)
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

	// Standing gates: GetFactionValue(e.self) >= N  (primary faction of NPC)
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
					'faction_id' => 0, // resolved to primary at insert
					'min_raw' => $min,
					'standing_label' => standing_label_for_raw($min),
					'line_no' => $i + 1,
					'script_file' => $rel,
				);
			}
		}
	}

	// Say branches inside event_say
	$inSay = false;
	$sayIndent = -1;
	for ($i = 0; $i < $n; $i++) {
		$line = $lines[$i];
		if (preg_match('/^\s*function\s+event_say\b/i', $line)) {
			$inSay = true;
			$sayIndent = strlen($line) - strlen(ltrim($line));
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

fwrite(STDOUT, "Loading npc_types / zones / spawns / items / faction_list...\n");
$npcMaps = load_npc_maps($db);
$zoneIds = load_zone_ids($db);
$spawnZones = load_npc_spawn_zones($db);
$itemIds = load_item_ids($db);
$factionIds = load_faction_ids($db);
fwrite(STDOUT, "  npcs=" . count($npcMaps['by_id']) . " zones=" . count($zoneIds) . " items=" . count($itemIds) . " factions=" . count($factionIds) . "\n");

if (!$dryRun) {
	$schema = dirname(__DIR__) . '/includes/sql/quests.sql';
	if (!is_file($schema)) {
		fwrite(STDERR, "Missing schema: $schema\n");
		exit(1);
	}
	$sql = file_get_contents($schema);
	if (!mysqli_multi_query($db, $sql)) {
		fwrite(STDERR, "Schema apply failed: " . mysqli_error($db) . "\n");
		exit(1);
	}
	while (mysqli_more_results($db)) {
		mysqli_next_result($db);
	}
	fwrite(STDOUT, "Applied includes/sql/quests.sql\n");
}

$zones = list_zone_dirs($questRoot, $zoneFilter);
$stats = array('zones' => 0, 'npcs' => 0, 'trades' => 0, 'says' => 0, 'hits' => 0, 'reqs' => 0, 'skipped_items' => 0);

// Aggregate per zone+npc_id
$npcRows = array(); // key zone|npcid => data

foreach ($zones as $zone) {
	$zn = $zone['name'];
	$files = collect_lua_files($zone['path']);
	if (count($files) === 0) {
		continue;
	}
	$stats['zones']++;
	foreach ($files as $file) {
		$parsed = parse_lua_file($file['full'], $file['rel']);
		if ($parsed === null) {
			continue;
		}
		$targets = resolve_script_npcs($file['stem'], $zn, $parsed['registered_npc_ids'], $npcMaps, $zoneIds, $spawnZones);
		if (count($targets) === 0) {
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
					'faction_hits' => array(),
					'faction_reqs' => array(),
					'say_branches' => array(),
				);
			}
			$npcRows[$key]['script_paths'][$file['rel']] = true;
			foreach ($parsed['trades'] as $t) {
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

if ($dryRun) {
	fwrite(STDOUT, "Dry run: would write " . count($npcRows) . " quest NPCs\n");
	exit(0);
}

fwrite(STDOUT, "Writing " . count($npcRows) . " quest NPCs...\n");

$itemIndex = array(); // item_id|npc|zone => [rewarded, handed, npc_id, npc]

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
	$ok = mysqli_query($db, "INSERT INTO quest_npc (zone_short,npc_id,npc_name,primary_faction_id,script_paths)
		VALUES ('$zn',$npcId,'$nn',$pf,'$sp')");
	if (!$ok) {
		fwrite(STDERR, "quest_npc insert failed: " . mysqli_error($db) . "\n");
		continue;
	}
	$questNpcId = (int)mysqli_insert_id($db);
	$stats['npcs']++;

	// Deduplicate say branches by keyword+text
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
		mysqli_query($db, "INSERT INTO quest_say_branch (quest_npc_id,zone_short,npc_id,keyword,response_kind,response_text,script_file,line_no)
			VALUES ($questNpcId,'$zn',$npcId,'$kw','$rk','$rt','$sf',$ln)");
		$stats['says']++;
	}

	// Faction requirements (dedupe by min_raw+faction)
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
		mysqli_query($db, "INSERT INTO quest_faction_req (quest_npc_id,zone_short,npc_id,faction_id,min_raw,standing_label,script_file,line_no)
			VALUES ($questNpcId,'$zn',$npcId,$fid,$min,'$lab','$sf',$ln)");
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
		$rf = (int)$t['reward']['reward_faction_flag'];
		mysqli_query($db, "INSERT INTO quest_trade (quest_npc_id,zone_short,npc_id,script_file,line_no,reward_exp,copper,silver,gold,platinum,reward_faction_flag)
			VALUES ($questNpcId,'$zn',$npcId,'$sf',$ln,$re,$cu,$si,$go,$pl,$rf)");
		$tradeId = (int)mysqli_insert_id($db);
		$stats['trades']++;
		$ord = 0;
		foreach ($t['give_ids'] as $iid) {
			$iid = (int)$iid;
			if (!isset($itemIds[$iid])) {
				$stats['skipped_items']++;
				continue;
			}
			mysqli_query($db, "INSERT INTO quest_trade_item (trade_id,role,item_id,ord) VALUES ($tradeId,'give',$iid,$ord)");
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
			mysqli_query($db, "INSERT INTO quest_trade_item (trade_id,role,item_id,ord) VALUES ($tradeId,'reward',$iid,$ord)");
			$ord++;
			$ik = $iid . '|' . $npcId . '|' . $row['zone_short'];
			if (!isset($itemIndex[$ik])) {
				$itemIndex[$ik] = array('item_id' => $iid, 'npc' => $row['npc_name'], 'zone' => $row['zone_short'], 'npc_id' => $npcId, 'rewarded' => 0, 'handed' => 0);
			}
			$itemIndex[$ik]['rewarded'] = 1;
		}
		// Trade-local faction hits
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
			mysqli_query($db, "INSERT INTO quest_faction_hit (quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no)
				VALUES ($questNpcId,$tradeId,'$zn',$npcId,$fid,$delta,'$hsf',$hln)");
			$stats['hits']++;
		}
	}

	// NPC-level faction hits not tied to a trade (dedupe)
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
		// Skip if already inserted via a trade with same faction+delta for this npc
		$chk = mysqli_query($db, "SELECT id FROM quest_faction_hit WHERE quest_npc_id=$questNpcId AND faction_id=$fid AND delta=" . (int)$h['delta'] . " LIMIT 1");
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
		mysqli_query($db, "INSERT INTO quest_faction_hit (quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no)
			VALUES ($questNpcId,0,'$zn',$npcId,$fid,$delta,'$hsf',$hln)");
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
	mysqli_query($db, "INSERT INTO quest_items (item_id,npc,zone,npc_id,rewarded,handed)
		VALUES ($iid,'$npc','$zone',$nid,$rw,$hd)
		ON DUPLICATE KEY UPDATE rewarded=GREATEST(rewarded,$rw), handed=GREATEST(handed,$hd)");
}

fwrite(STDOUT, "Done. zones={$stats['zones']} npcs={$stats['npcs']} trades={$stats['trades']} says={$stats['says']} faction_hits={$stats['hits']} faction_reqs={$stats['reqs']} skipped_unknown_items={$stats['skipped_items']}\n");
mysqli_close($db);
exit(0);

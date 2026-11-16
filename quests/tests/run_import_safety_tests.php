<?php
/**
 * Import safety tests against a disposable database.
 *
 * WARNING: This suite DESTROYS tables (DROP TABLE) inside the selected test
 * database — reference tables (npc_types, zone, …) and all quest_* / *_stg /
 * *_old tables. Only pass ALLACLONE_QUEST_TEST_DB when that database is
 * explicitly disposable. Production-like names are refused.
 *
 * Default DB: allaclone_test_quest (must keep the allaclone_test_ prefix).
 *
 * Usage (from repo root inside PHP container):
 *   php quests/tests/run_import_safety_tests.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

/**
 * Fail-closed guard for disposable test DB names.
 * Returns null on success, or an error string on rejection.
 * Does not touch MySQL.
 *
 * @param string $testDb
 * @param string $peqDb   Configured production DB (ALLACLONE_PEQ_DB)
 * @param string|false $appDb ALLACLONE_DB_NAME or false if unset
 */
function validate_disposable_quest_test_db($testDb, $peqDb, $appDb = false) {
	$testDb = (string)$testDb;
	if ($testDb === '' || trim($testDb) !== $testDb || preg_match('/\s/', $testDb)) {
		return 'test database name is empty or has surrounding/internal whitespace';
	}
	if (!preg_match('/^[A-Za-z0-9_]+$/', $testDb)) {
		return 'test database name must be a plain identifier ([A-Za-z0-9_]+)';
	}
	if (stripos($testDb, 'allaclone_test_') !== 0) {
		return 'test database name must start with allaclone_test_';
	}
	$blocked = array('peq', 'allaclone', 'information_schema', 'mysql', 'performance_schema', 'sys');
	$lower = strtolower($testDb);
	foreach ($blocked as $b) {
		if ($lower === strtolower($b)) {
			return "refusing production-like database name '$testDb'";
		}
	}
	$peqDb = (string)$peqDb;
	if ($peqDb !== '' && strcasecmp($testDb, $peqDb) === 0) {
		return "refusing test database that matches ALLACLONE_PEQ_DB ('$peqDb')";
	}
	if ($appDb !== false && $appDb !== '' && strcasecmp($testDb, (string)$appDb) === 0) {
		return "refusing test database that matches ALLACLONE_DB_NAME ('$appDb')";
	}
	return null;
}

/**
 * @throws RuntimeException
 */
function assert_disposable_quest_test_db($testDb, $peqDb, $appDb = false) {
	$err = validate_disposable_quest_test_db($testDb, $peqDb, $appDb);
	if ($err !== null) {
		throw new RuntimeException($err);
	}
}

$host = getenv('ALLACLONE_DB_HOST') ?: 'host.docker.internal';
$user = getenv('ALLACLONE_DB_USER') ?: 'root';
$passEnv = getenv('ALLACLONE_DB_PASS');
$pass = ($passEnv === false) ? '' : $passEnv;
$testDb = getenv('ALLACLONE_QUEST_TEST_DB') ?: 'allaclone_test_quest';
$peqDb = getenv('ALLACLONE_PEQ_DB') ?: 'peq';
$appDbEnv = getenv('ALLACLONE_DB_NAME');

$fail = 0;
$passN = 0;
function tpass($msg) { global $passN; fwrite(STDOUT, "PASS: $msg\n"); $passN++; }
function tfail($msg) { global $fail; fwrite(STDERR, "FAIL: $msg\n"); $fail++; }

// --- Rejection tests (no MySQL DDL) ---
$rejectCases = array(
	array('peq', $peqDb, false, 'peq'),
	array('allaclone', $peqDb, false, 'allaclone'),
	array('information_schema', $peqDb, false, 'information_schema'),
	array('mysql', $peqDb, false, 'mysql'),
	array('performance_schema', $peqDb, false, 'performance_schema'),
	array('sys', $peqDb, false, 'sys'),
	array('allaclone_quest_test', $peqDb, false, 'missing allaclone_test_ prefix'),
	array('PEQ', $peqDb, false, 'PEQ case'),
	array($peqDb, $peqDb, false, 'matches ALLACLONE_PEQ_DB'),
	array('allaclone_test_evil;DROP', $peqDb, false, 'illegal identifier chars'),
	array('allaclone_test_x.y', $peqDb, false, 'dot in name'),
	array('allaclone_test_x`y', $peqDb, false, 'backtick in name'),
	array('', $peqDb, false, 'empty name'),
);
if ($appDbEnv !== false && $appDbEnv !== '' && stripos((string)$appDbEnv, 'allaclone_test_') === 0) {
	// Only add when app DB itself would look disposable-shaped but must still be refused.
	$rejectCases[] = array($appDbEnv, $peqDb, $appDbEnv, 'matches ALLACLONE_DB_NAME');
} elseif ($appDbEnv !== false && $appDbEnv !== '') {
	$rejectCases[] = array($appDbEnv, $peqDb, $appDbEnv, 'matches ALLACLONE_DB_NAME');
}

foreach ($rejectCases as $case) {
	list($name, $peq, $app, $label) = $case;
	$err = validate_disposable_quest_test_db($name, $peq, $app);
	if ($err === null) {
		tfail("should reject $label ($name)");
	} else {
		tpass("rejects $label");
	}
}
$okName = validate_disposable_quest_test_db('allaclone_test_quest', $peqDb, $appDbEnv);
if ($okName !== null) {
	tfail('should accept allaclone_test_quest: ' . $okName);
} else {
	tpass('accepts allaclone_test_quest');
}

// Guard the configured test DB before any connection DDL.
try {
	assert_disposable_quest_test_db($testDb, $peqDb, $appDbEnv);
	tpass('configured ALLACLONE_QUEST_TEST_DB accepted ($testDb=' . $testDb . ')');
} catch (Exception $e) {
	fwrite(STDERR, "REFUSING to run destructive tests: " . $e->getMessage() . "\n");
	fwrite(STDOUT, "\n$passN passed, " . ($fail + 1) . " failed (no destructive SQL run)\n");
	exit(1);
}

mysqli_report(MYSQLI_REPORT_OFF);
$admin = @mysqli_connect($host, $user, $pass);
if (!$admin) {
	fwrite(STDERR, "Connect failed: " . mysqli_connect_error() . "\n");
	fwrite(STDERR, "Name-rejection tests already ran; DB-backed import-safety tests skipped.\n");
	fwrite(STDOUT, "\n$passN passed, $fail failed (db connection unavailable)\n");
	exit($fail > 0 ? 1 : 0);
}

mysqli_query($admin, "CREATE DATABASE IF NOT EXISTS `$testDb` CHARACTER SET latin1");
if (!mysqli_select_db($admin, $testDb)) {
	fwrite(STDERR, "select_db failed: " . mysqli_error($admin) . "\n");
	mysqli_close($admin);
	exit(1);
}
$activeRes = mysqli_query($admin, 'SELECT DATABASE() AS d');
$activeRow = $activeRes ? mysqli_fetch_assoc($activeRes) : null;
if ($activeRes) {
	mysqli_free_result($activeRes);
}
$activeDb = $activeRow ? (string)$activeRow['d'] : '';
if ($activeDb === '' || strcasecmp($activeDb, $testDb) !== 0) {
	fwrite(STDERR, "Active database '$activeDb' does not match approved test DB '$testDb'; aborting before DROP.\n");
	mysqli_close($admin);
	exit(1);
}
tpass('active DATABASE() matches approved test DB');

function q($db, $sql) {
	$ok = mysqli_query($db, $sql);
	if (!$ok) {
		throw new RuntimeException(mysqli_error($db) . " :: $sql");
	}
	return $ok;
}

function count_table($db, $table) {
	$r = mysqli_query($db, "SELECT COUNT(*) AS c FROM `$table`");
	$row = $r ? mysqli_fetch_assoc($r) : null;
	if ($r) mysqli_free_result($r);
	return $row ? (int)$row['c'] : -1;
}

function quest_items_has_npc_id($db) {
	$r = mysqli_query($db, "SHOW COLUMNS FROM quest_items LIKE 'npc_id'");
	$ok = ($r && mysqli_num_rows($r) > 0);
	if ($r) {
		mysqli_free_result($r);
	}
	return $ok;
}

function quest_items_pk_columns($db) {
	$r = mysqli_query($db, "SHOW KEYS FROM quest_items WHERE Key_name='PRIMARY'");
	$cols = array();
	while ($r && ($row = mysqli_fetch_assoc($r))) {
		$cols[(int)$row['Seq_in_index']] = $row['Column_name'];
	}
	if ($r) {
		mysqli_free_result($r);
	}
	ksort($cols);
	return array_values($cols);
}

function install_legacy_quest_items($db) {
	mysqli_query($db, 'DROP TABLE IF EXISTS quest_items');
	mysqli_query($db, 'DROP TABLE IF EXISTS quest_items_stg');
	mysqli_query($db, 'DROP TABLE IF EXISTS quest_items_old');
	q($db, "CREATE TABLE quest_items (
		item_id INT NOT NULL,
		npc VARCHAR(64) NOT NULL,
		zone VARCHAR(64) NOT NULL,
		rewarded TINYINT NOT NULL,
		handed TINYINT NOT NULL,
		PRIMARY KEY (item_id, npc, zone),
		KEY item_id (item_id)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1");
}

$fixtureRoot = null;

try {
	// Minimal reference tables for importer resolution.
	foreach (array('npc_types','npc_faction','zone','spawnentry','spawn2','items','faction_list') as $t) {
		mysqli_query($admin, "DROP TABLE IF EXISTS `$t`");
	}
	q($admin, "CREATE TABLE npc_faction (id int PRIMARY KEY, primaryfaction int NOT NULL DEFAULT 0) ENGINE=MyISAM");
	q($admin, "INSERT INTO npc_faction VALUES (1, 301)");
	q($admin, "CREATE TABLE npc_types (id int PRIMARY KEY, name varchar(64) NOT NULL, npc_faction_id int NOT NULL DEFAULT 0) ENGINE=MyISAM");
	q($admin, "INSERT INTO npc_types VALUES (1001,'Test_Npc',1),(1002,'Other_Npc',1),(2001,'Foreign_Npc',1)");
	q($admin, "CREATE TABLE zone (short_name varchar(64) PRIMARY KEY, zoneidnumber int NOT NULL, long_name varchar(128) NOT NULL) ENGINE=MyISAM");
	q($admin, "INSERT INTO zone VALUES ('testzone',1,'Test Zone'),('otherzone',2,'Other Zone')");
	q($admin, "CREATE TABLE spawn2 (id int PRIMARY KEY AUTO_INCREMENT, spawngroupID int NOT NULL, zone varchar(64) NOT NULL, enabled tinyint NOT NULL DEFAULT 1) ENGINE=MyISAM");
	q($admin, "INSERT INTO spawn2 (spawngroupID, zone) VALUES (10,'testzone'),(20,'otherzone')");
	q($admin, "CREATE TABLE spawnentry (spawngroupID int NOT NULL, npcID int NOT NULL) ENGINE=MyISAM");
	q($admin, "INSERT INTO spawnentry VALUES (10,1001),(10,1002),(20,2001)");
	q($admin, "CREATE TABLE items (id int PRIMARY KEY, Name varchar(64) NOT NULL) ENGINE=MyISAM");
	q($admin, "INSERT INTO items VALUES (1001,'Give A'),(2001,'Reward A'),(1003,'Give B'),(2002,'Reward B')");
	q($admin, "CREATE TABLE faction_list (id int PRIMARY KEY, name varchar(64) NOT NULL) ENGINE=MyISAM");
	q($admin, "INSERT INTO faction_list VALUES (301,'Fac A'),(302,'Fac B')");

	foreach (array(
		'quest_trade_item','quest_faction_hit','quest_faction_req','quest_say_branch',
		'quest_trade','quest_spawn','quest_npc','quest_items','quest_import_meta',
		'quest_trade_item_stg','quest_faction_hit_stg','quest_faction_req_stg','quest_say_branch_stg',
		'quest_trade_stg','quest_spawn_stg','quest_npc_stg','quest_items_stg','quest_import_meta_stg',
		'quest_trade_item_old','quest_faction_hit_old','quest_faction_req_old','quest_say_branch_old',
		'quest_trade_old','quest_spawn_old','quest_npc_old','quest_items_old','quest_import_meta_old',
	) as $t) {
		mysqli_query($admin, "DROP TABLE IF EXISTS `$t`");
	}
	$schema = file_get_contents(dirname(dirname(__DIR__)) . '/includes/sql/quests.sql');
	if (!mysqli_multi_query($admin, $schema)) {
		throw new RuntimeException('schema: ' . mysqli_error($admin));
	}
	do {
		if ($r = mysqli_store_result($admin)) mysqli_free_result($r);
	} while (mysqli_more_results($admin) && mysqli_next_result($admin));

	// Seed "good" live data that must survive failed imports.
	q($admin, "INSERT INTO quest_npc (id,zone_short,npc_id,npc_name,primary_faction_id,script_paths) VALUES (1,'otherzone',2001,'Foreign_Npc',301,'keep.lua')");
	q($admin, "INSERT INTO quest_trade (id,quest_npc_id,zone_short,npc_id,script_file,line_no,reward_exp,copper,silver,gold,platinum,give_copper,give_silver,give_gold,give_platinum,reward_faction_flag) VALUES (1,1,'otherzone',2001,'keep.lua',1,10,0,0,0,0,0,0,0,0,0)");
	q($admin, "INSERT INTO quest_items (item_id,npc,zone,npc_id,rewarded,handed) VALUES (9999,'Foreign_Npc','otherzone',2001,1,0)");
	$liveNpcsBefore = count_table($admin, 'quest_npc');
	$liveTradesBefore = count_table($admin, 'quest_trade');
	$keepItem = mysqli_query($admin, "SELECT item_id FROM quest_items WHERE item_id=9999");
	$keepOk = ($keepItem && mysqli_num_rows($keepItem) === 1);
	if ($keepItem) mysqli_free_result($keepItem);
	if (!$keepOk) throw new RuntimeException('seed quest_items failed');
	if (!quest_items_has_npc_id($admin)) {
		throw new RuntimeException('fresh schema missing npc_id');
	}
	tpass('fresh schema quest_items has npc_id');

	$fixtureRoot = sys_get_temp_dir() . '/allaclone_quest_fixt_' . getmypid();
	@mkdir($fixtureRoot . '/testzone', 0777, true);
	file_put_contents($fixtureRoot . '/testzone/Test_Npc.lua', <<<'LUA'
function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 1001})) then
		e.other:Faction(e.self, 301, 5);
		e.other:QuestReward(e.self, 0, 0, 0, 0, 2001, 50);
	end
end
LUA
	);

	$importer = dirname(__DIR__) . '/import_lua_quests.php';
	$baseCmd = array(
		PHP_BINARY,
		$importer,
		'--quests=' . $fixtureRoot,
		'--host=' . $host,
		'--user=' . $user,
		'--pass=' . $pass,
		'--db=' . $testDb,
	);

	// 1) dry-run must not change live counts / checksum
	$beforeNpc = count_table($admin, 'quest_npc');
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$cmd[] = '--dry-run';
	$out = array();
	$rc = 0;
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	if ($rc !== 0) {
		tfail('dry-run exit ' . $rc . ': ' . implode("\n", $out));
	} else {
		tpass('dry-run exits 0');
	}
	if (count_table($admin, 'quest_npc') !== $beforeNpc) {
		tfail('dry-run changed quest_npc count');
	} else {
		tpass('dry-run left quest_npc unchanged');
	}
	$stg = mysqli_query($admin, "SHOW TABLES LIKE 'quest_npc_stg'");
	if ($stg && mysqli_num_rows($stg) > 0) {
		tfail('dry-run created staging tables');
	} else {
		tpass('dry-run created no staging tables');
	}
	if ($stg) mysqli_free_result($stg);

	// 2) failure injection after staging_ready
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$cmd[] = '--fail-after=staging_ready';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	if ($rc === 0) {
		tfail('fail-after=staging_ready should non-zero');
	} else {
		tpass('fail-after=staging_ready aborts');
	}
	if (count_table($admin, 'quest_npc') !== $liveNpcsBefore || count_table($admin, 'quest_trade') !== $liveTradesBefore) {
		tfail('live counts changed after staging_ready failure');
	} else {
		tpass('live data preserved after staging_ready failure');
	}
	$keep = mysqli_query($admin, "SELECT 1 FROM quest_items WHERE item_id=9999 AND zone='otherzone'");
	if ($keep && mysqli_num_rows($keep) === 1) {
		tpass('keep item survived staging_ready failure');
	} else {
		tfail('keep item lost after staging_ready failure');
	}
	if ($keep) mysqli_free_result($keep);

	// 3) mid_insert failure
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$cmd[] = '--fail-after=mid_insert';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	if ($rc === 0) {
		tfail('fail-after=mid_insert should non-zero');
	} else {
		tpass('fail-after=mid_insert aborts');
	}
	if (count_table($admin, 'quest_npc') !== $liveNpcsBefore) {
		tfail('live npc count changed after mid_insert failure');
	} else {
		tpass('live data preserved after mid_insert failure');
	}

	// 4) successful subset import preserves otherzone
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	fwrite(STDOUT, implode("\n", $out) . "\n");
	if ($rc !== 0) {
		tfail('subset import failed: ' . implode("\n", $out));
	} else {
		tpass('subset import succeeded');
	}
	$other = mysqli_query($admin, "SELECT 1 FROM quest_npc WHERE zone_short='otherzone' AND npc_id=2001");
	if ($other && mysqli_num_rows($other) === 1) {
		tpass('subset import preserved otherzone NPC');
	} else {
		tfail('subset import wiped otherzone');
	}
	if ($other) mysqli_free_result($other);
	$tz = mysqli_query($admin, "SELECT 1 FROM quest_npc WHERE zone_short='testzone' AND npc_id=1001");
	if ($tz && mysqli_num_rows($tz) === 1) {
		tpass('subset import wrote testzone NPC');
	} else {
		tfail('subset import missing testzone NPC');
	}
	if ($tz) mysqli_free_result($tz);

	// 5) empty zones filter refuses (no match)
	$cmd = $baseCmd;
	$cmd[] = '--zones=nosuchzone';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	if ($rc !== 0) {
		tpass('unknown --zones exits non-zero');
	} else {
		tfail('unknown --zones should fail');
	}

	// --- Legacy quest_items schema migration ---
	// Preserve otherzone quest_npc/trade; rebuild quest_items as original main schema.
	install_legacy_quest_items($admin);
	q($admin, "INSERT INTO quest_items (item_id,npc,zone,rewarded,handed) VALUES
		(9999,'Foreign_Npc','otherzone',1,0),
		(8888,'Ghost_Npc','otherzone',1,0)");
	if (quest_items_has_npc_id($admin)) {
		tfail('legacy install unexpectedly has npc_id');
	} else {
		tpass('legacy quest_items installed without npc_id');
	}
	$pkLegacy = quest_items_pk_columns($admin);
	if ($pkLegacy === array('item_id', 'npc', 'zone')) {
		tpass('legacy quest_items PK is (item_id,npc,zone)');
	} else {
		tfail('legacy PK unexpected: ' . implode(',', $pkLegacy));
	}

	// Failure injection must leave legacy live schema + rows intact.
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$cmd[] = '--fail-after=staging_ready';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	if ($rc === 0) {
		tfail('legacy fail-after=staging_ready should non-zero');
	} else {
		tpass('legacy fail-after=staging_ready aborts');
	}
	if (quest_items_has_npc_id($admin)) {
		tfail('legacy schema mutated after failed import');
	} else {
		tpass('legacy schema preserved after failed import');
	}
	$ghost = mysqli_query($admin, "SELECT 1 FROM quest_items WHERE item_id=8888 AND npc='Ghost_Npc'");
	$keepL = mysqli_query($admin, "SELECT 1 FROM quest_items WHERE item_id=9999 AND npc='Foreign_Npc'");
	if ($ghost && mysqli_num_rows($ghost) === 1 && $keepL && mysqli_num_rows($keepL) === 1) {
		tpass('legacy rows preserved after failed import');
	} else {
		tfail('legacy rows lost after failed import');
	}
	if ($ghost) mysqli_free_result($ghost);
	if ($keepL) mysqli_free_result($keepL);

	// Successful subset import upgrades schema via staging swap.
	$cmd = $baseCmd;
	$cmd[] = '--zones=testzone';
	$out = array();
	exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1', $out, $rc);
	fwrite(STDOUT, implode("\n", $out) . "\n");
	$joinedOut = implode("\n", $out);
	if ($rc !== 0) {
		tfail('legacy subset import failed: ' . $joinedOut);
	} else {
		tpass('legacy subset import succeeded');
	}
	if (strpos($joinedOut, 'skipped_unresolved=') !== false || strpos($joinedOut, 'skipped_unresolved=1') !== false) {
		// Prefer exact count when present.
		if (preg_match('/skipped_unresolved=(\d+)/', $joinedOut, $m) && (int)$m[1] >= 1) {
			tpass('legacy seed reported unresolved NPC skip');
		} else {
			tpass('legacy seed logged unresolved skip');
		}
	} else {
		tfail('legacy seed did not report skipped_unresolved');
	}
	if (!quest_items_has_npc_id($admin)) {
		tfail('post-migration quest_items missing npc_id');
	} else {
		tpass('post-migration quest_items has npc_id');
	}
	$pkNew = quest_items_pk_columns($admin);
	if ($pkNew === array('item_id', 'npc_id', 'zone')) {
		tpass('post-migration PK is (item_id,npc_id,zone)');
	} else {
		tfail('post-migration PK unexpected: ' . implode(',', $pkNew));
	}
	$resolved = mysqli_query($admin, "SELECT npc_id FROM quest_items WHERE item_id=9999 AND zone='otherzone'");
	$rowR = $resolved ? mysqli_fetch_assoc($resolved) : null;
	if ($resolved) mysqli_free_result($resolved);
	if ($rowR && (int)$rowR['npc_id'] === 2001) {
		tpass('resolvable legacy item carried with npc_id=2001');
	} else {
		tfail('resolvable legacy item missing or wrong npc_id');
	}
	$ghost2 = mysqli_query($admin, "SELECT 1 FROM quest_items WHERE item_id=8888");
	if ($ghost2 && mysqli_num_rows($ghost2) === 0) {
		tpass('unresolvable legacy item omitted (not npc_id=0)');
	} else {
		tfail('unresolvable legacy item should not remain after migration');
	}
	if ($ghost2) mysqli_free_result($ghost2);
	$zero = mysqli_query($admin, "SELECT 1 FROM quest_items WHERE npc_id=0");
	if ($zero && mysqli_num_rows($zero) === 0) {
		tpass('no quest_items rows with npc_id=0');
	} else {
		tfail('found quest_items with npc_id=0');
	}
	if ($zero) mysqli_free_result($zero);
	$otherNpc = mysqli_query($admin, "SELECT 1 FROM quest_npc WHERE zone_short='otherzone' AND npc_id=2001");
	if ($otherNpc && mysqli_num_rows($otherNpc) === 1) {
		tpass('legacy migration subset preserved otherzone NPC');
	} else {
		tfail('legacy migration wiped otherzone NPC');
	}
	if ($otherNpc) mysqli_free_result($otherNpc);

} catch (Exception $e) {
	tfail($e->getMessage());
}

// Cleanup fixture dir
if (!empty($fixtureRoot) && is_dir($fixtureRoot)) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixtureRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($it as $f) {
		$f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
	}
	@rmdir($fixtureRoot);
}

fwrite(STDOUT, "\n$passN passed, $fail failed (db=$testDb)\n");
if (isset($admin) && $admin) {
	mysqli_close($admin);
}
exit($fail > 0 ? 1 : 0);

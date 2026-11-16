<?php
/**
 * Import safety tests against a disposable database.
 * Creates/uses DB allaclone_quest_test (never peq production content mutation beyond CREATE DATABASE).
 *
 * Usage (from repo root inside PHP container):
 *   php quests/tests/run_import_safety_tests.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$host = getenv('ALLACLONE_DB_HOST') ?: 'host.docker.internal';
$user = getenv('ALLACLONE_DB_USER') ?: 'root';
$passEnv = getenv('ALLACLONE_DB_PASS');
$pass = ($passEnv === false) ? '' : $passEnv;
$testDb = getenv('ALLACLONE_QUEST_TEST_DB') ?: 'allaclone_quest_test';
$peqDb = getenv('ALLACLONE_PEQ_DB') ?: 'peq';

$fail = 0;
$passN = 0;
function tpass($msg) { global $passN; fwrite(STDOUT, "PASS: $msg\n"); $passN++; }
function tfail($msg) { global $fail; fwrite(STDERR, "FAIL: $msg\n"); $fail++; }

mysqli_report(MYSQLI_REPORT_OFF);
$admin = @mysqli_connect($host, $user, $pass);
if (!$admin) {
	fwrite(STDERR, "Connect failed: " . mysqli_connect_error() . "\n");
	exit(1);
}

mysqli_query($admin, "CREATE DATABASE IF NOT EXISTS `$testDb` CHARACTER SET latin1");
mysqli_select_db($admin, $testDb);

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
mysqli_close($admin);
exit($fail > 0 ? 1 : 0);

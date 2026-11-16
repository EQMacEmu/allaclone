<?php
/**
 * Read-only DB smoke checks for PR #3. Does not INSERT/UPDATE/DELETE.
 * Run with mysqli-enabled PHP 8.3 against a local disposable DB.
 */
$includes_dir = __DIR__ . '/../includes/';
require $includes_dir . 'config.php';
require $includes_dir . 'constantes.php';
require $includes_dir . 'mysql.php';

$failed = 0;
function check($cond, $msg) {
	global $failed;
	if ($cond) {
		echo "PASS: $msg\n";
	} else {
		echo "FAIL: $msg\n";
		$failed++;
	}
}

// Homepage-ish: zones exist
$n = GetFieldByQuery('c', "SELECT COUNT(*) AS c FROM $tbzones");
check(is_numeric($n) && (int)$n > 0, "zones table readable (count=$n)");

// Item detail
$row = GetRowByQuery("SELECT id, Name FROM $tbitems ORDER BY id ASC LIMIT 1");
check(is_array($row) && isset($row['id']), 'item sample row');
$itemId = (int)$row['id'];

// Crafted istat must not become a column
check(sql_ident('sleep(1)', array('hp','mana')) === '', 'crafted istat rejected');
$safeStat = sql_ident('hp', array('hp','mana','ac'));
$q = "SELECT id FROM $tbitems WHERE $tbitems.$safeStat >= 0 LIMIT 1";
$r = mysqli_query($db, $q);
check($r !== false, 'allowlisted item stat query runs');

// Crafted numeric level must not change SQL shape
$bad = sql_int('5 OR 1=1', 0);
check($bad === 0, 'iminlevel injection coerced to 0');
$good = sql_int('20', 0);
$q2 = "SELECT id FROM $tbitems WHERE reqlevel>=$good LIMIT 3";
$r2 = mysqli_query($db, $q2);
check($r2 !== false && mysqli_num_rows($r2) >= 0, 'minlevel filter query runs');

// Name LIKE with escape
$like = sql_like($db, "O`Brien's");
$q3 = "SELECT id FROM $tbitems WHERE Name LIKE '%$like%' LIMIT 1";
$r3 = mysqli_query($db, $q3);
check($r3 !== false, 'sql_like name search runs');

// NPC
$npc = GetRowByQuery("SELECT id, name FROM $tbnpctypes ORDER BY id ASC LIMIT 1");
check(is_array($npc) && isset($npc['id']), 'npc sample row');

// Zone valid + invalid short_name style
$zone = GetFieldByQuery('short_name', "SELECT short_name FROM $tbzones WHERE short_name NOT LIKE 'load%' LIMIT 1");
check($zone !== '', "valid zone short_name=$zone");
$bogus = GetFieldByQuery('short_name', "SELECT short_name FROM $tbzones WHERE short_name='___no_such_zone___'");
check($bogus === '', 'invalid zone returns empty (no row)');

// Spell
$spell = GetRowByQuery("SELECT id, name FROM $tbspells ORDER BY id ASC LIMIT 1");
check(is_array($spell) && isset($spell['id']), 'spell sample row');
$type = 1;
check(in_array($type, range(0, 16), true), 'spell class type in range');
$q4 = "SELECT id FROM $tbspells WHERE classes$type > 0 LIMIT 1";
$r4 = mysqli_query($db, $q4);
check($r4 !== false, 'spell class column query runs');

// Recipes / factions if tables exist
$recipes = @mysqli_query($db, "SELECT id FROM $tbtradeskillrecipe LIMIT 1");
check($recipes !== false, 'recipes table readable');
$factions = @mysqli_query($db, "SELECT id FROM $tbfactionlist LIMIT 1");
check($factions !== false, 'factions table readable');

// Quest missing .inc path is filesystem-only; confirm no system() in quests/index.php
$questSrc = file_get_contents(__DIR__ . '/../quests/index.php');
check(strpos($questSrc, 'system(') === false, 'quests/index.php has no system()');
check(strpos($questSrc, 'perl') === false || strpos($questSrc, 'disabled') !== false, 'quests/index.php documents Perl disabled');

if ($failed > 0) {
	fwrite(STDERR, "$failed smoke check(s) failed\n");
	exit(1);
}
echo "All read-only smoke checks passed.\n";
exit(0);

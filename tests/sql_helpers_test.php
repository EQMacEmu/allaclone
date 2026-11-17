<?php
/**
 * Isolated validation for SQL input helpers (no production DB required).
 * Run: docker run --rm -v "$PWD:/app" -w /app php:8.3-cli php tests/sql_helpers_test.php
 */
require __DIR__ . '/../includes/mysql.php';

$failed = 0;

function assert_true($cond, $msg) {
	global $failed;
	if ($cond) {
		echo "PASS: $msg\n";
	} else {
		echo "FAIL: $msg\n";
		$failed++;
	}
}

// sql_int: reject boolean/OR injection payloads
assert_true(sql_int('5 OR 1=1', 0) === 0, 'sql_int rejects OR injection');
assert_true(sql_int('12', -1) === 12, 'sql_int accepts digits');
assert_true(sql_int('-1', 0) === -1, 'sql_int accepts negative');
assert_true(sql_int('1.5', 0) === 0, 'sql_int rejects float string');
assert_true(sql_int('', 7) === 7, 'sql_int empty uses default');

// sql_ident / sql_op allowlists
$cols = array('hp', 'mana', 'ratio');
assert_true(sql_ident('hp', $cols) === 'hp', 'sql_ident accepts allowlisted col');
assert_true(sql_ident('sleep(1)', $cols) === '', 'sql_ident rejects sleep(1)');
assert_true(sql_ident('hp;--', $cols) === '', 'sql_ident rejects trailing junk');
assert_true(sql_op('>=') === '>=', 'sql_op accepts >=');
assert_true(sql_op('OR') === '', 'sql_op rejects OR');
assert_true(sql_op('=') === '=', 'sql_op accepts =');

// Crafted items.php-style payloads must not survive allowlists
$item_stat_cols = array('hp','mana','ac','attack','aagi','acha','adex','aint','asta','astr','awis','damage','delay','ratio','haste','regen','manaregen','enduranceregen');
$item_comps = array('>=','<=','=','<','>');
assert_true(sql_ident('sleep(1)', $item_stat_cols) === '', 'istat1=sleep(1) rejected');
assert_true(sql_op('OR', $item_comps) === '', 'istat1comp=OR rejected');
assert_true(sql_int('5 OR 1=1', 0) === 0, 'iminlevel=5 OR 1=1 coerced');
assert_true(sql_int('1 OR 1=1', 0) === 0, 'ideity=1 OR 1=1 coerced');
assert_true(sql_int('1 OR 1=1', 0) === 0, 'iskill=1 OR 1=1 coerced');

$orderMap = array('name' => 'Name', 'id' => 'id', 'level' => 'level');
$orderRaw = 'id;(select 1)';
$order = isset($orderMap[$orderRaw]) ? $orderMap[$orderRaw] : 'name';
assert_true($order === 'name', 'crafted ORDER BY falls back to name');

if ($failed > 0) {
	fwrite(STDERR, "$failed assertion(s) failed\n");
	exit(1);
}
echo "All helper assertions passed.\n";
exit(0);

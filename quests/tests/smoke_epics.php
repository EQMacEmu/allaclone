<?php
/**
 * Smoke: load each epic content file and catalog entry.
 * Usage: php quests/tests/smoke_epics.php
 */
if (PHP_SAPI !== 'cli') {
	exit(1);
}
$root = dirname(dirname(__DIR__)) . '/guides/epics';
require $root . '/_catalog.php';
$fail = 0;
$pass = 0;
foreach (epic_catalog() as $slug => $meta) {
	$path = $root . '/content/' . $slug . '.php';
	$page = $root . '/' . $slug . '.php';
	if (!is_file($path)) {
		fwrite(STDERR, "FAIL: missing content $slug\n");
		$fail++;
		continue;
	}
	if (!is_file($page)) {
		fwrite(STDERR, "FAIL: missing page $slug\n");
		$fail++;
		continue;
	}
	$guide = include $path;
	if (!is_array($guide) || empty($guide['sections'])) {
		fwrite(STDERR, "FAIL: bad guide structure $slug\n");
		$fail++;
		continue;
	}
	$hasVerified = !empty($guide['verified']);
	fwrite(STDOUT, "PASS: $slug sections=" . count($guide['sections']) . " verified_list=" . ($hasVerified ? 'yes' : 'no') . "\n");
	$pass++;
}
fwrite(STDOUT, "$pass guides ok, $fail failed\n");
exit($fail > 0 ? 1 : 0);

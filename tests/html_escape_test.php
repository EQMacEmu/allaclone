<?php
/**
 * HTML attribute escaping + PHP_SELF path hardening (no production DB required).
 * Run: docker run --rm -v "$PWD:/app" -w /app php:8.3-cli php tests/html_escape_test.php
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

function render_search_input($iname, $phpSelf = '/allaclone/items.php') {
	return "<form method='GET' action='" . html_attr($phpSelf) . "'>"
		. "<input type='text' value=\"" . html_attr($iname) . "\" name='iname' />"
		. "</form>";
}

// --- html_attr payload matrix ---
$payloads = array(
	'double quote' => 'Sword of "Truth"',
	'single quote' => "O'Malley",
	'html tags' => '<script>alert(1)</script>',
	'attr break' => '"><img src=x onerror=alert(1)>',
	'ampersand query' => 'foo&bar=1',
	'unicode' => "Café résumé — 日本語",
	'punct name' => "King's Relic, Vol. 2",
);

foreach ($payloads as $label => $value) {
	$escaped = html_attr($value);
	assert_true(strpos($escaped, '<') === false, "html_attr strips/escapes < ($label)");
	assert_true(strpos($escaped, '>') === false || strpos($escaped, '&gt;') !== false || strpos($value, '>') === false,
		"html_attr handles > ($label)");
	assert_true(strpos($escaped, '"') === false, "html_attr escapes double quotes ($label)");
	$html = render_search_input($value);
	assert_true(preg_match('/value="[^"]*"/', $html) === 1, "rendered value attr stays closed ($label)");
	assert_true(strpos($html, '<script>') === false, "rendered HTML has no raw script tag ($label)");
	// Round-trip: htmlspecialchars_decode should recover the original (UTF-8 safe payloads).
	$decoded = htmlspecialchars_decode($escaped, ENT_QUOTES);
	assert_true($decoded === $value, "html_attr preserves meaning after decode ($label)");
}

// Attribute-breaking payload must not close the attribute early
$break = '"><script>alert(1)</script>';
$html = render_search_input($break);
assert_true(strpos($html, 'value="&quot;&gt;') !== false || strpos($html, 'value="&#039;') !== false
	|| (strpos($html, 'value="') !== false && strpos($html, '<script>') === false),
	'attr-break payload does not inject raw markup');
assert_true(substr_count($html, '<input') === 1, 'attr-break does not inject extra input');

// Ampersand in query-like search text
$html = render_search_input('a&b');
assert_true(strpos($html, 'value="a&amp;b"') !== false, 'ampersand escaped in value');

// Nested-directory PHP_SELF remains usable and escaped
$nested = '/takp/allaclone/items.php';
$html = render_search_input('Cloth Cap', $nested);
assert_true(strpos($html, "action='/takp/allaclone/items.php'") !== false, 'nested SCRIPT_NAME preserved in action');

// --- safe_script_path ---
assert_true(safe_script_path('/allaclone/items.php') === '/allaclone/items.php', 'accepts nested absolute script path');
assert_true(safe_script_path('/items.php') === '/items.php', 'accepts root script path');
$trav = safe_script_path('/allaclone/../etc/passwd');
assert_true($trav === 'index.php' || ($trav === 'passwd' && strpos($trav, '..') === false),
	'rejects path traversal (..)');
$bad = safe_script_path('/foo/bar"><x>.php');
assert_true($bad === 'index.php', 'attribute-breaking SCRIPT_NAME falls back to index.php');
assert_true(strpos($bad, '"') === false && strpos($bad, '<') === false, 'fallback has no attr-breaking chars');
$relPath = safe_script_path('items.php');
assert_true($relPath === 'items.php', 'basename-only SCRIPT_NAME accepted as fallback');
assert_true(safe_script_path('') === 'index.php', 'empty falls back to index.php');

// --- Source guards: vulnerable patterns must be gone from fixed pages ---
$files = array('items.php', 'npcs.php', 'factions.php', 'advnpcs.php', 'recipes.php');

$root = dirname(__DIR__);
foreach ($files as $rel) {
	$src = file_get_contents($root . DIRECTORY_SEPARATOR . $rel);
	assert_true($src !== false, "readable $rel");
	assert_true(strpos($src, 'html_attr($iname)') !== false, "$rel uses html_attr(\$iname)");
	assert_true(strpos($src, 'html_attr($PHP_SELF)') !== false, "$rel escapes PHP_SELF via html_attr");
	assert_true(!preg_match('/value=\\\\?"\$iname\\\\?"/', $src), "$rel has no raw \$iname value interpolation");
}

$items = file_get_contents($root . DIRECTORY_SEPARATOR . 'items.php');
assert_true(strpos($items, 'html_attr($ieffect)') !== false, 'items.php escapes ieffect');
assert_true(strpos($items, 'html_attr($iresistsvalue)') !== false, 'items.php escapes iresistsvalue');

$recipes = file_get_contents($root . DIRECTORY_SEPARATOR . 'recipes.php');
assert_true(strpos($recipes, 'html_attr($minskill)') !== false, 'recipes.php escapes minskill');
assert_true(strpos($recipes, 'html_attr($maxskill)') !== false, 'recipes.php escapes maxskill');

if ($failed > 0) {
	fwrite(STDERR, "$failed assertion(s) failed\n");
	exit(1);
}
echo "All HTML escape assertions passed.\n";
exit(0);

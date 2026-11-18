<?php

/** Outputs an error message if a database error occurs
 */
function message_die($t1, $t2, $t3, $t4) {
	// Log details server-side; do not expose SQL or DB errors to clients.
	error_log('Allaclone DB error: ' . $t1 . ' | ' . $t2 . ' | ' . $t3 . ' | ' . $t4);
	print "<p><table width='100%' border=0>\n";
	print "<tr><td align=left><b>Database error</b></td></tr>\n";
	print "<tr><td align=left>A database error occurred. Please try again later.</td></tr>\n";
	print "<tr><td align=center><font color=red><b>Have you sourced the eqbrowser tables in your database ?</b></font></td></tr>\n";
	print "</table></p>\n";
	exit;
}

/**
 * Escape a value for use inside an HTML attribute (double- or single-quoted).
 */
function html_attr($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Derive a safe relative script path for form actions / self-links.
 * Prefers SCRIPT_NAME; rejects path traversal and attribute-breaking characters.
 * Falls back to basename so nested installs still self-post.
 */
function safe_script_path($scriptName = null) {
	if ($scriptName === null) {
		$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
	}
	$script = (string)$scriptName;
	if ($script !== '' && $script[0] === '/' && strpos($script, '..') === false
		&& preg_match('#^/[A-Za-z0-9._/-]+$#', $script)) {
		return $script;
	}
	$base = basename($script);
	if ($base !== '' && preg_match('#^[A-Za-z0-9._-]+$#', $base)) {
		return $base;
	}
	return 'index.php';
}

/**
 * Coerce a request value to an integer. Non-integer strings return $default
 * (blocks values like "5 OR 1=1").
 */
function sql_int($value, $default = 0) {
	if ($value === null || $value === '') {
		return (int)$default;
	}
	if (is_int($value)) {
		return $value;
	}
	$s = trim((string)$value);
	if (preg_match('/^-?\d+$/', $s)) {
		return (int)$s;
	}
	return (int)$default;
}

/**
 * Allowlist a SQL identifier fragment (column name, etc.).
 */
function sql_ident($value, array $allowlist) {
	return in_array($value, $allowlist, true) ? $value : '';
}

/**
 * Allowlist a SQL comparison operator.
 */
function sql_op($value, ?array $ops = null) {
	if ($ops === null) {
		$ops = array('>=', '<=', '=', '<', '>');
	}
	return in_array($value, $ops, true) ? $value : '';
}

/**
 * Apply LIKE wildcard substitutions then escape for mysqli.
 * Default map treats spaces and underscores as wildcards (wiki-compatible).
 *
 * @param mysqli $db
 * @param string $value
 * @param array|null $map character => replacement
 */
function sql_like($db, $value, $map = null) {
	if ($map === null) {
		$map = array(' ' => '%', '_' => '%');
	}
	$q = strtr((string)$value, $map);
	return mysqli_real_escape_string($db, $q);
}

/** 
 * Runs '$query' and returns the value of '$field' of the first (arbitrarily) found row
 * If no row is selected by '$query', returns an empty string
 */
function GetFieldByQuery($field, $query) {
	global $db;
	$QueryResult = mysqli_query($db, $query);
	if ($QueryResult === false) {
		message_die('GetFieldByQuery', 'MYSQL_QUERY', $query, mysqli_error($db));
	}
	if (mysqli_num_rows($QueryResult) > 0) {
		$rows = mysqli_fetch_array($QueryResult);
		$Result = $rows[$field];
	} else
		$Result = "";

	return $Result;
}

/**
 * Runs '$query' and returns the first (arbitrarily) found row.
 */
function GetRowByQuery($query) {
	global $db;
	$QueryResult = mysqli_query($db, $query);
	if ($QueryResult === false) {
		message_die('GetRowByQuery', 'MYSQL_QUERY', $query, mysqli_error($db));
	}
	$Result = mysqli_fetch_array($QueryResult);

	return $Result;
}

function mysqli_field_name($result, $field_offset)
{
    $properties = mysqli_fetch_field_direct($result, $field_offset);
    return is_object($properties) ? $properties->name : null;
}

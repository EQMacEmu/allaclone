<?php
/**
 * Generic stub page. Set $epic_slug before including.
 */
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry($epic_slug);
if (!$meta) {
	header('Location: ' . $root_url . 'guides/epics/');
	exit;
}

$Title = $meta['class'] . ' Epic 1.0';
include($includes_dir . 'headers.php');
epic_render_stub_page($db, $meta);
echo "</div>\n";
include($includes_dir . 'footers.php');

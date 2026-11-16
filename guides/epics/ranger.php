<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('ranger');
$guide = include __DIR__ . '/content/ranger.php';

$Title = 'Ranger Epic 1.0 — Swiftwind and Earthcaller';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

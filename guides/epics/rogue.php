<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('rogue');
$guide = include __DIR__ . '/content/rogue.php';

$Title = 'Rogue Epic 1.0 — Ragebringer';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

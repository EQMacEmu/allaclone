<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('paladin');
$guide = include __DIR__ . '/content/paladin.php';

$Title = 'Paladin Epic 1.0 — Fiery Defender';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

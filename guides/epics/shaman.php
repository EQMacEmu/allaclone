<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('shaman');
$guide = include __DIR__ . '/content/shaman.php';

$Title = 'Shaman Epic 1.0 — Spear of Fate';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

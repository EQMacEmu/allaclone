<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('warrior');
$guide = include __DIR__ . '/content/warrior.php';

$Title = 'Warrior Epic 1.0 — Blade of Tactics & Blade of Strategy';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

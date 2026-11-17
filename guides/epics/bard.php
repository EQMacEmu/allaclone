<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('bard');
$guide = include __DIR__ . '/content/bard.php';

$Title = 'Bard Epic 1.0 — Singing Short Sword';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('cleric');
$guide = include __DIR__ . '/content/cleric.php';

$Title = 'Cleric Epic 1.0 — Water Sprinkler of Nem Ankh';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

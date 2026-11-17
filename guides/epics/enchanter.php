<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('enchanter');
$guide = include __DIR__ . '/content/enchanter.php';

$Title = 'Enchanter Epic 1.0 — Staff of the Serpent';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

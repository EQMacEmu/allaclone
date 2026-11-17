<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('magician');
$guide = include __DIR__ . '/content/magician.php';

$Title = 'Magician Epic 1.0 — Orb of Mastery';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

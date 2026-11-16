<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('necromancer');
$guide = include __DIR__ . '/content/necromancer.php';

$Title = 'Necromancer Epic 1.0 — Scythe of the Shadowed Soul';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('wizard');
$guide = include __DIR__ . '/content/wizard.php';

$Title = 'Wizard Epic 1.0 — Staff of the Four';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

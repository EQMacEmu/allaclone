<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('beastlord');
$guide = include __DIR__ . '/content/beastlord.php';

$Title = 'Beastlord Epic 1.0 — Claws of the Savage Spirit';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

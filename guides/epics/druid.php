<?php
require __DIR__ . '/_bootstrap.php';

$meta = epic_catalog_entry('druid');
$guide = include __DIR__ . '/content/druid.php';

$Title = 'Druid Epic 1.0 — Nature Walker\'s Scimitar';
include($includes_dir . 'headers.php');
epic_render_guide_page($db, $meta, $guide);
echo "</div>\n";
include($includes_dir . 'footers.php');

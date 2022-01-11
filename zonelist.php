<?php
$Title = "Zone List";
include('./includes/config.php');
include($includes_dir . 'constantes.php');
include($includes_dir . 'headers.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php'); ?>

<div class="container zonelist">
	<ul class="expansion-list">
		<li><a href="static/antonica.php">Antonica</a></li>
		<li><a href="static/odus.php">Odus</a></li>
		<li><a href="static/faydwer.php">Faydwer</a></li>
		<li><a href="static/planes.php">Old World Planes</a></li>
		<li><a href="static/kunark.php">Ruins of Kunark</a></li>
		<li><a href="static/velious.php">Scars of Velious</a></li>
		<li><a href="static/luclin.php">Shadows of Luclin</a></li>
		<li><a href="static/power.php">The Planes of Power</a></li>
	</ul>
</div>

<?php include($includes_dir . "footers.php"); ?>
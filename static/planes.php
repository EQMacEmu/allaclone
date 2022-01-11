<?php
$Title = "Outer Planes";
include('../includes/config.php');
include('../includes/constantes.php');
include('../includes/headers.php');
include('../includes/mysql.php');
include('../includes/functions.php');
?>
<div class="container zonelist">
	<h3>Zones</h3>
	<ul class="zone-list">
		<li><a href=../zone.php?name=fearplane>Plane of Fear</a></li>
		<li><a href=../zone.php?name=hateplane>Plane of Hate</a></li>
		<li><a href=../zone.php?name=airplane>Plane of Air</a></li>
	</ul>
</div>
<?
include('../includes/footers.php');
?>
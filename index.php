<?php

	include('./revision.php');
	include('./includes/config.php');
	include($includes_dir.'constantes.php');

	include($includes_dir.'mysql.php');
	include($includes_dir.'functions.php');

	$Title="Welcome to TAKP AllaClone!";
	include($includes_dir.'headers.php');
    echo'<img style="max-height:50%; margin:0 auto; display: block;" src="'. $root_url .'images/eqmac.jpg" />';


	if (file_exists("design/index.html")) { include("design/index.html"); }
	
	include($includes_dir."footers.php");
?>

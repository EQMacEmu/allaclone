<?php

include('./revision.php');
include('./includes/config.php');
include($includes_dir . 'constantes.php');

include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');
include($includes_dir . 'headers.php');
?>
<h2>The Al'Kabor Project Allaclone</h2>
<p><img id="box-art" src='<?php echo $root_url; ?>/images/eqmac.jpg' />
	The Al'Kabor Project aims to replicate EverQuest's Al'Kabor server, which was the server for the Macintosh version of the game. The Al'Kabor server was
	perpetually stuck in the early Planes of Power expansion, receiving no content updates for the decade of its life.
	Many bugs/quirks from this time period have been replicated which gives the server a certain charm.</p>

<?php
include($includes_dir . "footers.php");

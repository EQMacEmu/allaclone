<?php

include('./revision.php');
include('./includes/config.php');
include($includes_dir . 'constantes.php');

include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');
include($includes_dir . 'headers.php');
?>
<div class='container home'>
	<p><a href="https://takproject.net/">The Al'Kabor Project</a> aims to replicate EverQuest's Al'Kabor server, which was the Macintosh version of the game. The server was
		perpetually stuck in the early Planes of Power expansion, receiving no content updates for the decade of its life.
		Many bugs/quirks from this time period have been replicated which gives the server a certain charm, and one of the most "classic" or era-appropriate experiences available today.</p>
	<p>This website is a tool for you to look up items, spells, NPC entities, recipes, etc.</p>
	<img id="box-art" src='<?php echo $root_url; ?>/images/eqmac.jpg' />
</div>
</div>
<?php include($includes_dir . "footers.php");

<div class="nav-wrapper">
	<ul class="navbar" role="navigation" aria-label="Main">
		<li><a href="<?php echo $root_url; ?>items.php">Items</a></li>
		<li><a href="<?php echo $root_url; ?>spells.php">Spells</a></li>
		<li><a href="<?php echo $root_url; ?>factions.php">Factions</a></li>
		<li><a href="<?php echo $root_url; ?>recipes.php">Recipes</a></li>
		<li><a href="<?php echo $root_url; ?>advnpcs.php">NPCs</a></li>
		<li><a href="<?php echo $root_url; ?>pets.php">Pets</a></li>
		<li><a href="<?php echo $root_url; ?>zonelist.php">Zones</a></li>
<?php if (!empty($AllowQuestsNPC)) : ?>
		<li><a href="<?php echo $root_url; ?>quests/zones.php">Quests</a></li>
<?php endif; ?>
<?php if (!empty($AllowEpics)) : ?>
		<li><a href="<?php echo $root_url; ?>guides/epics/">Epics</a></li>
<?php endif; ?>
	</ul>
</div>

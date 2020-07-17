<div class="nav-wrapper">
    <ul class="navbar">
        <li><a href="items.php">Items</a></li>
        <li><a href="spells.php">Spells</a></li>
        <li><a href="factions.php">Factions</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <?php // if ($EnableNews) :?>
        <!-- <li><a href = "<?php //echo $root_url;?>news.php"
        >News</a></li> -->
        <?php // endif;?>
        <li><a href="#" class="toggle">Bestiary</a>
            <ul class="dropdown-menu">
                <li><a href="<?php echo $root_url; ?>advnpcs.php">Advanced
                        NPC Search</a></li>
                <li><a
                        href="<?php echo $root_url; ?>pets.php">Pets</a>
                </li>
            </ul>
        </li>
        <li><a href="#" class="toggle">Zones</a>
            <ul class="dropdown-menu">
                <li><a href="<?php echo $root_url; ?>zonelist.php">By
                        Continent</a></li>
                <li><a
                        href="<?php echo $root_url; ?>zoneslevels.php">By
                        Level</a></li>
                <li><a
                        href="<?php echo $root_url; ?>zones.php">Populated</a>
                </li>
            </ul>
        </li>
        <li><a href="#" class="toggle">Resources</a>
            <ul class="dropdown-menu">
                <li><a href="http://wiki.takp.info">TAKP Wiki</a></li>
                <li><a href="http://www.takproject.net/forums/index.php?forums/changelog.24/" target="_blank">TAKP
                        Change Log</a></li>
            </ul>
        </li>
    </ul>
</div>
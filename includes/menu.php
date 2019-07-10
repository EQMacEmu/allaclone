<?php  if($AllowQuestsNPC) : ?>
<a href='".$root_url."quests/zones.php'>Quests by Zone</a>
<?php endif; ?>

<?php if($ShowAccount) : ?>
<a href="<?php echo $root_url; ?>accounts.php">Player Accounts</a>
<?php endif; ?>

<?php if($ShowCharacters) : ?>
<a href="<?php echo $root_url; ?>chars.php">Player Characters</a>
<?php endif; ?>


<?php if($UseZAMSearch) : ?>
    <div class="zam-search">
        <script type="text/javascript">
            var zam_searchbox_site = "everquest";
            var zam_searchbox_format = "160x130";
        </script>
        <script type="text/javascript" src="http://zam.zamimg.com/j/searchbox.js"></script>
    </div>
<?php endif; ?>
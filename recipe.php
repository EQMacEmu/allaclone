<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');

$id   = (isset($_GET['id']) ? $_GET['id'] : '');

if (!is_numeric($id)) {
	header("Location: recipes.php");
	exit();
}

$Title = "Recipe : " . str_replace('_', ' ', GetFieldByQuery("name", "SELECT name FROM $tbtradeskillrecipe WHERE id=$id"));
include($includes_dir . 'headers.php');
include($includes_dir . 'functions.php');

if (!isset($id)) {
	print "<script>document.location=\"index.php\";</script>";
}

$query = "SELECT *
			FROM $tbtradeskillrecipe
			WHERE id=$id";

$result = mysqli_query($db, $query) or message_die('recipe.php', 'MYSQL_QUERY', $query, mysqli_error($db));
$recipe = mysqli_fetch_array($result);

print "<div class='container recipes'>";
print "<table border=0 width=0%>";
print "<tr><td nowrap><b>Recipe : </b></td><td nowrap>" . ucfirstwords(str_replace('_', ' ', $recipe["name"])) . "</td></tr>";
print "<tr><td nowrap><b>Tradeskill : </b></td><td nowrap>" . ucfirstwords($dbskills[$recipe["tradeskill"]]) . "</td></tr>";
if ($recipe["skillneeded"] > 0) {
	print "<tr><td nowrap><b>Skill needed : </b></td><td nowrap>" . $recipe["skillneeded"] . "</td></tr>";
}
print "<tr><td nowrap><b>Trivial at : </b></td><td nowrap>" . $recipe["trivial"] . "</td></tr>";
if ($recipe["nofail"] > 0) {
	print "<tr><td nowrap colspan=2>This recipe cannot fail.</td></tr>";
}
if ($recipe["notes"] != "") {
	print "<tr><td cospan=2><b>Notes : </b>" . $recipe["notes"] . "</td></tr>";
}
// results containers
$query = "SELECT $tbtradeskillrecipeentries.*,$tbitems.Name, $tbitems.icon
			FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries
			LEFT OUTER JOIN $tbitems on $tbitems.id = $tbtradeskillrecipeentries.item_id
			WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id
			  AND $tbtradeskillrecipeentries.recipe_id=$id
			  AND $tbtradeskillrecipeentries.item_id=$tbitems.id
			  AND $tbtradeskillrecipeentries.iscontainer=1";
$query = "SELECT tradeskill_recipe_entries.item_id, items.Name, items.icon
	FROM tradeskill_recipe
	JOIN tradeskill_recipe_entries ON tradeskill_recipe.id = tradeskill_recipe_entries.recipe_id
	LEFT OUTER JOIN items ON items.id = tradeskill_recipe_entries.item_id
	WHERE tradeskill_recipe_entries.iscontainer = 1
	AND tradeskill_recipe_entries.recipe_id = $id;";

$result = mysqli_query($db, $query) or message_die('recipe.php', 'MYSQL_QUERY', $query, mysqli_error($db));

if (mysqli_num_rows($result) > 0) {
	print "<tr class=myline height=6><td colspan=2></td><tr>";
	print "<tr><td nowrap><b>Containers needed for the combine </b>";
	print "<ul>";
	while ($row = mysqli_fetch_array($result)) {

		$item_id = $row["item_id"];
		if ($row["Name"]) {
			print "<img src='" . $icons_url . "item_" . $row["icon"] . ".gif' align='left' width='15' height='15'/>" .
				"<a href=item.php?id=$item_id id=$item_id data-item-id=\"$item_id\" class=\"item-link\">" .
				str_replace("_", " ", $row["Name"]) . "</a><br>";
			if ($recipe["replace_container"] == 1) {
				print " (this container will disappear after combine)";
			}
		} else {
			if (array_key_exists($item_id, $worldcontainer)) {
				print $worldcontainer[$item_id] . "<br/>";
			}
		}
	}
	print "</ul></td></tr>";
}


// results success
$query = "SELECT $tbtradeskillrecipeentries.*,$tbitems.*,$tbitems.id AS item_id
			FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries,$tbitems
			WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id
			  AND $tbtradeskillrecipeentries.recipe_id=$id
			  AND $tbtradeskillrecipeentries.item_id=$tbitems.id
			  AND $tbtradeskillrecipeentries.successcount>0";

$result = mysqli_query($db, $query) or message_die('recipe.php', 'MYSQL_QUERY', $query, mysqli_error($db));
if (mysqli_num_rows($result) > 0) {
	print "<tr class=myline height=6><td colspan=2></td><tr>";
	print "<tr><td nowrap><b>Items resulting of a <FONT COLOR='#00FF00'> successfull combine </FONT></b><ul>";
	while ($row = mysqli_fetch_array($result)) {

		$item_id = $row["item_id"];
		print "<img src='" . $icons_url . "item_" . $row["icon"] . ".gif' align='left' width='15' height='15'/>" .
			"<a href=item.php?id=$item_id id=" . ($item_id * 110) . " data-item-id=\"$item_id\" class=\"item-link\">" .
			str_replace("_", " ", $row["Name"]) . "</a> x" . $row["successcount"] . " <br>";
	}
	print "</ul></td></tr>";
}

if ($recipe["nofail"] == 0) {
	// results fail
	$query = "SELECT $tbtradeskillrecipeentries.*,$tbitems.*,$tbitems.id AS item_id
				FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries,$tbitems
				WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id
				  AND $tbtradeskillrecipeentries.recipe_id=$id
				  AND $tbtradeskillrecipeentries.item_id=$tbitems.id
				  AND $tbtradeskillrecipeentries.failcount>0";

	$result = mysqli_query($db, $query) or message_die('recipe.php', 'MYSQL_QUERY', $query, mysqli_error($db));
	if (mysqli_num_rows($result) > 0) {
		print "<tr class=myline height=6><td colspan=2></td><tr>";
		print "<tr><td nowrap><b>Items resulting of a <FONT COLOR='#FF0000'> failed combine </FONT></b><ul>";
		while ($row = mysqli_fetch_array($result)) {

			$item_id = $row["item_id"];
			print "<img src='" . $icons_url . "item_" . $row["icon"] . ".gif' align='left' width='15' height='15'/>" .
				"<a href=item.php?id=$item_id id=" . ($item_id * 10) . " data-item-id=\"$item_id\" class=\"item-link\">" .
				str_replace("_", " ", $row["Name"]) . "</a> x" . $row["failcount"] . " <br>";
		}
		print "</td></tr>";
	}
}

// components
$query = "SELECT $tbtradeskillrecipeentries.*,$tbitems.*,$tbitems.id AS item_id
			FROM $tbtradeskillrecipe,$tbtradeskillrecipeentries,$tbitems
			WHERE $tbtradeskillrecipe.id=$tbtradeskillrecipeentries.recipe_id
			AND $tbtradeskillrecipeentries.recipe_id=$id
			AND $tbtradeskillrecipeentries.item_id=$tbitems.id
			AND $tbtradeskillrecipeentries.iscontainer=0
			AND $tbtradeskillrecipeentries.componentcount>0";

$result = mysqli_query($db, $query) or message_die('recipe.php', 'MYSQL_QUERY', $query, mysqli_error($db));
if (mysqli_num_rows($result) > 0) {
	print "<tr class=myline height=6><td colspan=2></td><tr>";
	print "<tr><td nowrap><b>Components needed : </b><ul>";

	while ($row = mysqli_fetch_array($result)) {

		$item_id = $row["item_id"];
		print "<img src='" . $icons_url . "item_" . $row["icon"] . ".gif' align='left' width='15' height='15'/>" . "<a href=item.php?id=" . $row["item_id"] . " id=" . ($row["item_id"] * 100) . " data-item-id=\"$item_id\" class=\"item-link\">" .
			str_replace("_", " ", $row["Name"]) . "</a> x " . $row["componentcount"] . " <br>";
	}
	print "</td></tr>";
}
print "</table></div></div>";

include($includes_dir . "footers.php");

<?php
	$Title="Search Recipes";
	include('./includes/constantes.php');
	include('./includes/config.php');
	include($includes_dir.'mysql.php');
	include($includes_dir.'headers.php');
	include($includes_dir.'functions.php');

	$minskill = (isset($_GET['minskill']) ? $_GET['minskill'] : 0);
	$maxskill = (isset($_GET['maxskill']) ? $_GET['maxskill'] : 0);
	$isearch = (isset($_GET['isearch']) ? $_GET['isearch'] : '');
	$iname = (isset($_GET[  'iname']) ? $_GET[  'iname'] : '');
	$iskill = (isset($_GET[  'iskill']) ? $_GET[  'iskill'] : 0);

	if (!isset($maxskill)) { $maxskill=0; }
	if (!isset($minskill)) { $minskill=0; }
	if (!ctype_digit($maxskill)) { $maxskill=0; }
	if (!ctype_digit($minskill)) { $minskill=0; }
	if ($minskill>$maxskill) { $tempskill=$minskill; $minskill=$maxskill; $maxskill=$tempskill; }

	echo "<div class='container recipes'>";
	echo "<form method='GET' action=$PHP_SELF>";
    echo "<div class='recipe-container'>";
	echo "<div class='recipe-search'><b>Name : </b><input type=text value=\"$iname\" size=30 name=iname></div>";
	echo "<div class='recipe-search'><b>Tradeskill : </b>";
	echo SelectTradeSkills("iskill",$iskill);
    echo "</div>";
	echo "<div class='recipe-search'><b>Min trivial skill : </b><input type=text value=\"$minskill\" size=30 name=minskill></div>";
	echo "<div class='recipe-search'><b>Max trivial skill : </b><input type=text value=\"$maxskill\" size=30 name=maxskill></div></div>";
	echo "<input type='submit' value='Search' name='isearch' class='form'/> <input type='reset' value='Reset' class='form'/>";
    echo "</form>";
    echo "<div class='recipe-results'>";
	if (isset($isearch) && $isearch != "")
	{
		if ($minskill>$maxskill) { $tempskill=$minskill; $minskill=$maxskill; $maxskill=$tempskill; }
		$query="SELECT $tbtradeskillrecipe.id,$tbtradeskillrecipe.name,
				$tbtradeskillrecipe.tradeskill,$tbtradeskillrecipe.trivial
				FROM $tbtradeskillrecipe";
		$s="WHERE";
		if ($iname!="")
		{
			$iname=str_replace(' ','%',addslashes($iname));
			$query.=" $s $tbtradeskillrecipe.name like '%".$iname."%'"; $s="AND"; 
		}
		if ($iskill>0) { $query.=" $s $tbtradeskillrecipe.tradeskill=$iskill"; $s="AND"; }
		if ($minskill>0) { $query.=" $s $tbtradeskillrecipe.trivial>=$minskill"; $s="AND"; }
		if ($maxskill>0) { $query.=" $s $tbtradeskillrecipe.trivial<=$maxskill"; $s="AND"; }
		$query.=" ORDER BY $tbtradeskillrecipe.name";
		$result=mysqli_query($db, $query) or message_die('recipes.php','MYSQL_QUERY',$query,mysqli_error($db));

		if(isset($result))
		{
			PrintQueryResults($result, $MaxItemsReturned, "recipe.php", "recipe", "recipes", "id", "name", "trivial", "trivial at level", "tradeskill");
		}
	}
    echo "</div></div>";

	include($includes_dir."footers.php");

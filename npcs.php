<?php
/** If the parameter 'isearch' is set, queries for the factions matching 'iname' and displays them, along with a faction display form.
 *    If only one and only one faction is found then this faction is displayed.
 *  If 'isearch' is not set, displays a search faction form.
 *  If 'iname' is not set then it is equivalent to searching for all factions.
 *  For compatbility with Wikis and multi-word searches, underscores are treated as jokers in 'iname'.
 */
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir.'mysql.php');
include($includes_dir.'functions.php');

$isearch = (isset($_GET['isearch']) ? $_GET['isearch'] : '');
$iname   = (isset($_GET[  'iname']) ? $_GET[  'iname'] : '');

if($isearch != "")
{
	if($iname == "")
	{
		$name = "";
	}
	else
	{
		$name = addslashes($iname);
	}
	$Query="SELECT $tbnpctypes.id,$tbnpctypes.name
		FROM $tbnpctypes
		WHERE 1=1";
	if($name != "")
	{
		$name = str_replace('`', '-', str_replace('_', '%', str_replace(' ', '%', $name)));
		$Query .= " AND $tbnpctypes.Name like '%$name%'";
	}
	if($HideInvisibleMen)
	{
		$Query .= " AND $tbnpctypes.race != 127 AND $tbnpctypes.race != 240";
	}
	$Query.=" ORDER BY $tbnpctypes.Name, $tbnpctypes.id LIMIT ".(LimitToUse($MaxNpcsReturned) + 1);

	$QueryResult = mysqli_query($db, $Query) or message_die('npcs.php','MYSQL_QUERY',$Query,mysqli_error($db));

	if(mysqli_num_rows($QueryResult) == 1)
	{
		$row = mysqli_fetch_array($QueryResult);
		header("Location: npc.php?id=".$row["id"]);
		exit();
	}
}


/** Here the following holds :
 *    $QueryResult : NPCs queried for if any query was issued, otherwise it is not defined
 *    $iname : previously-typed query, or empty by default
 *    $isearch is set if a query was issued
 */

$Title="NPCs search";
$XhtmlCompliant = TRUE;
include($includes_dir.'headers.php');

echo "<form method='GET' action='".$PHP_SELF."'>";
echo "<input type='text' value=\"$iname\" size='30' name='iname' placeholder='NPC Name' />";
echo "<input type='submit' value='Search' name='isearch' />";
echo "</form>";

if(isset($QueryResult)) {
    echo '<div class="flex">';
    PrintQueryResults($QueryResult, $MaxNpcsReturned, "npc.php", "npc", "npcs", "id", "name");
    echo '</div>';
}

include($includes_dir."footers.php");
?>

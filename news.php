<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir.'mysql.php');
$Title="News and Updates";
include($includes_dir.'headers.php');
include($includes_dir.'functions.php');

if ($EnableNews==FALSE)
{
	print "Access forbidden";
	die;
}
$query="SELECT * 
        FROM $tbnews
        ORDER BY DATE desc";
$result=mysqli_query($db, $query) or message_die('news.php','MYSQL_QUERY',$query,mysqli_error($db));

while ($res=mysqli_fetch_array($result))
{
	print "<article class='news'>";
    print "<h2>" . $res["title"] . "</h2>";
    print "<small class='news-post-date'><strong>" . WriteDate($res["date"]) . "</strong></small>";
    print "<p>" . $res["content"] . "</p>";
    print "</article>";
}

include($includes_dir."footers.php");
?>
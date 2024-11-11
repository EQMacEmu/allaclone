<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir.'mysql.php');
include($includes_dir.'functions.php');

$id   = (isset($_GET[  'id']) ? $_GET[  'id'] : '');

if (!is_numeric($id) || $DisplaySpawnGroupInfo==FALSE)
{
	header("Location: index.php");
	exit();
}
$query="SELECT $tbspawngroup.name AS sgname, $tbspawn2.*, 
        $tbzones.long_name AS zone, $tbzones.short_name AS spawnzone
        FROM $tbspawngroup,$tbspawn2,$tbzones
        WHERE $tbspawngroup.id=$id
          AND $tbspawn2.spawngroupID=$tbspawngroup.id
          AND $tbspawn2.zone=$tbzones.short_name";
$result=mysqli_query($db, $query) or message_die('spawngroup.php','MYSQL_QUERY',$query,mysqli_error($db));
$spawn=mysqli_fetch_array($result);
$Title=$spawn["sgname"]; #." (".$spawn["zone"]." : ".floor($spawn["y"]).",".floor($spawn["x"]).",".floor($spawn["z"]).")";
$x=floor($spawn["x"]);
$y=floor($spawn["y"]);
$z=floor($spawn["z"]);

include($includes_dir.'headers.php');

if (!isset($id) || $id=='') { print "<script>document.location=\"index.php\";</script>"; }

print "<div><tr valign=top><td width=50% nowrap>\n";
$query="SELECT $tbspawnentry.chance,$tbnpctypes.name,$tbnpctypes.id
        FROM $tbspawnentry,$tbnpctypes
        WHERE $tbspawnentry.spawngroupID=$id
          AND $tbspawnentry.npcID=$tbnpctypes.id 
        ORDER BY $tbspawnentry.chance DESC
        ";
$result=mysqli_query($db, $query) or message_die('spawngroup.php','MYSQL_QUERY',$query,mysqli_error($db));
print "<b>NPCs composing that spawngroup :</b>";
if (mysqli_num_rows($result)>0) {
  while ($row=mysqli_fetch_array($result)) {
    print "<li><a href=npc.php?id=".$row["id"].">".$row["name"]."</a> (".$row["chance"]."%)"; 
  }
}
print "</td><br/><td width=50% nowrap>";
print "<b>NPCs spawning around that spawngroup : </b><br>(Max range : $SpawngroupAroundRange)<ul>";
$myrange=$SpawngroupAroundRange*$SpawngroupAroundRange; // precalculate, saves some mysql time
$query="SELECT $tbspawnentry.chance,$tbspawn2.x AS x, $tbspawn2.y AS y, $tbspawn2.z AS z,
               $tbnpctypes.name,$tbnpctypes.id,
               $tbspawngroup.id AS sgid,$tbspawngroup.name AS sgname
        FROM $tbspawnentry,$tbnpctypes,$tbspawngroup,$tbspawn2
        WHERE $tbspawn2.zone='".$spawn["spawnzone"]."'
          AND $tbspawn2.spawngroupID=$tbspawngroup.id
          AND $tbspawn2.spawngroupID=$tbspawnentry.spawngroupID
          AND $tbspawnentry.npcID=$tbnpctypes.id
          AND(($x-$tbspawn2.x)*($x-$tbspawn2.x))+(($y-$tbspawn2.y)*($y-$tbspawn2.y))<$myrange
          AND (abs(z-$tbspawn2.z)<20)
          AND $tbspawngroup.id!=$id
        ORDER BY sgid ASC, $tbnpctypes.name ASC
        ";
$result=mysqli_query($db, $query) or message_die('spawngroup.php','MYSQL_QUERY',$query,mysqli_error($db));
$sg=0;
if (mysqli_num_rows($result)>0) {
  while ($row=mysqli_fetch_array($result)) {
    if ($sg!=$row["sgid"]) {
      $sg=$row["sgid"];
      print "</ul><li><a href=$PHP_SELF?id=".$row["sgid"].">".$row["sgname"]."</a>, range=";
      print floor(sqrt(($x-$row["x"])*($x-$row["x"])+($y-$row["y"])*($y-$row["y"])));
      print " (".floor($row["y"]).",".floor($row["x"]).",".floor($row["z"]).")<ul>";
    }
    print "<li><a href=npc.php?id=".$row["id"].">".$row["name"]."</a> (".$row["chance"]."%)";
  }
} else {
  print "None... ";
}
print "</ul></td></tr></div>";

print "</div>";
include($includes_dir."footers.php");
?>

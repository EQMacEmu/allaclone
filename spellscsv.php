<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir.'mysql.php');
include($includes_dir.'functions.php');
include($includes_dir.'spell.inc.php');

$iname   = (isset($_GET['iname']) ? $_GET['iname'] : '');
$iclass  = isset($_GET['iclass']) ? sql_int($_GET['iclass'], 0) : 0;
$ieffect = isset($_GET['ieffect']) ? sql_int($_GET['ieffect'], -1) : -1;
$imin    = isset($_GET['imin']) ? sql_int($_GET['imin'], 0) : 0;
$imax    = isset($_GET['imax']) ? sql_int($_GET['imax'], 0) : 0;
$allowed_spell_classes = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16);
if (!in_array($iclass, $allowed_spell_classes, true)) {
  $iclass = 0;
}

if ($imin <= 0) { $imin = 1; }
if ($imax <= 0) { $imax = $ServerMaxLevel; }
$query="SELECT $tbspells.*
        FROM $tbspells
        WHERE 1=1";
if ($iname!="") { 
  $query.=" AND name like '%".sql_like($db, $iname, array(' ' => '%'))."%'"; 
}
if ($ieffect >= 0) { 
  $query.=" AND (";
  $s="";
  for ($i=1; $i<=12; $i++) { $query.=" $s effectid$i=$ieffect"; $s="OR"; }
  $query.=")";
}
if ($iclass > 0) { $query.=" AND level$iclass>=$imin AND level$iclass<=$imax ORDER BY level$iclass ASC,name ASC"; }
else { $query.=" ORDER BY name"; }

header("Content-type: application/vnd.ms-excel");
header("Content-disposition: attachment; filename=spells.csv"); 
$result=mysqli_query($db, $query) or message_die('spells.php','MYSQL_QUERY',$query,mysqli_error($db));
if (mysqli_num_rows($result)>0) {
  $l=0;
  if (!($iclass>0)) { print "Spell name,Mana,Duration/Effects,Casting Time,Target\n"; }
  while ($row=mysqli_fetch_array($result)) {
    if (($iclass>0) AND ($l!=$row["level$iclass"])) { 
      print "\nLevel ".$row["level$iclass"]."\n";
      print "Spell name,Mana,Duration/Effects,Casting Time,Target\n";
      $l=$row["level$iclass"];
    }
    print $row["name"];
    print ",".$row["mana"];
    $duration=CalcBuffDuration($ServerMaxLevel,$row["buffformula"],$row["buffduration"]);
    if ($duration==0) { print ",Instant"; } 
    else { print ",".translate_time($duration*6)." ($duration ticks)"; }
    print ",".($row["casttime"]/1000)." sec";
    print ",".$dbspelltargets[$row["targettype"]];
    print "\n";
    for ($n=1; $n<=12; $n++) { SpellDescription($row,$n,true); }
  }
} else { print "No spell found"; }
?>

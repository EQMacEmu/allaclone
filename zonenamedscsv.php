<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir.'mysql.php');
include($includes_dir.'functions.php');
include($includes_dir.'spell.inc.php');

if ($DisplayNamedNPCsInfo == FALSE) {
  header('Location: index.php');
  exit;
}

$name = $_GET['name'] ?? '';
$liste = $_GET['liste'] ?? '';
if (!is_string($name) || !preg_match('/^[A-Za-z0-9_]+$/', $name) || !is_string($liste)) {
  http_response_code(400);
  exit('Invalid zone or NPC selection.');
}
// This endpoint also accepts direct requests, so validate the IDs again here.
$npcs = array();
foreach ($liste === '' ? array() : explode(':', $liste) as $value) {
  $id = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
  if ($id === false) {
    http_response_code(400);
    exit('Invalid NPC selection.');
  }
  $npcs[] = $id;
}
$npcs = array_values(array_unique($npcs));
$zone=GetRowByQuery("SELECT * FROM $tbzones WHERE short_name='$name'");
if (!$zone) {
  http_response_code(404);
  exit('Unknown zone.');
}

header("Content-type: application/vnd.ms-excel");
header("Content-disposition: attachment; filename=$name.csv"); 

print $zone["long_name"];
print "\nName,Race,Class,Level,Spawn points,Drops\n";

foreach ($npcs as $id) {
  $txt=""; 
  $spawns=array();
  $loots=array();
  $query="SELECT $tbnpctypes.* FROM $tbnpctypes WHERE $tbnpctypes.id=$id";
  $mymob=GetRowByQuery($query);
  if (!$mymob) {
    continue;
  }
  $txt=str_replace(array('_','#'),' ',$mymob["name"]).",";
  $txt.=$dbiracenames[$mymob["race"]].",";
  $txt.=$dbclasses[$mymob["class"]].",";
  $txt.=$mymob["level"].",";
    
  $query="SELECT $tbspawn2.x,$tbspawn2.y,$tbspawn2.z,
             $tbspawngroup.name as spawngroup,
             $tbspawngroup.id as spawngroupID,
             $tbspawn2.respawntime
          FROM $tbspawnentry,$tbspawn2,$tbspawngroup
          WHERE $tbspawnentry.npcID=$id
            AND $tbspawnentry.spawngroupID=$tbspawn2.spawngroupID
            AND $tbspawn2.zone='$name'
            AND $tbspawnentry.spawngroupID=$tbspawngroup.id";
  $result=mysqli_query($db, $query) or message_die('npc.php','MYSQL_QUERY',$query,mysqli_error($db));
  if (mysqli_num_rows($result)>0) {
    $cpt=0;
    while ($row=mysqli_fetch_array($result)) {
      $spawns[$cpt]=floor($row["y"])." / ".floor($row["x"])." / ".floor($row["z"]);
      $spawns[$cpt].=" (".translate_time($row["respawntime"]).")";
      $cpt++;
    }
  }
    
  if (($mymob["loottable_id"]>0) AND ((!in_array($mymob["class"],$dbmerchants)) OR ($MerchantsDontDropStuff==FALSE))) {
    $query="SELECT $tbitems.id,$tbitems.Name,$tbitems.itemtype,
                   $tblootdropentries.chance,$tbloottableentries.probability,
                   $tbloottableentries.lootdrop_id,$tbloottableentries.multiplier
            FROM $tbitems,$tbloottableentries,$tblootdropentries
            WHERE $tbloottableentries.loottable_id=".$mymob["loottable_id"]."
              AND $tbloottableentries.lootdrop_id=$tblootdropentries.lootdrop_id
              AND $tblootdropentries.item_id=$tbitems.id";
    $result=mysqli_query($db, $query) or message_die('npc.php','MYSQL_QUERY',$query,mysqli_error($db));
    if (mysqli_num_rows($result)>0) {
      $cpt=0;
      while ($row=mysqli_fetch_array($result)) {
        $loots[$cpt]=$row["Name"];
        $loots[$cpt].=" (".$dbitypes[$row["itemtype"]].")";
        $cpt++;
      }
    }
  }
  $n=max(count($spawns),count($loots));
  for ($i=0; $i<$n; $i++) {
    if ($i==0) { print $txt; } else { print ",,,,"; }
    if ($i<count($spawns)) { print $spawns[$i]; } 
    print ","; 
    if ($i<count($loots)) { print $loots[$i]; }
    print "\n";
  }
}

?>

<?php
$Title = "Populated Zones List";
include('./includes/config.php');
include($includes_dir . 'constantes.php');
include($includes_dir . 'headers.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');

$query = "SELECT $tbzones.short_name AS short_name,
               $tbzones.long_name AS long_name,
               COUNT($tbspawn2.id) AS spawns,
               $tbzones.zoneidnumber AS zoneidnumber
        FROM $tbzones,$tbspawnentry,$tbspawn2
        WHERE $tbspawn2.spawngroupID=$tbspawnentry.spawngroupID 
          AND $tbspawn2.zone=$tbzones.short_name";
/*foreach ($IgnoreZones AS $zid) {
  $query.=" AND $tbzones.short_name!='$zid'";
}
*/
$query .= " GROUP BY $tbspawn2.zone
        ORDER BY $tbzones.long_name ASC";
$result = mysqli_query($db, $query) or message_die('zones.php', 'MYSQL_QUERY', $query, mysqli_error($db));
print "<div class='container zones-populated'>";
print "<div class='table-wrapper'><table width='100%' class='sticky-header'><thead><tr>
       <td class=tab_title>Name</td>
       <td class='tab_title short-name'>Short name</td>
       <td class=tab_title>ID</td>
       <td class=tab_title>Spawn points</td>
       </tr>
       </thead>
       ";
while ($row = mysqli_fetch_array($result)) {
	print "<tr>
         <td><a href=zone.php?name=" . $row["short_name"] . ">" . $row["long_name"] . "</a></td>
         <td class='short-name'>" . $row["short_name"] . "</td>
         <td align=center>" . $row["zoneidnumber"] . "</td>
         <td align=center>" . $row["spawns"] . "</td>
         </tr>";
}
print "</table></div></div></div>";

include($includes_dir . "footers.php");

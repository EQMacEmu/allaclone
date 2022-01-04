<?php
/* Displays the results of a query for objects returning the 'id' and 'name' fields.
 * The query must have been done with at least a limit of '$MaxRowsReturned + 1'.
 * '$MaxObjectsReturned' can be '0', which means the query had no limits (ex: $MaxNpcsReturned).
 * '$OpenObjectById' must contain the name of the page used to open one of the object (ex: npc.php) by passing it
 *   the ID by GET method.
 * 'IdAttribute' and 'NameAttribute' are the name of the columns retrieved used for ID and Name (ex: 'id' and 'name').
 * '$ObjectDescription' is the text describing the kind of objects to display (ex: 'NPC'). '$ObjectsDescription' is the plural. */
function PrintQueryResults(
    $FoundObjects,
    $MaxObjectsReturned,
    $OpenObjectByIdPage,
    $ObjectDescription,
    $ObjectsDescription,
    $IdAttribute,
    $NameAttribute,
    $ExtraField,
    $ExtraFieldDescription,
    $ExtraSkill
) {
    global $dbskills;
    $ObjectsToShow = mysqli_num_rows($FoundObjects);
    if ($ObjectsToShow > LimitToUse($MaxObjectsReturned)) {
        $ObjectsToShow = LimitToUse($MaxObjectsReturned);
        $MoreObjectsExist = true;
    } else {
        $MoreObjectsExist = false;
    }

    if ($ObjectsToShow == 0) {
        echo  "";
    } else {
        echo  "<div class=\"column\"><h3>".$ObjectsToShow." ".($ObjectsToShow == 1 ? $ObjectDescription : $ObjectsDescription)." displayed.";
        if ($MoreObjectsExist) {
            echo  " More ".$ObjectsDescription." exist but you reached the query limit.";
        }
        echo  "</h3>\n";
        echo  "<ul>";
        for ($j = 1 ; $j <= $ObjectsToShow ; $j ++) {
            $row = mysqli_fetch_array($FoundObjects);
            $PrintString = " <li><a href='".$OpenObjectByIdPage."?id=".$row[$IdAttribute]."'>";
            if ($ObjectDescription == "npc") {
                // Clean up the name for NPCs
                $PrintString .= ReadableNpcName($row[$NameAttribute]);
            } else {
                $PrintString .= $row[$NameAttribute];
            }
            $PrintString .= " (".$row[$IdAttribute].")</a>";

            if ($ExtraField && $ExtraFieldDescription && $ExtraSkill) {
                $PrintString .= " - ".ucfirstwords(str_replace("_", " ", $dbskills[$row[$ExtraSkill]])).", $ExtraFieldDescription ".$row[$ExtraField];
            }
            echo  $PrintString;
            echo  "</li>\n";
        }
        echo  "</ul>\n</ul></div>\n";
    }
}

/* Returns the actual limit to use for queries for the specified limit '$MaxObjects'
 * Essentially transforms the '0' in a very large integer.
 * Could be use to put an extra (hard-coded) upper limit to queries. */
function LimitToUse($MaxObjects)
{
    if ($MaxObjects == 0) {
        $Result = 2147483647;
    } else {
        $Result = $MaxObjects;
    }
    return $Result;
}

/* Returns the "readable" name of an NPC from its database-encoded '$DbName'. */
function ReadableNpcName($DbName)
{
    $Result = str_replace('-', '`', str_replace('_', ' ', str_replace('#', '', str_replace('!', '', str_replace('~', '', $DbName)))));
    for ($i = 0; $i < 10; $i++) {
        $Result = str_replace($i, '', $Result);
    }
    return $Result;
}

/* Returns the type of NPC based on the name of an NPC from its database-encoded '$DbName'. */
function NpcTypeFromName($DbName)
{
    global $NPCTypeArray;
    foreach ($NPCTypeArray as $key => $type) {
        $KeyCount = substr_count($DbName, $key);
        $StringLength = strlen($DbName);
        $KeyLength = strlen($key);
        if ($KeyCount > 0 && substr($DbName, 0, $KeyLength) == $key) {
            return $type;
        }
    }
    return "Normal";
}

/* Converts the first letter of each word in $str to upper case and the rest to lower case. */
function ucfirstwords($str)
{
    return ucwords(strtolower($str));
}

/* Returns the URL in the Wiki to the image illustrating the NPC with ID '$NpcId'
 * Returns an empty string if the image does not exist in the Wiki */
function NpcImage($WikiServerUrl, $WikiRootName, $NpcId)
{
    $SystemCall = "wget -q \"".$WikiServerUrl.$WikiRootName."/index.php/Image:Npc-".$NpcId.".jpg\" -O -| grep \"/".$WikiRootName."/images\" | head -1 | sed 's;.*\\(/".$WikiRootName."/images/[^\"]*\\).*;\\1;'";
    $Result = `$SystemCall`;
    if ($Result != "") {
        $Result = $WikiServerUrl.$Result;
    }

    return $Result;
}

/* Returns a uniform value 'Yes'/'No' for many ways of modelling a predicate. */
function YesNo($val)
{
    switch (strtolower($val)) { case true:
    case 1:
    case "yes":
      $Result = "Yes";
      break;

    case false:
    case 0:
    case "no":
      $Result = "No";
      break;
  }
    return $Result;
}

/* Returns a human-readable translation of '$sec' seconds (for respawn times)
 * If '$sec' is '0', returns 'time' (prints 'Spawns all the time' as a result) */
function translate_time($sec)
{
    if ($sec == 0) {
        $Result = "time";
    } else {
        $h = floor($sec / 3600);
        $m = floor(($sec - $h * 3600) / 60);
        $s = $sec - $h * 3600 - $m * 60;
        $Result = ($h > 1 ? "$h hours " : "").($h == 1 ? "1 hour " : "").($m > 0 ? "$m min " : "").($s > 0 ? "$s sec" : "");
    }
    return $Result;
}
function make_thumb($FileSrc)
{
    // If PHP is installed with GD and jpeg support, uncomment the following line
  //execute_make_thumb($FileSrc);
}

// This function (execute_make_thumb) requires to install PHP with GD & jpeg-6 support
// GD -> http://www.boutell.com/gd/
// JPEG -> ftp://ftp.uu.net/graphics/jpeg/
function execute_make_thumb($FileSrc)
{
    $tnH=100;
    $size=getimagesize($FileSrc);
    $src=imagecreatefromjpeg($FileSrc);
    $destW=$size[0]*$tnH/$size[1];
    $destH=$tnH;
    $dest=imagecreate($destW, $destH); // creation de l'image de destination
    imagecopyresized($dest, $src, 0, 0, 0, 0, $destW, $destH, $size[0], $size[1]);
    $tn_name=$FileSrc;
    $tn_name = preg_replace("/\.(gif|jpe|jpg|jpeg|png|wbmp)$/i", "_tn", $tn_name);
    imagejpeg($dest, $tn_name.".jpg");
}

/* Returns the rest of the euclidian division of '$d' by '$v'
 * Returns '0' if '$v' equals '0'
 * Supposes '$d' and '$v' are positive */
function modulo($d, $v)
{
    if ($v == 0) {
        $Result = 0;
    } else {
        $s=floor($d/$v);
        $Result = $d - $v * $s;
    }
}

/* Returns the list of slot names '$val' corresponds to (as a bit field) */
function getslots($val)
{
    global $dbslots;
    reset($dbslots);
    do {
        $key=key($dbslots);
        if ($key <= $val) {
            $val -= $key;
            $Result .= $v.current($dbslots);
            $v=", ";
        }
    } while (next($dbslots));
    return $Result;
}
function getclasses($val)
{
    global $dbiclasses;
    reset($dbiclasses);
    do {
        $key=key($dbiclasses);
        if ($key<=$val) {
            $val-=$key;
            $res.=$v.current($dbiclasses);
            $v=", ";
        }
    } while (next($dbiclasses));
    return $res;
}
function getraces($val)
{
    global $dbraces;
    reset($dbraces);
    do {
        $key=key($dbraces);
        if ($key<=$val) {
            $val-=$key;
            $res.=$v.current($dbraces);
            $v=", ";
        }
    } while (next($dbraces));
    return $res;
}
function getsize($val)
{
    switch ($val) {
    case 0: return "Tiny"; break;
    case 1: return "Small"; break;
    case 2: return "Medium"; break;
    case 3: return "Large"; break;
    case 4: return "Giant"; break;
    default: return "$val?"; break;
  }
}
function getspell($id)
{
    global $tbspells,$tbspellglobals,$UseSpellGlobals, $db;
    if ($UseSpellGlobals==true) {
        $query="SELECT ".$tbspells.".* FROM ".$tbspells." WHERE ".$tbspells.".id=".$id."
			AND ISNULL((SELECT ".$tbspellglobals.".spellid FROM ".$tbspellglobals."
			WHERE ".$tbspellglobals.".spellid = ".$tbspells.".id))";
    } else {
        $query="SELECT * FROM $tbspells WHERE id=$id";
    }
    $result=mysqli_query($db, $query) or message_die('functions.php', 'getspell', $query, mysqli_error($db));
    $s=mysqli_fetch_array($result);
    return $s;
}
function gedeities($val)
{
    global $dbideities;
    reset($dbideities);
    do {
        $key=key($dbideities);
        if ($key<=$val) {
            $val-=$key;
            $res.=$v.current($dbideities);
            $v=", ";
        }
    } while (next($dbideities));
    return $res;
}
function SelectClass($name, $selected)
{
    global $dbclasses;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Class</option>\n";
    for ($i=1; $i<=16; $i++) {
        print "<option value='".$i."'";
        if ($i==$selected) {
            print " selected='1'";
        }
        print ">".$dbclasses[$i]."</option>\n";
    }
    print "</SELECT>";
}
function SelectDeity($name, $selected)
{
    global $dbideities;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Deity</option>\n";
    for ($i=2; $i<=65536; $i*=2) {
        print "<option value='".$i."'";
        if ($i==$selected) {
            print " selected='1'";
        }
        print ">".$dbideities[$i]."</option>\n";
    }
    print "</SELECT>";
}
function SelectRace($name, $selected)
{
    global $dbraces;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Race</option>\n";
    for ($i=1; $i<32768; $i*=2) {
        print "<option value='".$i."'";
        if ($i==$selected) {
            print " selected='1'";
        }
        print ">".$dbraces[$i]."</option>\n";
    }
    print "</SELECT>";
}
function SelectMobRace($name, $selected)
{
    global $dbiracenames;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Mob Race</option>\n";
    foreach ($dbiracenames as $key => $value) {
        print "<option value='".$key."'";
        if ($key==$selected) {
            print " selected='1'";
        }
        print ">".$value."</option>\n";
    }
    print "</SELECT>";
}
function SelectIClass($name, $selected)
{
    global $dbiclasses;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Class</option>\n";
    for ($i=1; $i<=32768; $i*=2) {
        print "<option value='".$i."'";
        if ($i==$selected) {
            print " selected='1'";
        }
        print ">".$dbiclasses[$i]."</option>\n";
    }
    print "</SELECT>";
}
function SelectIType($name, $selected)
{
    global $dbitypes;
    print "<SELECT name=\"$name\">";
    print "<option value='-1'>Type</option>\n";
    reset($dbitypes);
    do {
        $key=key($dbitypes);
        print "<option value='".$key."'";
        if ($key==$selected) {
            print " selected='1'";
        }
        print ">".current($dbitypes)."</option>\n";
    } while (next($dbitypes));
    print "</SELECT>";
}
function SelectSlot($name, $selected)
{
    global $dbslots;
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Slot</option>\n";
    reset($dbslots);
    do {
        $key=key($dbslots);
        print "<option value='".$key."'";
        if ($key==$selected) {
            print " selected='1'";
        }
        print ">".current($dbslots)."</option>\n";
    } while (next($dbslots));
    print "</SELECT>";
}
function SelectSpellEffect($name, $selected)
{
    global $dbspelleffects;
    print "<SELECT name=\"$name\">";
    print "<option value=-1>Effect</option>\n";
    reset($dbspelleffects);
    do {
        $key=key($dbspelleffects);
        print "<option value='".$key."'";
        if ($key==$selected) {
            print " selected='1'";
        }
        print ">".current($dbspelleffects)."</option>\n";
    } while (next($dbspelleffects));
    print "</SELECT>";
}
function SelectAugSlot($name, $selected)
{
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Augment Slot</option>\n";
    for ($i=1; $i<=25; $i++) {
        print "<option value='".$i."'";
        if ($i==$selected) {
            print " selected='1'";
        }
        print ">slot $i</option>\n";
    }
    print "</SELECT>";
}
function SelectLevel($name, $maxlevel, $selevel)
{
    print "<SELECT name=\"$name\">";
    print "<option value='0'>Level</option>\n";
    for ($i=1; $i<=$maxlevel; $i++) {
        print "<option value='".$i."'";
        if ($i==$selevel) {
            print " selected='1'";
        }
        print ">$i</option>\n";
    }
    print "</SELECT>";
}
function SelectTradeSkills($name, $selected)
{
    print "<SELECT name=\"$name\">";
    WriteIt("0", "-", $selected);
    WriteIt("59", "Alchemy", $selected);
    WriteIt("60", "Baking", $selected);
    WriteIt("63", "Blacksmithing", $selected);
    WriteIt("65", "Brewing", $selected);
    WriteIt("55", "Fishing", $selected);
    WriteIt("64", "Fletching", $selected);
    WriteIt("68", "Jewelery making", $selected);
    WriteIt("56", "Poison making", $selected);
    WriteIt("69", "Pottery making", $selected);
    WriteIt("58", "Research", $selected);
    WriteIt("61", "Tailoring", $selected);
    WriteIt("57", "Tinkering", $selected);
    print "</SELECT>";
}
function WriteIt($value, $name, $sel)
{
    print "  <option value='".$value."'";
    if ($value==$sel) {
        print " selected='1'";
    }
    print ">$name</option>\n";
}
function SelectStats($name, $stat)
{
    print "<select name=\"$name\">\n";
    print "  <option value=''>Stat</option>\n";
    WriteIt("hp", "Hit Points", $stat);
    WriteIt("mana", "Mana", $stat);
    WriteIt("ac", "AC", $stat);
    WriteIt("attack", "Attack", $stat);
    WriteIt("aagi", "Agility", $stat);
    WriteIt("acha", "Charisma", $stat);
    WriteIt("adex", "Dexterity", $stat);
    WriteIt("aint", "Intelligence", $stat);
    WriteIt("asta", "Stamina", $stat);
    WriteIt("astr", "Strength", $stat);
    WriteIt("awis", "Wisdom", $stat);
    WriteIt("damage", "Damage", $stat);
    WriteIt("delay", "Delay", $stat);
    WriteIt("ratio", "Ratio", $stat);
    WriteIt("haste", "Haste", $stat);
    WriteIt("regen", "HP Regen", $stat);
    WriteIt("manaregen", "Mana Regen", $stat);
    WriteIt("enduranceregen", "Endurance Regen", $stat);
    print "</select>\n";
}
function SelectHeroicStats($name, $heroic)
{
    print "<select name=\"$name\">\n";
    print "  <option value=''>H. Stat</option>\n";
    WriteIt("heroic_agi", "Heroic Agility", $stat);
    WriteIt("heroic_cha", "Heroic Charisma", $stat);
    WriteIt("heroic_dex", "Heroic Dexterity", $stat);
    WriteIt("heroic_int", "Heroic Intelligence", $stat);
    WriteIt("heroic_sta", "Heroic Stamina", $stat);
    WriteIt("heroic_str", "Heroic Strength", $stat);
    WriteIt("heroic_wis", "Heroic Wisdom", $stat);
    WriteIt("heroic_mr", "Heroic Resist Magic", $heroic);
    WriteIt("heroic_fr", "Heroic Resist Fire", $heroic);
    WriteIt("heroic_cr", "Heroic Resist Cold", $heroic);
    WriteIt("heroic_pr", "Heroic Resist Poison", $heroic);
    WriteIt("heroic_dr", "Heroic Resist Disease", $heroic);
    WriteIt("heroic_svcorrup", "Heroic Resist Corruption", $heroic);
    print "</select>\n";
}
function SelectResists($name, $resist)
{
    print "<select name=\"$name\">\n";
    print "  <option value=''>Resists</option>\n";
    WriteIt("mr", "Resist Magic", $resist);
    WriteIt("fr", "Resist Fire", $resist);
    WriteIt("cr", "Resist Cold", $resist);
    WriteIt("pr", "Resist Poison", $resist);
    WriteIt("dr", "Resist Disease", $resist);
    WriteIt("svcorruption", "Resist Corruption", $resist);
    print "</select>\n";
}
function SelectModifiers($name, $mod)
{
    print "<select name=\"$name\">\n";
    print "  <option value=''>Modifiers</option>\n";
    WriteIt("avoidance", "Avoidance", $mod);
    WriteIt("accuracy", "Accuracy", $mod);
    WriteIt("backstabdmg", "Backstab Damage", $mod);
    WriteIt("clairvoyance", "Clairvoyance", $mod);
    WriteIt("combateffects", "Combat Effects", $mod);
    WriteIt("damageshield", "Damage Shield", $mod);
    WriteIt("dsmitigation", "Damage Shield Mit", $mod);
    WriteIt("dotshielding", "DoT Shielding", $mod);
    WriteIt("extradmgamt", "Extra Damage", $mod);
    WriteIt("healamt", "Heal Amount", $mod);
    WriteIt("purity", "Purity", $mod);
    WriteIt("shielding", "Shielding", $mod);
    WriteIt("spelldmg", "Spell Damage", $mod);
    WriteIt("spellshield", "Spell Shielding", $mod);
    WriteIt("strikethrough", "Strikethrough", $mod);
    WriteIt("stunresist", "Stun Resist", $mod);
    print "</select>\n";
}
function GetItemStatsString($name, $stat, $stat2, $stat2color)
{
    if (!$stat2) {
        $stat2 = 0;
    }
    $PrintString = "";
    if (is_numeric($stat)) {
        if ($stat != 0 || $stat2 != 0) {
            $PrintString .= $name.": ";
            if ($stat < 0) {
                $PrintString .= "<font color='red'>".sign($stat)."</font>";
            } else {
                $PrintString .= $stat;
            }
            if ($stat2 < 0) {
                $PrintString .= "<font color='red'> ".sign($stat2)."</font>";
            } elseif ($stat2 > 0) {
                if ($stat2color) {
                    $PrintString .= "<font color='".$stat2color."'> ".sign($stat2)."</font>";
                } else {
                    $PrintString .= sign($stat2);
                }
            }
        }
    } else {
        if (preg_replace("[^0-9]", "", $stat) > 0) {
            $PrintString .= $name.": ".$stat;
        }
    }
    return $PrintString;
}

// spell_effects.cpp int Mob::CalcSpellEffectValue_formula(int formula, int base, int max, int caster_level, int16 spell_id)
function CalcSpellEffectValue($form, $base, $max, $lvl)
{
    // print " (base=$base form=$form max=$max, lvl=$lvl)";
    $sign=1;
    $ubase=abs($base);
    $result=0;
    if (($max<$base) and ($max!=0)) {
        $sign=-1;
    }
    switch ($form) {
        case 0:
        case 100:
            $result=$ubase; break;
        case 101:
            $result=$ubase+$sign*($lvl/2); break;
        case 102:
            $result=$ubase+$sign*$lvl; break;
        case 103:
            $result=$ubase+$sign*$lvl*2; break;
        case 104:
            $result=$ubase+$sign*$lvl*3; break;
        case 105:
        case 107:
            $result=$ubase+$sign*$lvl*4; break;
        case 108:
            $result=floor($ubase+$sign*$lvl/3); break;
        case 109:
            $result=floor($ubase+$sign*$lvl/4); break;
        case 110:
            $result=floor($ubase+$lvl/5); break;
        case 111:
            $result=$ubase+5*($lvl-16); break;
        case 112:
            $result=$ubase+8*($lvl-24); break;
        case 113:
            $result=$ubase+12*($lvl-34); break;
        case 114:
            $result=$ubase+15*($lvl-44); break;
        case 115:
            $result=$ubase+15*($lvl-54); break;
      case 116:
        $result=floor($ubase+8*($lvl-24)); break;
      case 117:
        $result=$ubase+11*($lvl-34); break;
      case 118:
        $result=$ubase+17*($lvl-44); break;
      case 119:
            $result=floor($ubase+$lvl/8); break;
      case 121:
            $result=floor($ubase+$lvl/3); break;

        default:
            if ($form<100) {
                $result=$ubase+($lvl*$form);
            }
  } // end switch
    if ($max!=0) {
        if ($sign==1) {
            if ($result>$max) {
                $result=$max;
            }
        } else {
            if ($result<$max) {
                $result=$max;
            }
        }
    }
    if (($base<0) && ($result>0)) {
        $result*=-1;
    }
    return $result;
}
function CalcBuffDuration($lvl, $form, $duration)
{ // spells.cpp, carefull, return value in ticks, not in seconds
    //print " Duration lvl=$lvl, form=$form, duration=$duration ";
    switch ($form) {
        case 0:
          return 0;
          break;
        case 1:
          $i=ceil($lvl/2);
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 2:
            $i=ceil($duration/5*3);
            return ($i<$duration?($i<1?1:$i):$duration);
            break;
    case 3:
            $i=$lvl*30;
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 4:
            return $duration;
      break;
        case 5:
            $i=$duration;
            return ($i<3?($i<1?1:$i):3);
      break;
        case 6:
            $i=ceil($lvl/2);
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 7:
            $i=$lvl;
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 8:
            $i=$lvl+10;
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 9:
            $i=$lvl*2+10;
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 10:
            $i=$lvl*3+10;
            return ($i<$duration?($i<1?1:$i):$duration);
      break;
        case 11:
        case 12:
            return $duration;
      break;
        case 50:
            return 72000;
        case 3600:
            return ($duration?$duration:3600);
    }
}
function SpecialAttacks($att)
{
    $data='';
    $v='';
    // from mobs.h
    for ($i=0; $i<strlen($att); $i++) {
        switch ($att{$i}) {
      case 'A': $data.=$v." Immune to melee"; $v=', '; break;
      case 'B': $data.=$v." Immune to magic"; $v=', '; break;
      case 'C': $data.=$v." Uncharmable"; $v=', '; break;
      case 'D': $data.=$v." Unfearable"; $v=', '; break;
      case 'E': $data.=$v." Enrage"; $v=', '; break;
      case 'F': $data.=$v." Flurry"; $v=', '; break;
      case 'f': $data.=$v." Immune to fleeing"; $v=', '; break;
      case 'I': $data.=$v." Unsnarable"; $v=', '; break;
      case 'M': $data.=$v." Unmezzable"; $v=', '; break;
      case 'N': $data.=$v." Unstunable"; $v=', '; break;
      case 'O': $data.=$v." Immune to melee except bane"; $v=', '; break;
      case 'Q': $data.=$v." Quadruple Attack"; $v=', '; break;
      case 'R': $data.=$v." Rampage"; $v=', '; break;
      case 'S': $data.=$v." Summon"; $v=', '; break;
      case 'T': $data.=$v." Triple Attack"; $v=', '; break;
      case 'U': $data.=$v." Unslowable"; $v=', '; break;
      case 'W': $data.=$v." Immune to melee except magical"; $v=', '; break;
    }
    }
    return $data;
}
function price($price)
{
    $res="";
    if ($price>=1000) {
        $p=floor($price/1000);
        $price-=$p*1000;
    }
    if ($price>=100) {
        $g=floor($price/100);
        $price-=$g*100;
    }
    if ($price>=10) {
        $s=floor($price/10);
        $price-=$s*10;
    }
    $c=$price;
    if ($p>0) {
        $res=$p."p";
        $sep=" ";
    }
    if ($g>0) {
        $res.=$sep.$g."g";
        $sep=" ";
    }
    if ($s>0) {
        $res.=$sep.$s."s";
        $sep=" ";
    }
    if ($c>0) {
        $res.=$sep.$c."c";
    }
    return $res;
}
function sign($val)
{
    if ($val>0) {
        return "+$val";
    } else {
        return $val;
    }
}
function WriteDate($d)
{
    return date("F d, Y", $d);
}
function isinteger($val)
{
    return (intval($val)==$val);
}
function CanThisNPCDoubleAttack($class, $level)
{ // mob.cpp
    if ($level>26) {
        return true;
    } #NPC over lvl 26 all double attack
    switch ($class) {
    case 0: # monks and warriors
    case 1:
    case 20:
    case 26:
    case 27:
      if ($level<15) {
          return false;
      }
      break;
    case 9: # rogues
    case 28:
      if ($level<16) {
          return false;
      }
      break;
    case 4: # rangers
    case 23:
    case 5: # shadowknights
    case 24:
    case 3: # paladins
    case 22:
      if ($level<20) {
          return false;
      }
      break;
  }
    return false;
}

// Automatically format and populate the table based on the query
function AutoDataTable($Query)
{
    $result = mysqli_query($db, $Query);
    if (!$result) {
        echo 'Could not run query: ' . mysqli_error($db);
        exit;
    }
    $columns = mysql_num_fields($result);
    echo "<table border=0 width=100%><thead>";
    $RowClass = "lr";
    ###Automatically Generate the column names from the Table
    for ($i = 0; $i < $columns; $i++) {
        echo "<th class='menuh'>". ucfirstwords(str_replace('_', ' ', mysql_field_name($result, $i))) . " </th>";
    }
    echo "</tr></thead><tbody>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<tr class='".$RowClass."'>";
        for ($i = 0; $i < $columns; $i++) {
            echo "<td>" . $row[$i] . "</td>";
        }
        echo "</tr>";
        if ($RowClass == "lr") {
            $RowClass = "dr";
        } else {
            $RowClass = "lr";
        }
    }
    echo "</tbody></table>";
}
function Pagination($targetpage, $page, $total_pages, $limit, $adjacents)
{

    /* Setup page vars for display. */
    if ($page == 0) {
        $page = 1;
    }					//if no page var is given, default to 1.
    $prev = $page - 1;							//previous page is page - 1
    $next = $page + 1;							//next page is page + 1
    $lastpage = ceil($total_pages/$limit);		//lastpage is = total pages / items per page, rounded up.
    $lpm1 = $lastpage - 1;						//last page minus 1

    $pagination = "";
    if ($lastpage > 1) {
        $pagination .= "<div class=\"pagination\">";
        //previous button
        if ($page > 1) {
            $pagination.= "<a href=\"$targetpage?page=$prev\">previous</a>";
        } else {
            $pagination.= "<span class=\"disabled\">previous</span>";
        }

        //pages
        if ($lastpage < 7 + ($adjacents * 2)) {	//not enough pages to bother breaking it up
            for ($counter = 1; $counter <= $lastpage; $counter++) {
                if ($counter == $page) {
                    $pagination.= "<span class=\"current\">$counter</span>";
                } else {
                    $pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
                }
            }
        } elseif ($lastpage > 5 + ($adjacents * 2)) {	//enough pages to hide some
            //close to beginning; only hide later pages
            if ($page < 1 + ($adjacents * 2)) {
                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                    if ($counter == $page) {
                        $pagination.= "<span class=\"current\">$counter</span>";
                    } else {
                        $pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
                    }
                }
                $pagination.= "...";
                $pagination.= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
                $pagination.= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
            }
            //in middle; hide some front and some back
            elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                $pagination.= "<a href=\"$targetpage?page=1\">1</a>";
                $pagination.= "<a href=\"$targetpage?page=2\">2</a>";
                $pagination.= "...";
                for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                    if ($counter == $page) {
                        $pagination.= "<span class=\"current\">$counter</span>";
                    } else {
                        $pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
                    }
                }
                $pagination.= "...";
                $pagination.= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
                $pagination.= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
            }
            //close to end; only hide early pages
            else {
                $pagination.= "<a href=\"$targetpage?page=1\">1</a>";
                $pagination.= "<a href=\"$targetpage?page=2\">2</a>";
                $pagination.= "...";
                for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                    if ($counter == $page) {
                        $pagination.= "<span class=\"current\">$counter</span>";
                    } else {
                        $pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
                    }
                }
            }
        }

        //next button
        if ($page < $counter - 1) {
            $pagination.= "<a href=\"$targetpage?page=$next\">next</a>";
        } else {
            $pagination.= "<span class=\"disabled\">next</span>";
        }
        $pagination.= "</div>\n";
    }
    return $pagination;
}

// Function to build item stats tables
// Used for item.php as well as for tooltips for items
function BuildItemStats($item, $show_name_icon)
{
    global $dbitypes, $dam2h, $dbbagtypes, $dbskills, $icons_url, $tbspells, $dbiracenames, $dbelements, $dbbodytypes, $dbbardskills;

    $html_string = "";
    $html_string .= "<div class='item-stats'>";
    if ($show_name_icon) {
        $html_string .= "<h4 style='margin-top:0'>" . $item["Name"] . "</h4></td>";
        $html_string .= "<img src='" . $icons_url . "item_" . $item["icon"] . ".gif' align='right' valign='top'/>";
    }

    $html_string .= "<p>";
    $v = "";
    if ($item["magic"] == 1) {
        $html_string .= "MAGIC ITEM";
    }
    if (substr($item["lore"], 0, 2) == '*#') {
        $html_string .= " LORE ITEM ARTIFACT";
    } elseif (substr($item["lore"], 0, 1) == '*') {
        $html_string .= " LORE ITEM";
    } elseif (substr($item["lore"], 0, 1) == '#') {
        $html_string .= " ARTIFACT";
    }
    if ($item["nodrop"] == 0) {
        $html_string .= " NODROP";
    }
    if ($item["norent"] == 0) {
        $html_string .= " NORENT";
    }
    if ($item["maxcharges"] > 0) {
        $html_string .= " Charges: " . $item["maxcharges"];
    }

    $html_string .= "</p>";


    if ($item["deity"] > 0) {
        $html_string .= "<p><strong>Deity:</strong> " . gedeities($item["deity"]) . "</p>";
    }

    if ($item["slots"] > 0) {
        $html_string .= "<p><strong>Slot:</strong> " . strtoupper(getslots($item["slots"])) . "</p>";
    }
    if ($item["slots"] == 0) {
        $html_string .= "<p><strong>Slot:</strong> NONE</p>";
    }

    $TypeString = "";
    switch ($item["itemtype"]) {
        case 0: // 1HS
        case 2: // 1HP
        case 3: // 1HB
        case 42: // H2H
        case 1: // 2hs
        case 4: // 2hb
        case 35: // 2hp
            $TypeString = "Skill";
            break;
        default:
            $TypeString = "Item Type";
            break;
    }

    if (($dbitypes[$item["itemtype"]] != "") && ($item["bagslots"] == 0)) {
        if ($item["slots"] == 0) {
            $html_string .= $TypeString . ": Inventory";
        } else {
            $html_string .= $TypeString . ": " . $dbitypes[$item["itemtype"]];
        }

        if ($item["stacksize"] > 1) {
            $html_string .= " (stackable)";
        }
        $html_string;
    }

    if ($item["bagslots"] > 0) {
        $html_string .= " Item Type: Container";
        $html_string .= " Number of Slots: " . $item["bagslots"];
        if ($item["bagtype"] > 0) {
            $html_string .= "Trade Skill Container: " . $dbbagtypes[$item["bagtype"]];
        }
        if ($item["bagwr"] > 0) {
            $html_string .= " Weight Reduction: " . $item["bagwr"] . "%";
        }
        $html_string .= "This can hold " . strtoupper(getsize($item["bagsize"])) . " and smaller items.";
    }
    $html_string .= GetItemStatsString(" Haste", $item["haste" . "%"]);

    if (($item["banedmgrace"] > 0) && ($item["banedmgamt"] != 0)) {
        $html_string .= " Bane Damage (";
        $html_string .= $dbiracenames[$item["banedmgrace"]];
        $html_string .= ")" . sign($item["banedmgamt"]) . "";
    }
    $html_string .= GetItemStatsString(ucfirstwords($dbbodytypes[$item["banedmgbody"]]), $item["banedmgamt"]);
    $html_string .= GetItemStatsString(" Backstab Damage", $item["backstabdmg"]);
    $html_string .= GetItemStatsString(" Atk Delay", $item["delay"]);
    $html_string .= "<br />";
    $html_string .= GetItemStatsString(" DMG", $item["damage"]);
    if ($item["damage"] > 0) {
        switch ($item["itemtype"]) {
            case 0: // 1HS
            case 2: // 1HP
            case 3: // 1HB
            case 42: // H2H
                $dmgbonus = 13; // floor((65-25)/3)  main hand
                $html_string .= " Dmg Bonus: $dmgbonus";
                break;
            case 1: // 2hs
            case 4: // 2hb
            case 35: // 2hp
                $dmgbonus = $dam2h[$item["delay"]];
                $html_string .= " Dmg Bonus: $dmgbonus";
                break;
        }
    }
    $html_string .= GetItemStatsString(" AC", $item["ac"]);
    $html_string .= "<br />";
    if ($dbelements[$item["elemdmgtype"]] != 'Unknown') {
        $html_string .= GetItemStatsString(ucfirstwords($dbelements[$item["elemdmgtype"]]) . " DMG", $item["elemdmgamt"]);
        $html_string .= "<br />";
    }
    $html_string .= GetItemStatsString(" Range", $item["range"]);
    $html_string .= GetItemStatsString(" STR", $item["astr"]);
    $html_string .= GetItemStatsString(" DEX", $item["adex"]);
    $html_string .= GetItemStatsString(" STA", $item["asta"]);
    $html_string .= GetItemStatsString(" CHA", $item["acha"]);
    $html_string .= GetItemStatsString(" WIS", $item["awis"]);
    $html_string .= GetItemStatsString(" INT", $item["aint"]);
    $html_string .= GetItemStatsString(" AGI", $item["aagi"]);
    $html_string .= GetItemStatsString(" HP", $item["hp"]);
    $html_string .= GetItemStatsString(" MANA", $item["mana"]);
    $html_string .= "<br />";
    $html_string .= GetItemStatsString(" SV FIRE", $item["fr"]);
    $html_string .= GetItemStatsString(" SV DISEASE", $item["dr"]);
    $html_string .= GetItemStatsString(" SV COLD", $item["cr"]);
    $html_string .= GetItemStatsString(" SV MAGIC", $item["mr"]);
    $html_string .= GetItemStatsString(" SV POISON", $item["pr"]);
    $html_string .= "<br />";
    $html_string .= GetItemStatsString(" Atk", $item["attack"]);
    $html_string .= GetItemStatsString(" Regen", $item["regen"]);
    $html_string .= GetItemStatsString(" Flowing Thought", $item["manaregen"]);
    $html_string .= GetItemStatsString(" Spell Shielding", $item["spellshield"]);
    $html_string .= GetItemStatsString(" Combat Effects", $item["combateffects"]);
    $html_string .= GetItemStatsString(" Shielding", $item["shielding"]);
    $html_string .= GetItemStatsString(" DoT Shielding", $item["dotshielding"]);
    $html_string .= GetItemStatsString(" Avoidance", $item["avoidance"]);
    $html_string .= GetItemStatsString(" Accuracy", $item["accuracy"]);
    $html_string .= GetItemStatsString(" Stun Resist", $item["stunresist"]);
    $html_string .= GetItemStatsString(" Strikethrough", $item["strikethrough"]);
    $html_string .= GetItemStatsString(" Damage Shield", $item["damageshield"]);

    if ($item["extradmgamt"] > 0) {
        $html_string .= ucfirstwords($dbskills[$item["extradmgskill"]]) . " Damage: " . sign($item["extradmgamt"]);
    }

    if (($item["skillmodtype"] > 0) && ($item["skillmodvalue"] != 0)) {
        $html_string .= " Skill Mod: " . ucfirstwords($dbskills[$item["skillmodtype"]]) . ": " . sign($item["skillmodvalue"]) . "%";
    }

    $html_string .= GetItemStatsString(" Recommended level", $item["reclevel"]);
    $html_string .= GetItemStatsString(" Required level", $item["reqlevel"]);

    if (($item["proceffect"] > 0) && ($item["proceffect"] < 65535)) {
        $html_string .= " <br />Effect: <a href='spell.php?id=" . $item["proceffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["proceffect"]) . "</a> (Combat)";
        // if ($item["proclevel2"] > 0) {
        //     $html_string .= "<br />Level for effect: " . $item["proclevel2"];
        // }
    }

    if (($item["worneffect"] > 0) && ($item["worneffect"] < 65535)) {
        $html_string .= " Effect: <a href='spell.php?id=" . $item["worneffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["worneffect"]) . "</a> (Worn)";
        if ($item["worneffect"] == 998) {
            $haste = (int) $item["wornlevel"];
            $haste++;
            $html_string .= " (" . $haste . "%)";
        }
        // if ($item["wornlevel"] > 0 && $item["worneffect"] != 998) {
        //     $html_string .= "<br />Level for effect: " . $item["wornlevel"];
        // }
    }

    if (($item["focuseffect"] > 0) && ($item["focuseffect"] < 65535)) {
        $html_string .= " Focus Effect: <a href='spell.php?id=" . $item["focuseffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["focuseffect"]) . "</a>";
        // if ($item["focuslevel"] > 0) {
        //     $html_string .= "<br />Level for effect: " . $item["focuslevel"];
        // }
    }

    if (($item["clickeffect"] > 0) && ($item["clickeffect"] < 65535)) {
        $html_string .= " Click Effect: <a href='spell.php?id=" . $item["clickeffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["clickeffect"]) . "</a> (";

        if ($item["clicktype"] == 4) {
            $html_string .= " Must Equip. ";
        }

        if ($item["casttime"] > 0) {
            $html_string .= " Casting time: " . ($item["casttime"] / 1000) . " sec";
        } else {
            $html_string .= " Casting time: Instant";
        }
        $html_string .= ")";

        // if ($item["clicklevel"] > 0) {
        //     $html_string .= "<br />Level for effect: </b>" . $item["clicklevel"];
        // }
    }

    $html_string .= GetItemStatsString("<br />WT", ($item["weight"] / 10));
    $html_string .= " Size: " . strtoupper(getsize($item["size"]));

    if (($item["scrolleffect"] > 0) && ($item["scrolleffect"] < 65535)) {
        $html_string .= " Spell Scroll Effect: <a href='spell.php?id=" . $item["scrolleffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["scrolleffect"]) . "</a>";
    }

    // bard items (fuck bards)
    if (($item["bardtype"] > 22) && ($item["bardtype"] < 65535)) {
        $html_string .= " Bard skill: " . $dbbardskills[$item["bardtype"]];
        if ($dbbardskills[$item["bardtype"]] == "") {
            $html_string .= "Unknown" . $item["bardtype"];
        }

        $val = ($item["bardvalue"] * 10) - 100;
        if ($val > 0) {
            $html_string .= " (" . sign($val) . "%)";
        }
    }

    $ItemPrice = $item["price"];
    $ItemValue = "";
    $Platinum = 0;
    $Gold = 0;
    $Silver = 0;
    $Copper = 0;

    if ($ItemPrice > 1000) {
        $Platinum = ((int)($ItemPrice / 1000));
    }

    if (($ItemPrice - ($Platinum * 1000)) > 100) {
        $Gold = ((int)(($ItemPrice - ($Platinum * 1000)) / 100));
    }

    if (($ItemPrice - ($Platinum * 1000) - ($Gold * 100)) > 10) {
        $Silver = ((int)(($ItemPrice - ($Platinum * 1000) - ($Gold * 100)) / 10));
    }

    if (($ItemPrice - ($Platinum * 1000) - ($Gold * 100) - ($Silver * 10)) > 0) {
        $Copper = ($ItemPrice - ($Platinum * 1000) - ($Gold * 100) - ($Silver * 10));
    }

    // $ItemValue .= "<p>Value: ";
    // $ItemValue .= $Platinum." <img src='" . $icons_url . "item_644.gif' width='14' height='14'/> ".
    //                   $Gold." <img src='" . $icons_url . "item_645.gif' width='14' height='14'/> ".
    //                 $Silver." <img src='" . $icons_url . "item_646.gif' width='14' height='14'/> ".
    //                 $Copper." <img src='" . $icons_url . "item_647.gif' width='14' height='14'/>";
    // $html_string .= $ItemValue ."</p>";


    if ($item["classes"] > 0) {
        $html_string .= "<p><strong>Class:</strong> " . getclasses($item["classes"]) . "</p>";
    } else {
        $html_string .= "<p><strong>Class:</strong> ALL</p>";
    }
    if ($item["races"] > 0) {
        $html_string .= "<p><strong>Race:</strong> " . getraces($item["races"]) . "</p>";
    } else {
        $html_string .= "<p><strong>Race: </strong> ALL</p>";
    }

    $html_string .= "</div>";

    return $html_string;
}

function debug($data)
{
    print "<pre>";
    print "Admin Debug Panel:\r\n";
    print print_r($data);
    print "</pre>";
}
function isadmin()
{
    global $admin;
    if ($_SERVER['REMOTE_ADDR'] === $admin) {
        return true;
    }
    return false;
}
function admindebug($content)
{
    if (isadmin()) {
        debug($content);
    }
}

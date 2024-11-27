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
	$ExtraField = null,
	$ExtraFieldDescription = null,
	$ExtraSkill = null
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
		echo  "<div class='results'><h3>" . $ObjectsToShow . " " . ($ObjectsToShow == 1 ? $ObjectDescription : $ObjectsDescription) . " displayed.";
		if ($MoreObjectsExist) {
			echo  " More " . $ObjectsDescription . " exist but you reached the query limit.";
		}
		echo  "</h3>\n";
		echo  "<ul>";
		for ($j = 1; $j <= $ObjectsToShow; $j++) {
			$row = mysqli_fetch_array($FoundObjects);
			$attrs = "";
			$id = $row[$IdAttribute];
			if ($ObjectDescription == "item") {
				$attrs = "data-item-id=\"$id\" class=\"item-link\"";
			}
			$PrintString = " <li><a href='$OpenObjectByIdPage?id=$id' $attrs>";
			if ($ObjectDescription == "npc") {
				// Clean up the name for NPCs
				$PrintString .= ReadableNpcName($row[$NameAttribute]);
			} else {
				$PrintString .= $row[$NameAttribute];
			}
			$PrintString .= " ($id)</a>";

			if ($ExtraField && $ExtraFieldDescription && $ExtraSkill) {
				$PrintString .= " - " . ucfirstwords(str_replace("_", " ", $dbskills[$row[$ExtraSkill]])) . ", $ExtraFieldDescription " . $row[$ExtraField];
			}
			echo  $PrintString;
			echo  "</li>";
		}
		echo  "</ul></ul></div>";
	}
}

/* Returns the actual limit to use for queries for the specified limit '$MaxObjects'
 * Essentially transforms the '0' in a very large integer.
 * Could be use to put an extra (hard-coded) upper limit to queries. */
function LimitToUse($MaxObjects) {
	if ($MaxObjects == 0) {
		$Result = 2147483647;
	} else {
		$Result = $MaxObjects;
	}
	return $Result;
}

/* Returns the "readable" name of an NPC from its database-encoded '$DbName'. */
function ReadableNpcName($DbName) {
	$Result = str_replace('-', '`', str_replace('_', ' ', str_replace('#', '', str_replace('!', '', str_replace('~', '', $DbName)))));
	for ($i = 0; $i < 100; $i++) {
		$Result = str_replace($i, '', $Result);
	}
	return $Result;
}

/* Returns the type of NPC based on the name of an NPC from its database-encoded '$DbName'. */
function NpcTypeFromName($DbName) {
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
function ucfirstwords($str) {
	return ucwords(strtolower($str));
}

/* Returns the URL in the Wiki to the image illustrating the NPC with ID '$NpcId'
 * Returns an empty string if the image does not exist in the Wiki */
function NpcImage($WikiServerUrl, $WikiRootName, $NpcId) {
	$SystemCall = "wget -q \"" . $WikiServerUrl . $WikiRootName . "/index.php/Image:Npc-" . $NpcId . ".jpg\" -O -| grep \"/" . $WikiRootName . "/images\" | head -1 | sed 's;.*\\(/" . $WikiRootName . "/images/[^\"]*\\).*;\\1;'";
	$Result = `$SystemCall`;
	if ($Result != "") {
		$Result = $WikiServerUrl . $Result;
	}

	return $Result;
}

/* Returns a uniform value 'Yes'/'No' for many ways of modelling a predicate. */
function YesNo($val) {
	switch (strtolower($val)) {
		case true:
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
function translate_time($sec) {
	if ($sec == 0) {
		$Result = "instantly";
	} else {
		$d = floor($sec / 86400);
		$sec = $sec % 86400;
		$h = floor($sec / 3600);
		$sec = $sec % 3600;
		$m = floor($sec / 60);
		$sec = $sec % 60;
		$s = $sec;
		$Result = ($d > 1 ? "$d days " : "") . ($d == 1 ? "1 day " : "") .($h > 1 ? "$h hours " : "") . ($h == 1 ? "1 hour " : "") . ($m > 0 ? "$m min " : "") . ($s > 0 ? "$s sec" : "");
	}
	return $Result;
}
function make_thumb($FileSrc) {
	// If PHP is installed with GD and jpeg support, uncomment the following line
	//execute_make_thumb($FileSrc);
}

// This function (execute_make_thumb) requires to install PHP with GD & jpeg-6 support
// GD -> http://www.boutell.com/gd/
// JPEG -> ftp://ftp.uu.net/graphics/jpeg/
function execute_make_thumb($FileSrc) {
	$tnH = 100;
	$size = getimagesize($FileSrc);
	$src = imagecreatefromjpeg($FileSrc);
	$destW = $size[0] * $tnH / $size[1];
	$destH = $tnH;
	$dest = imagecreate($destW, $destH); // creation de l'image de destination
	imagecopyresized($dest, $src, 0, 0, 0, 0, $destW, $destH, $size[0], $size[1]);
	$tn_name = $FileSrc;
	$tn_name = preg_replace("/\.(gif|jpe|jpg|jpeg|png|wbmp)$/i", "_tn", $tn_name);
	imagejpeg($dest, $tn_name . ".jpg");
}

/* Returns the rest of the euclidian division of '$d' by '$v'
 * Returns '0' if '$v' equals '0'
 * Supposes '$d' and '$v' are positive */
function modulo($d, $v) {
	if ($v == 0) {
		$Result = 0;
	} else {
		$s = floor($d / $v);
		$Result = $d - $v * $s;
	}
}

/* Returns the list of slot names '$val' corresponds to (as a bit field) */
function getslots($val) {
	global $dbslots;
	$all_slots = 23;
	if ($val == (2**$all_slots) - 1) {
		return $dbslots[$all_slots];
	} else if ($val == 0 ) {
		return "None";
	}
	$Result = array();
	for ($slot = 0; $slot < $all_slots; $slot++) {
		if ($val & (2**$slot)) {
			// EAR, FINGER, and WRIST appear twice in the list
			if (strlen($dbslots[$slot])) {
				array_push($Result, strtoupper($dbslots[$slot]));
			}
		}
	}
	return implode(" ", $Result);
}

function getclasses($val) {
	global $dbiclasses;
	$all_classes = 15;
	if ($val == (2**$all_classes) - 1) {
		return $dbiclasses[$all_classes];
	} else if ($val == 0 ) {
		return "None";
	}
	$Result = array();
	for ($class = 0; $class < $all_classes; $class++) {
		if ($val & (2**$class)) {
			array_push($Result, $dbiclasses[$class]);
		}
	}
	return implode(" ", $Result);
}

function getraces($val) {
	global $dbraces;
	$all_races = 14;
	if ($val == (2**$all_races) - 1) {
		return $dbraces[$all_races];
	} else if ($val == 0 ) {
		return "None";
	}
	$Result = array();
	for ($race = 0; $race < $all_races; $race++) {
		if ($val & (2**$race)) {
			array_push($Result, $dbraces[$race]);
		}
	}
	return implode(" ", $Result);
}
function getsize($val) {
	switch ($val) {
		case 0:
			return "TINY";
			break;
		case 1:
			return "SMALL";
			break;
		case 2:
			return "MEDIUM";
			break;
		case 3:
			return "LARGE";
			break;
		case 4:
			return "GIANT";
			break;
		default:
			return "$val?";
			break;
	}
}
function getspell($id) {
	global $tbspells, $tbspellglobals, $UseSpellGlobals, $db;
	if ($UseSpellGlobals == true) {
		$query = "SELECT " . $tbspells . ".* FROM " . $tbspells . " WHERE " . $tbspells . ".id=" . $id . "
			AND ISNULL((SELECT " . $tbspellglobals . ".spellid FROM " . $tbspellglobals . "
			WHERE " . $tbspellglobals . ".spellid = " . $tbspells . ".id))";
	} else {
		$query = "SELECT * FROM $tbspells WHERE id=$id";
	}
	$result = mysqli_query($db, $query) or message_die('functions.php', 'getspell', $query, mysqli_error($db));
	$s = mysqli_fetch_array($result);
	return $s;
}
function gedeities($val) {
	global $dbideities;
	reset($dbideities);
  $Result = '';
  $v = '';
	do {
		$key = key($dbideities);
		if ($key <= $val) {
			$val -= $key;
			$Result .= $v . current($dbideities);
			$v = ", ";
		}
	} while (next($dbideities));
	return $Result;
}
function SelectClass($name, $selected) {
	global $dbclasses;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Class</option>\n";
	for ($i = 1; $i <= 16; $i++) {
		print "<option value='" . $i . "'";
		if ($i == $selected) {
			print " selected='1'";
		}
		print ">" . $dbclasses[$i] . "</option>\n";
	}
	print "</SELECT>";
}
function SelectDeity($name, $selected) {
	global $dbideities;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Deity</option>\n";
	for ($i = 2; $i <= 65536; $i *= 2) {
		print "<option value='" . $i . "'";
		if ($i == $selected) {
			print " selected='1'";
		}
		print ">" . $dbideities[$i] . "</option>\n";
	}
	print "</SELECT>";
}
function SelectRace($name, $selected) {
	global $dbraces;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Race</option>\n";
	for ($i = 1; $i < 32768; $i *= 2) {
		print "<option value='" . $i . "'";
		if ($i == $selected) {
			print " selected='1'";
		}
		print ">" . $dbraces[$i] . "</option>\n";
	}
	print "</SELECT>";
}
function SelectMobRace($name, $selected) {
	global $dbiracenames;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Mob Race</option>\n";
	foreach ($dbiracenames as $key => $value) {
		print "<option value='" . $key . "'";
		if ($key == $selected) {
			print " selected='1'";
		}
		print ">" . $value . "</option>\n";
	}
	print "</SELECT>";
}
function SelectIClass($name, $selected) {
	global $dbiclasses;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Class</option>\n";
	for ($i = 1; $i < 15; $i++) {
		print "<option value='" . $i . "'";
		if ($i == $selected) {
			print " selected='1'";
		}
		print ">" . $dbiclasses[$i] . "</option>\n";
	}
	print "</SELECT>";
}
function SelectIType($name, $selected) {
	global $dbitypes;
	print "<SELECT name=\"$name\">";
	print "<option value='-1'>Type</option>\n";
	reset($dbitypes);
	do {
		$key = key($dbitypes);
		print "<option value='" . $key . "'";
		if ($key == $selected) {
			print " selected='1'";
		}
		print ">" . current($dbitypes) . "</option>\n";
	} while (next($dbitypes));
	print "</SELECT>";
}
function SelectSlot($name, $selected) {
	global $dbslots;
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Slot</option>\n";
	for ($i = 1; $i < 15; $i++) {
		$key = $dbslots[$i];
		print "<option value='" . $key . "'";
		if ($key == $selected) {
			print " selected='1'";
		}
		print ">" . $dbslots[$i] . "</option>\n";
	}
	print "</SELECT>";
}
function SelectSpellEffect($name, $selected) {
	global $dbspelleffects;
	print "<SELECT name=\"$name\">";
	print "<option value=-1>Effect</option>\n";
	reset($dbspelleffects);
	do {
		$key = key($dbspelleffects);
		print "<option value='" . $key . "'";
		if ($key == $selected) {
			print " selected='1'";
		}
		print ">" . current($dbspelleffects) . "</option>\n";
	} while (next($dbspelleffects));
	print "</SELECT>";
}
function SelectAugSlot($name, $selected) {
	print "<SELECT name=\"$name\">";
	print "<option value='0'>Augment Slot</option>\n";
	for ($i = 1; $i <= 25; $i++) {
		print "<option value='" . $i . "'";
		if ($i == $selected) {
			print " selected='1'";
		}
		print ">slot $i</option>\n";
	}
	print "</SELECT>";
}
function SelectLevel($name, $maxlevel, $selevel) {
	print "<SELECT name=\"$name\" class='level-select'>";
	print "<option value='0'>Level</option>\n";
	for ($i = 1; $i <= $maxlevel; $i++) {
		print "<option value='" . $i . "'";
		if ($i == $selevel) {
			print " selected='1'";
		}
		print ">$i</option>\n";
	}
	print "</SELECT>";
}
function SelectTradeSkills($name, $selected) {
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
function WriteIt($value, $name, $sel) {
	print "  <option value='" . $value . "'";
	if ($value == $sel) {
		print " selected='1'";
	}
	print ">$name</option>\n";
}
function SelectStats($name, $stat) {
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
function SelectHeroicStats($name, $stat, $heroic) {
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
function SelectResists($name, $resist) {
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
function SelectModifiers($name, $mod) {
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
function signred($stat) {
	$PrintString = "";
	if ($stat < 0) {
		$PrintString .= "<font color='red'>" . sign($stat) . "</font>";
	} else {
		$PrintString .= sign($stat);
	}
	return $PrintString;
}
function GetItemStatsString($name, $stat, $stat2, $stat2color) {
	if (!$stat2) {
		$stat2 = 0;
	}
	$PrintString = "";
	if (is_numeric($stat)) {
		if ($stat != 0 || $stat2 != 0) {
			$PrintString .= $name . ": ";
			if ($stat < 0) {
				$PrintString .= "<font color='red'>" . sign($stat) . "</font>";
			} else {
				$PrintString .= $stat;
			}
			if ($stat2 < 0) {
				$PrintString .= "<font color='red'> " . sign($stat2) . "</font>";
			} elseif ($stat2 > 0) {
				if ($stat2color) {
					$PrintString .= "<font color='" . $stat2color . "'> " . sign($stat2) . "</font>";
				} else {
					$PrintString .= sign($stat2);
				}
			}
		}
	} else {
		if (preg_replace("[^0-9]", "", $stat) > 0) {
			$PrintString .= $name . ": " . $stat;
		}
	}
	return $PrintString;
}

// spell_effects.cpp int Mob::CalcSpellEffectValue_formula(int formula, int base, int max, int caster_level, int16 spell_id)
function CalcSpellEffectValue($form, $base, $max, $lvl = 65) {
	// print " (base=$base form=$form max=$max, lvl=$lvl)";
	$sign = 1;
	$ubase = abs($base);
	$result = 0;
	if (($max < $base) and ($max != 0)) {
		$sign = -1;
	}
	switch ($form) {
		case 0:
		case 100:
			$result = $ubase;
			break;
		case 101:
			$result = $ubase + $sign * ($lvl / 2);
			break;
		case 102:
			$result = $ubase + $sign * $lvl;
			break;
		case 103:
			$result = $ubase + $sign * $lvl * 2;
			break;
		case 104:
			$result = $ubase + $sign * $lvl * 3;
			break;
		case 105:
		case 107:
			$result = $ubase + $sign * $lvl * 4;
			break;
		case 108:
			$result = floor($ubase + $sign * $lvl / 3);
			break;
		case 109:
			$result = floor($ubase + $sign * $lvl / 4);
			break;
		case 110:
			$result = floor($ubase + $lvl / 5);
			break;
		case 111:
			$result = $ubase + 5 * ($lvl - 16);
			break;
		case 112:
			$result = $ubase + 8 * ($lvl - 24);
			break;
		case 113:
			$result = $ubase + 12 * ($lvl - 34);
			break;
		case 114:
			$result = $ubase + 15 * ($lvl - 44);
			break;
		case 115:
			$result = $ubase + 15 * ($lvl - 54);
			break;
		case 116:
			$result = floor($ubase + 8 * ($lvl - 24));
			break;
		case 117:
			$result = $ubase + 11 * ($lvl - 34);
			break;
		case 118:
			$result = $ubase + 17 * ($lvl - 44);
			break;
		case 119:
			$result = floor($ubase + $lvl / 8);
			break;
		case 121:
			$result = floor($ubase + $lvl / 3);
			break;

		default:
			if ($form < 100) {
				$result = $ubase + ($lvl * $form);
			}
	} // end switch
	if ($max != 0) {
		if ($sign == 1) {
			if ($result > $max) {
				$result = $max;
			}
		} else {
			if ($result < $max) {
				$result = $max;
			}
		}
	}
	if (($base < 0) && ($result > 0)) {
		$result *= -1;
	}
	return $result;
}
function CalcBuffDuration($lvl, $form, $duration) { // spells.cpp, carefull, return value in ticks, not in seconds
	//print " Duration lvl=$lvl, form=$form, duration=$duration ";
	switch ($form) {
		case 0:
			return 0;
			break;
		case 1:
			$i = ceil($lvl / 2);
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 2:
			$i = ceil($duration / 5 * 3);
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 3:
			$i = $lvl * 30;
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 4:
			return $duration;
			break;
		case 5:
			$i = $duration;
			return ($i < 3 ? ($i < 1 ? 1 : $i) : 3);
			break;
		case 6:
			$i = ceil($lvl / 2);
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 7:
			$i = $lvl;
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 8:
			$i = $lvl + 10;
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 9:
			$i = $lvl * 2 + 10;
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 10:
			$i = $lvl * 3 + 10;
			return ($i < $duration ? ($i < 1 ? 1 : $i) : $duration);
			break;
		case 11:
		case 12:
			return $duration;
			break;
		case 50:
			return 72000;
		case 3600:
			return ($duration ? $duration : 3600);
	}
}
function SpecialAttacks($specialstr) {
	global $specialattack;
	$specials = explode("^", $specialstr);
	$result = array();
	foreach ($specials as $valuestr) {
		$values = explode(",", $valuestr);
		array_push($result, $specialattack[$values[0]]);
	}
	return implode(", ", $result);
}
function price($price) {
	$res = "";
	if ($price >= 1000) {
		$p = floor($price / 1000);
		$price -= $p * 1000;
	}
	if ($price >= 100) {
		$g = floor($price / 100);
		$price -= $g * 100;
	}
	if ($price >= 10) {
		$s = floor($price / 10);
		$price -= $s * 10;
	}
	$c = $price;
	if ($p > 0) {
		$res = $p . "p";
		$sep = " ";
	}
	if ($g > 0) {
		$res .= $sep . $g . "g";
		$sep = " ";
	}
	if ($s > 0) {
		$res .= $sep . $s . "s";
		$sep = " ";
	}
	if ($c > 0) {
		$res .= $sep . $c . "c";
	}
	return $res;
}
function sign($val) {
	if ($val > 0) {
		return "+$val";
	} else {
		return $val;
	}
}
function WriteDate($d) {
	return date("F d, Y", $d);
}
function isinteger($val) {
	return (intval($val) == $val);
}
function CanThisNPCDoubleAttack($class, $level) { // mob.cpp
	if ($level > 26) {
		return true;
	} #NPC over lvl 26 all double attack
	switch ($class) {
		case 0: # monks and warriors
		case 1:
		case 20:
		case 26:
		case 27:
			if ($level < 15) {
				return false;
			}
			break;
		case 9: # rogues
		case 28:
			if ($level < 16) {
				return false;
			}
			break;
		case 4: # rangers
		case 23:
		case 5: # shadowknights
		case 24:
		case 3: # paladins
		case 22:
			if ($level < 20) {
				return false;
			}
			break;
	}
	return false;
}

// Automatically format and populate the table based on the query
function AutoDataTable($Query) {
  global $db;
	$result = mysqli_query($db, $Query);
	if (!$result) {
		echo 'Could not run query: ' . mysqli_error($db);
		exit;
	}
	$columns = mysqli_num_fields($result);
	echo "<table border=0 width=100%><thead>";
	$RowClass = "lr";
	###Automatically Generate the column names from the Table
	for ($i = 0; $i < $columns; $i++) {
		echo "<th class='menuh'>" . ucfirstwords(str_replace('_', ' ', mysqli_field_name($result, $i))) . " </th>";
	}
	echo "</tr></thead><tbody>";
	while ($row = mysqli_fetch_array($result)) {
		echo "<tr class='" . $RowClass . "'>";
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
function Pagination($targetpage, $page, $total_pages, $limit, $adjacents) {

	/* Setup page vars for display. */
	if ($page == 0) {
		$page = 1;
	}					//if no page var is given, default to 1.
	$prev = $page - 1;							//previous page is page - 1
	$next = $page + 1;							//next page is page + 1
	$lastpage = ceil($total_pages / $limit);		//lastpage is = total pages / items per page, rounded up.
	$lpm1 = $lastpage - 1;						//last page minus 1

	$pagination = "";
	if ($lastpage > 1) {
		$pagination .= "<div class=\"pagination\">";
		//previous button
		if ($page > 1) {
			$pagination .= "<a href=\"$targetpage?page=$prev\">previous</a>";
		} else {
			$pagination .= "<span class=\"disabled\">previous</span>";
		}

		//pages
		if ($lastpage < 7 + ($adjacents * 2)) {	//not enough pages to bother breaking it up
			for ($counter = 1; $counter <= $lastpage; $counter++) {
				if ($counter == $page) {
					$pagination .= "<span class=\"current\">$counter</span>";
				} else {
					$pagination .= "<a href=\"$targetpage?page=$counter\">$counter</a>";
				}
			}
		} elseif ($lastpage > 5 + ($adjacents * 2)) {	//enough pages to hide some
			//close to beginning; only hide later pages
			if ($page < 1 + ($adjacents * 2)) {
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
					if ($counter == $page) {
						$pagination .= "<span class=\"current\">$counter</span>";
					} else {
						$pagination .= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
				}
				$pagination .= "...";
				$pagination .= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
				$pagination .= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
			}
			//in middle; hide some front and some back
			elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
				$pagination .= "<a href=\"$targetpage?page=1\">1</a>";
				$pagination .= "<a href=\"$targetpage?page=2\">2</a>";
				$pagination .= "...";
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
					if ($counter == $page) {
						$pagination .= "<span class=\"current\">$counter</span>";
					} else {
						$pagination .= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
				}
				$pagination .= "...";
				$pagination .= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
				$pagination .= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
			}
			//close to end; only hide early pages
			else {
				$pagination .= "<a href=\"$targetpage?page=1\">1</a>";
				$pagination .= "<a href=\"$targetpage?page=2\">2</a>";
				$pagination .= "...";
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
					if ($counter == $page) {
						$pagination .= "<span class=\"current\">$counter</span>";
					} else {
						$pagination .= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
				}
			}
		}

		//next button
		if ($page < $counter - 1) {
			$pagination .= "<a href=\"$targetpage?page=$next\">next</a>";
		} else {
			$pagination .= "<span class=\"disabled\">next</span>";
		}
		$pagination .= "</div>\n";
	}
	return $pagination;
}

function getPetFocus($id)
{
	$power = 0;
	$max_level = 0;
	$min_level = 0;
	$pet_type = "";
	switch($id)
	{
	case 20508: // Symbol of Ancient Summoning
		$power = 25;
		$max_level = 60;
		$min_level = 40;
		$pet_type = "All";
		break;
	case 28144: // Gloves of Dark Summoning
		$power = 20;
		$max_level = 60;
		$min_level = 40;
		$pet_type = "All";
		break;
	case 11571: // Encyclopedia Necrotheurgia
		$power = 10;
		$max_level = 48;
		$min_level = 40;
		$pet_type = "Necro";
		break;
	case 11569: // Staff of Elemental Mastery: Water
		$power = 10;
		$max_level = 48;
		$min_level = 40;
		$pet_type = "Water";
		break;
	case 11567: // Staff of Elemental Mastery: Earth
		$power = 10;
		$max_level = 48;
		$min_level = 40;
		$pet_type = "Earth";
		break;
	case 11566: // Staff of Elemental Mastery: Fire
		$power = 10;
		$max_level = 48;
		$min_level = 40;
		$pet_type = "Fire";
		break;
	case 11568: // Staff of Elemental Mastery: Air
		$power = 10;
		$max_level = 48;
		$min_level = 40;
		$pet_type = "Air";
		break;
	case 6360:  // Broom of Trilon
		$power = 5;
		$max_level = 41;
		$min_level = 4;
		$pet_type = "Air";
		break;
	case 6361:  // Shovel of Ponz
		$power = 5;
		$max_level = 41;
		$min_level = 4;
		$pet_type = "Earth";
		break;
	case 6362:  // Torch of Alna
		$power = 5;
		$max_level = 41;
		$min_level = 4;
		$pet_type = "Fire";
		break;
	case 6363:  // Stein of Ulissa
		$power = 5;
		$max_level = 41;
		$min_level = 4;
		$pet_type = "Water";
		break;
	}
	if ($power > 0)
	{
		return "Pet Focus: $pet_type $power (Lvl: $min_level-$max_level)";
	}
	return "";
}

function GetItemRow($id) {
	global $db, $tbitems, $DiscoveredItemsOnly, $hide_item_id;
	if ($id != "" && is_numeric($id)) {
		if ($DiscoveredItemsOnly == true) {
			$Query = "SELECT * FROM $tbitems, discovered_items WHERE $tbitems.id='" . $id . "' AND discovered_items.item_id=$tbitems.id";
		} else {
			$Query = "SELECT * FROM $tbitems WHERE id='" . $id . "'";
		}
		foreach ($hide_item_id as $hideme) {
			$Query .= " AND $tbitems.id != $hideme"; // Block by ID set in config
		}
		$QueryResult = mysqli_query($db, $Query) or message_die('item.php', 'MYSQL_QUERY', $Query, mysqli_error($db));
		if (mysqli_num_rows($QueryResult) == 0) {
			header("Location: items.php");
			exit();
		}
		$ItemRow = mysqli_fetch_array($QueryResult);
		$name = $ItemRow["Name"];
	} elseif ($name != "") {
		if ($DiscoveredItemsOnly == true) {
			$Query = "SELECT * FROM $tbitems, discovered_items WHERE $tbitems.name like '$name' AND discovered_items.item_id=$tbitems.id";
		} else {
			$Query = "SELECT * FROM $tbitems WHERE name like '$name'";
		}
		$QueryResult = mysqli_query($db, $Query) or message_die('item.php', 'MYSQL_QUERY', $query, mysqli_error($db));
		if (mysqli_num_rows($QueryResult) == 0) {
			header("Location: items.php?iname=" . $name . "&isearch=true");
			exit();
		} else {
			$ItemRow = mysqli_fetch_array($QueryResult);
			$id = $ItemRow["id"];
			$name = $ItemRow["name"];
		}
	} else {
		header("Location: items.php");
		exit();
	}
	return $ItemRow;
}

// Function to build item stats tables
// Used for item.php as well as for tooltips for items
function BuildItemStats($item, $show_name_icon) {
	global $db, $dbitypes, $dam2h, $dbbagtypes, $dbskills, $icons_url, $tbspells, $dbiracenames, $dbelements, $dbbodytypes, $dbbardskills, $expansion;

	$html_string = "";
	if ($show_name_icon) {
		$html_string .= "<h4 style='margin-top:0'>" . $item["Name"] . "</h4></td>";
		$html_string .= "<img src='" . $icons_url . "item_" . $item["icon"] . ".gif' align='right' valign='top'/>";
	}

	// Add the Lore string if any. This isn't displayed in game.
	$lorestr = ltrim($item["lore"], "#*");
	if ($lorestr != $item["Name"] && strlen($lorestr))
	{
		$html_string .= "<p>Item Lore: $lorestr</p>\n";
	}

	// In game, top line is any of MAGIC, LORE ITEM, ARTIFACT, NO DROP, NORENT.
	// This line is always present even if empty (hence, &nbsp)
	$line = array();
	if ($item["magic"] == 1) {
		array_push($line, "MAGIC ITEM");
	}
	$i = 0;
	if ($item["lore"][$i] == '*') {
		$i++;
		array_push($line, "LORE ITEM");
	}
	if ($item["lore"][$i] == '#') {
		array_push($line, "ARTIFACT");
	}
	if ($item["nodrop"] == 0) {
		array_push($line, "NO DROP");
	}
	if ($item["norent"] == 0) {
		array_push($line, "NORENT");
	}
	// Print and clear
	$html_string .= "<p>" . implode(" ", $line) . "&nbsp;</p>\n";
	$line = array();

	// Items that are not books
	if ($item["itemclass"] == 0)
	{
		// Slot: PRIMARY
		if ($item["slots"] > 0) {
			$html_string .= "<p>Slot: " . getslots($item["slots"]) . "</p>\n";
		}

		// EXPENDABLE Charges: XX
		if ($item["maxcharges"] > 0) {
			if ($item["clicktype"] == 3) {
				array_push($line, "EXPENDABLE");
			}
			array_push($line, "Charges:", $item["maxcharges"]);
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Skill: Xyz Delay
		$TypeString = "";
		switch ($item["itemtype"]) {
			case 0: // 1HS
			case 2: // 1HP
			case 3: // 1HB
			case 45: // H2H
			case 5: // Archery
			case 1: // 2hs
			case 4: // 2hb
			case 35: // 2hp
				$TypeString = "Skill";
				array_push($line, "Skill:");
				array_push($line, $dbitypes[$item["itemtype"]]);
				break;
		}
		if ($item["delay"]) {
			array_push($line, "Atk Delay:");
			array_push($line, $item["delay"]);
		}
		if (count($line)) {
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Level Needed:
		// Skill: Alteration
		// Mana Cost: ##
		if (($item["scrolleffect"] > 0) && ($item["scrolleffect"] < 65535)) {
			$manacost = 0;
			$skill = 0;
			$query = "SELECT mana, skill FROM $tbspells WHERE id=" . $item["scrolleffect"];
			$QueryResult = mysqli_query($db, $query);
			if (mysqli_num_rows($QueryResult) > 0) {
				$rows = mysqli_fetch_array($QueryResult);
				$manacost = $rows["mana"];
				$skill = $rows["skill"];

				$html_string .= "<p>Skill: " . $dbskills[$skill] . "</p>\n";
				$html_string .= "<p>Mana Cost: " . $manacost . "</p>\n";
			}
		}
		
		// DMG, Bonus, AC
		if ($item["damage"] > 0) {
			switch ($item["itemtype"]) {
				case 0: // 1HS
				case 2: // 1HP
				case 3: // 1HB
				case 45: // H2H
				case 5: // Archery
					$dmgbonus = 13; // floor((65-25)/3)  main hand
					break;
				case 1: // 2hs
				case 4: // 2hb
				case 35: // 2hp
					$dmgbonus = $dam2h[$item["delay"]];
					break;
				default:
					$dmgbonus = $item["itemtype"];
			}
			array_push($line, "DMG:", $item["damage"]);
			if ($dmgbonus) {
				array_push($line, "Dmg Bonus:", $dmgbonus);
			}
		}
		if ($item["ac"]) {
			array_push($line, "AC:", $item["ac"]);
		}
		if (count($line)) {
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Cold DMG: ##
		if ($dbelements[$item["elemdmgtype"]] != 'Unknown') {
			array_push($line, $dbelements[$item["elemdmgtype"]], "DMG:", $item["elemdmgamt"]);
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Bane DMG: Type ##
		if ($expansion >=3 && $item["banedmgamt"] != 0) {
			$banetarget = "";
			$banetype = "";
			if ($item["banedmgrace"] > 0) {
				$banetarget = $dbiracenames[$item["banedmgrace"]];
				$banetype = "Race";
			} else if ($item["banedmgbody"] > 0) {
				$banetarget = $dbbodytypes[$item["banedmgbody"]];
				$banetype = "Body";
			}
			array_push($line, "Bane DMG:", $banetarget, sign($item["banedmgamt"]), "($banetype)");
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// #Skill Mod
		if ($expansion >=3 && ($item["skillmodtype"] > 0) && ($item["skillmodvalue"] != 0)) {
			$html_string .= "<p>Skill Mod: " . $dbskills[$item["skillmodtype"]] . " " . sign($item["skillmodvalue"]) . "%</p>\n";
		}

		// Bard Skill
		if ($item["bardtype"] > 0) {
			$val = ($item["bardvalue"] * 10) - 100;
			$html_string .= $dbitypes[$item["bardtype"]] . " (" . sign($val) . "%)";
		}


		// STR: # DEX: # STA: # CHA: # WIS: # INT: # AGI: # HP: # MANA: #
		if ($item["astr"])
			array_push($line, "STR:", signred($item["astr"]));
		if ($item["adex"])
			array_push($line, "DEX:", signred($item["adex"]));
		if ($item["asta"])
			array_push($line, "STA:", signred($item["asta"]));
		if ($item["acha"])
			array_push($line, "CHA:", signred($item["acha"]));
		if ($item["awis"])
			array_push($line, "WIS:", signred($item["awis"]));
		if ($item["aint"])
			array_push($line, "INT:", signred($item["aint"]));
		if ($item["aagi"])
			array_push($line, "AGI:", signred($item["aagi"]));
		if ($item["hp"])
			array_push($line, "HP:", signred($item["hp"]));
		if ($item["mana"])
			array_push($line, "MANA:", signred($item["mana"]));
		if (count($line)) {
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		# SV FIRE: # SV DISEASE: # SV COLD: # SV MAGIC: # SV POISON: #
		if ($item["fr"])
			array_push($line, "SV FIRE:", signred($item["fr"]));
		if ($item["dr"])
			array_push($line, "SV DISEASE:", signred($item["dr"]));
		if ($item["cr"])
			array_push($line, "SV COLD:", signred($item["cr"]));
		if ($item["mr"])
			array_push($line, "SV MAGIC:", signred($item["mr"]));
		if ($item["pr"])
			array_push($line, "SV POISON:", signred($item["pr"]));
		if (count($line)) {
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Recommended level of ##. Required level of ##.
		if ($item["reclevel"] > 0) {
			array_push($line, "Recommended level of " . $item["reclevel"] . ".");
		}
		if ($item["reqlevel"] > 0) {
			array_push($line, "Required level of " . $item["reqlevel"] . ".");
		}
		if (count($line)) {
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Effect: 
		if (($item["proceffect"] > 0) && ($item["proceffect"] != $item["worneffect"])) {
			$spellname = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["proceffect"]);
			array_push($line, "Effect:", "<a href='spell.php?id=" . $item["proceffect"] . "'>$spellname</a>", "(Combat)");

			$proclevel = $item["proclevel"] ?: $item["proclevel2"];
			if (!$proclevel) {
				$proclevel = 1;
			}
			array_push($line, "(Lvl: " . $proclevel . ")");
			$procrate = 100 + $item["procrate"];
			array_push($line, "(Rate: $procrate%)");
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		if (($item["worneffect"] > 0 || $item["proceffect"] > 0) && $item["worntype"] == 2) {
			$effect = $item["worneffect"] ?: $item["proceffect"];
			$spellname = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $effect);
			array_push($line, "Effect:", "<a href='spell.php?id=" . $effect . "'>$spellname</a>", "(Worn)");
			if ($item["worneffect"] == 998) {
				$haste = (int) $item["wornlevel"];
				$haste++;
				array_push($line, "(" . $haste . "%)");
			} else {
				array_push($line, "(Lvl: " . $item["wornlevel"]. ")");
			}
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Effect: Aura of Bravery (Worn)
		if (($expansion >= 3) && ($item["focuseffect"] > 0) && ($item["focuseffect"] < 65535)) {
			$spellname = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["focuseffect"]);
			array_push($line, "Focus Effect:", "<a href='spell.php?id=" . $item["focuseffect"] . "'>$spellname</a>");
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// Effect: Allure (Casting Time: Instant)
		// Effect: Divine Aura (Must Equip. Casting Time: 1.0)
		// Effect: Identify (Casting Time: 4.0)
		if (($item["clickeffect"] > 0) && ($item["clickeffect"] < 65535)) {
			$spellname = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["clickeffect"]);
			array_push($line, "Effect:", "<a href='spell.php?id=" . $item["clickeffect"] . "'>$spellname</a>");

			if ($item["clicktype"] == 4) {
				array_push($line, "(Must Equip. Casting Time:");
			} else {
				array_push($line, "(Casting Time:");
			}

			if ($item["casttime"] > 0) {
				array_push($line, ($item["casttime"] / 1000) . ")");
			} else {
				array_push($line, "Instant)");
			}
	    
			if ($item["clicklevel"] > 0) {
				array_push($line, "(Lvl", $item["clicklevel"] . ")");
			}
			$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
			$line = array();
		}

		// WT: ## Range: ## Size: ##
		array_push($line, "WT:", $item["weight"]/10);
		if ($item["range"] > 0) {
			array_push($line, "Range:", $item["range"]);
		}
		array_push($line, "Size:", getsize($item["size"]));
		$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
		$line = array();

		// Class: XXX YYY ZZZ
		$html_string .= "<p>Class: " . getclasses($item["classes"]) . "</p>\n";

		// Race: XXX YYY ZZZ
		$html_string .= "<p>Race: " . getraces($item["races"]) . "</p>\n";

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
		// $html_string .= $ItemValue ."</p>\n";


		if (($item["scrolleffect"] > 0) && ($item["scrolleffect"] < 65535)) {
			array_push($line, "Level Needed:", $item["scrolllevel"] . "-" . $item["scrolllevel2"]);
			$html_string .= "<p>Spell Scroll Effect: <a href='spell.php?id=" . $item["scrolleffect"] . "'>" . GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $item["scrolleffect"]) . "</a></p>\n";
		}

	}
	// Bags
	else if ($item["itemclass"] == 1)
	{
		// Container:
		$html_string .= "<p>Container: CLOSED</p>\n";

		// WT: ## Weight Reduction ##%
		array_push($line, "WT:", $item["weight"]/10);
		array_push($line, "Weight Reduction:", $item["bagwr"] . "%");
		$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
		$line = array();

		// Capacity: ## Size Capacity: XXX
		array_push($line, "Capacity:", $item["bagslots"]);
		array_push($line, "Size Capacity:", getsize($item["bagsize"]));
		$html_string .= "<p>" . implode(" ", $line) . "</p>\n";
		$line = array();

		$bagtype = $dbbagtypes[$item["bagtype"]];
		if ($bagtype) {
			$html_string .= " <p>Trade Skill Container: " . $dbbagtypes[$item["bagtype"]] . "</p>\n";
		}
	}
	// Books
	else if ($item["itemclass"] == 2)
	{
		$html_string .= "The Book is closed.";
	}

	if ($item["deity"] > 0) {
		$html_string .= "<p><strong>Deity:</strong> " . gedeities($item["deity"]) . "</p>\n";
	}

	return $html_string;
}

function debug($data) {
	print "<pre>";
	print "Admin Debug Panel:\r\n";
	print print_r($data);
	print "</pre>";
}
function isadmin() {
	global $admin;
	if ($_SERVER['REMOTE_ADDR'] === $admin) {
		return true;
	}
	return false;
}
function admindebug($content) {
	if (isadmin()) {
		debug($content);
	}
}

function getcookie($name, $default) {
	return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
}

function contentflagfilter() {
	$contentflags = array(
		"EquestrielleCorrupted"=>getcookie("EquestrielleCorrupted", true),
		"OldPlane_Hate_Sky"=>getcookie("OldPlane_Hate_Sky", true),
		"OldPlane_Fear"=>getcookie("OldPlane_Fear", true),
		"Classic_OldWorldDrops"=>getcookie("Classic_OldWorldDrops", false),
		"anniversary"=>false
	);
	$cf = array();
	foreach($contentflags as $key=>$value) {
		if ($value) {
			array_push($cf, "'".$key."'");
		}
	}
	return implode(",", $cf);
}

function gatefilter($tables) {
	global $expansion;
	$filter = "";
	$cf = contentflagfilter();

	foreach($tables as $table) {
		if ($table == "zone") {
			$filter .="
				AND $table.expansion <= $expansion";
		} else {
			$filter .="
				AND ($table.content_flags IS NULL OR $table.content_flags IN ($cf))
				AND ($table.content_flags_disabled IS NULL OR $table.content_flags_disabled NOT IN ($cf))
				AND ($table.min_expansion = -1 OR $table.min_expansion <= $expansion)
				AND ($table.max_expansion = -1 OR $table.max_expansion >= $expansion)";
		}
	}
	return $filter;
}

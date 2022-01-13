<?php

#ini_set('display_errors', 'On');
#error_reporting(E_ALL);

include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');
$Title = "Spell Search";
include($includes_dir . 'headers.php');
include($includes_dir . 'spell.inc.php');

$opt = (isset($_GET['opt']) ? $_GET['opt'] : '');
$namestring = (isset($_GET['name']) ? $_GET['name'] : '');
$level = (isset($_GET['level']) ? $_GET['level'] : 0);
$type = (isset($_GET['type']) ? $_GET['type'] : 0);

$check1 = "";
$check2 = "";
$check3 = "";

if ($opt == 1) {
	$check1 = "checked";
	$OpDiff = 0;
	$ClassOper = "=";
} elseif ($opt == 2) {
	$check2 = "checked";
	$OpDiff = -1;
	$ClassOper = ">=";
} elseif ($opt == 3) {
	$check3 = "checked";
	$OpDiff = 1;
	$ClassOper = "<=";
} else {
	$check2 = "checked";
	$OpDiff = 0;
	$ClassOper = ">=";
}

/* Display Spell Form */
echo '<div class="container spell-search">
			<form name="f" action="">
            <input type="text" name="name" size="40" value="' . $namestring . '" /></td></tr>
            <select name="type">
            <optgroup label="Class">
                <option value="0"' . ($type == 0 ? ' selected="1"' : '') . '>All Classes</option>
                <option value="8"' . ($type == 8 ? ' selected="1"' : '') . '>Bard</option>
                <option value="15"' . ($type == 15 ? ' selected="1"' : '') . '>Beastlord</option>
                <option value="16"' . ($type == 16 ? ' selected="1"' : '') . '>Berserker</option>
                <option value="2"' . ($type == 2 ? ' selected="1"' : '') . '>Cleric</option>
                <option value="6"' . ($type == 6 ? ' selected="1"' : '') . '>Druid</option>
                <option value="14"' . ($type == 14 ? ' selected="1"' : '') . '>Enchanter</option>
                <option value="13"' . ($type == 13 ? ' selected="1"' : '') . '>Magician</option>
                <option value="7"' . ($type == 7 ? ' selected="1"' : '') . '>Monk</option>
                <option value="11"' . ($type == 11 ? ' selected="1"' : '') . '>Necromancer</option>
                <option value="3"' . ($type == 3 ? ' selected="1"' : '') . '>Paladin</option>
                <option value="4"' . ($type == 4 ? ' selected="1"' : '') . '>Ranger</option>
                <option value="9"' . ($type == 9 ? ' selected="1"' : '') . '>Rogue</option>
                <option value="5"' . ($type == 5 ? ' selected="1"' : '') . '>Shadowknight</option>
                <option value="10"' . ($type == 10 ? ' selected="1"' : '') . '>Shaman</option>
                <option value="1"' . ($type == 1 ? ' selected="1"' : '') . '>Warrior</option>
                <option value="12"' . ($type == 12 ? ' selected="1"' : '') . '>Wizard</option>
            </optgroup>
			</select>

            <select name="level">
            <optgroup label="Level">
			<option value="">Any Level</option>';

for ($i = 1; $i <= $ServerMaxLevel; $i++) {
	echo '<option value="' . $i . '"' . ($level == $i ? ' selected="1"' : '') . '>' . $i . '</option>';
}

echo '</optgroup></select>
			<label><input type="radio" name="opt" value="1" ' . $check1 . ' />Only</label> 
			<label><input type="radio" name="opt" value="2" ' . $check2 . ' />And Higher</label> 
			<label><input type="radio" name="opt" value="3" ' . $check3 . ' />And Lower</label>
			<input type="submit" value="Search"/>
			<input type="hidden" name="action" value="search"/>
			<input type="reset" value="Reset"/>
			</form>';
/* End Display Spell Form */

/* Start Data Pull */

if (($type != 0 && $level != 0) || $namestring != '') {
	if (!$level) {
		$level = 0;
		$ClassOper = ">";
	}

	$sql = 'SELECT ' . $tbspells . '.* FROM ' . $tbspells . ' WHERE';
	$sv = '';

	if ($type) {
		$sql .= ' ' . $tbspells . '.classes' . $type . " " . $ClassOper .  " " . $level . ' 
					AND ' . $tbspells . '.classes' . $type . ' <= ' . $ServerMaxLevel;
		$sv = 'AND';
	}

	$sql .= ' ' . $sv . ' ' . $tbspells . '.name LIKE \'%' . addslashes($namestring) . '%\'';

	if ($UseSpellGlobals == TRUE) {
		$sql .= ' AND ISNULL((SELECT ' . $tbspellglobals . '.spellid FROM ' . $tbspellglobals . ' 
				WHERE ' . $tbspellglobals . '.spellid = ' . $tbspells . '.id))';
	}

	if ($type != 0) {
		$sql .= ' ORDER BY ' . $tbspells . '.classes' . $type . ', ' . $tbspells . '.name';
	} else {
		$sql .= ' ORDER BY ' . $tbspells . '.name LIMIT ' . $MaxItemsReturned;
	}

	$result = mysqli_query($db, $sql);
	if (!$result) {
		die('Invalid query: ' . mysqli_error($db));
	}

	echo ' <table border="0" cellpadding="5" cellspacing="15">';

	$LevelCheck = $level + $OpDiff;
	$Class = 'classes' . $type;
	$ClassName = $dbclasses[$type];
	$RowClass = "lr";

	while ($row = mysqli_fetch_array($result)) {
		/* This will only come through when the Level Changes */
		$DBSkill = $dbskills[$row["skill"]];
		if ($LevelCheck != $row[$Class]) {
			$LevelCheck = $row[$Class];
			echo '<tr class="level"><td colspan="4"><h3>Level: ' . $row['classes' . $type] . '</h3></td></tr>';
			echo '<tr>
                        <td colspan=2>Name</td>
                        <td class="left">Class</td>
                        <td>Effect(s)</td>
                        <td class="center">Mana</td>
                        <td class="center">Skill</td>
                        <td class="right">Target</td>
                      </tr>';
		}
		echo '<tr>
					<td width="5%">
                        <a href="spell.php?id=' . $row['id'] . '"><img src="' . $icons_url . $row['new_icon'] . '.gif" align="center" border="1"></a>
                    </td>
					<td width="20%">
                        <a href="spell.php?id=' . $row['id'] . '">' . $row['name'] . '</a>
                    </td>
					<td width="10%">' . $ClassName . " " . $LevelCheck . '</td>
					<td width="40%"><ul>';
		for ($n = 1; $n <= 12; $n++) {
			SpellDescription($row, $n);
		}
		echo '</ul></td>
					<td width="5%" class="center">' . $row['mana'] . '</td>
					<td width="10%" class="center">' . ucwords(strtolower($DBSkill)) . '</td>
					<td width="10%" class="right">';
		if ($dbspelltargets[$row["targettype"]] != "") {
			print $dbspelltargets[$row["targettype"]];
		}
		echo '</td></tr>';
	}
	echo '</table>';
}
echo '</div>';
echo '</div>';

include($includes_dir . "footers.php");

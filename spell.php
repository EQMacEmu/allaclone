<?php
include('./includes/constantes.php');
include('./includes/config.php');
include($includes_dir . 'mysql.php');
include($includes_dir . 'functions.php');
include($includes_dir . 'spell.inc.php');

$id = $_GET["id"];
$spell = getspell($id);
$minlvl = 70;

if (!$spell || in_array($id, $hide_spell_id)) {
	header("Location: spells.php");
	exit();
}

include($includes_dir . 'headers.php');

function OutputClasses() {
	global $dbclasses, $spell, $minlvl;
	$v = "";
	for ($i = 1; $i <= 16; $i++) {
		if (($spell["classes$i"] > 0) and ($spell["classes$i"] < 255)) {
			print "$v " . $dbclasses[$i] . " (" . $spell["classes$i"] . ")";
			$v = ",";
			if ($spell["classes$i"] < $minlvl) {
				$minlvl = $spell["classes$i"];
			}
		}
	}
}

function GetReagents() {
	global $spell, $tbitems;
	$string = array();
	for ($i = 1; $i <= 4; $i++) {
		if ($spell["components" . $i] > 0) {
			$itemName = GetFieldByQuery("Name", "SELECT Name FROM $tbitems WHERE id=" . $spell["components" . $i]);
			$id = $spell["components" . $i];
			array_push($string, "<a data-item-id=\"$id\" class=\"item-link\" style='display: inline; color: black; padding: 0;' href='item.php?id=$id'>" . $itemName . " </a>(" . $spell["component_counts" . $i] . ")");
		}
	}
	return $string;
}

function OutputItems($type, $id) {
	global $db, $tbitems;
	$itemQuery = "SELECT $tbitems.id,$tbitems.name
		FROM $tbitems
		WHERE $tbitems.${type}effect=$id
		ORDER BY $tbitems.name ASC";
	$result = mysqli_query($db, $itemQuery) or message_die('item.php', 'MYSQL_QUERY', $itemQuery, mysqli_error($db));

	$string = "None";
	if (mysqli_num_rows($result)) {
		$string = "<ol>";
		while ($row = mysqli_fetch_array($result)) {
			$id = $row["id"];
			$string .= "<li><a data-item-id=\"$id\" class=\"item-link\" href=item.php?id=" . $row["id"] . ">" . $row["name"] . "</a></li>";
		}
		$string .= "</ol>";
	}

	return $string;
}

$duration = CalcBuffDuration($minlvl, $spell["buffdurationformula"], $spell["buffduration"]);
?>

<div class="container spell-details">
	<div class="spell-information">
		<dl class="spell-details">
			<dt class="spell-name">
				<?php
				if (file_exists(getcwd() . "/icons/{$spell['new_icon']}.gif")) {
					echo "<img id='spell-icon' src='{$icons_url}{$spell['new_icon']}.gif' alt='{$spell["name"]}' />";
				}
				?>
				<strong><?= ($spell["name"]) ? $spell['name'] : null ?></strong>
			</dt>
			<dd><strong>Classes:</strong> <?php OutputClasses($dbclasses, $spell); ?></dd>
			<?php if ($spell['you_cast']) { ?>
				<dd><strong>When you cast:</strong> <?= $spell["you_cast"] ?></dd>
			<?php } ?>
			<?php if ($spell['other_casts']) { ?>
				<dd><strong>When others cast:</strong> <?= $spell["other_casts"] ?></dd>
			<?php } ?>
			<?php if ($spell['cast_on_you']) { ?>
				<dd><strong>When cast on you:</strong> <?= $spell["cast_on_you"] ?></dd>
			<?php } ?>
			<?php if ($spell['cast_on_other']) { ?>
				<dd><strong>When cast on other:</strong> <?= "Target" . $spell["cast_on_other"] ?></dd>
			<?php } ?>
			<?php if ($spell['spell_fades']) { ?>
				<dd><strong>When fading:</strong> <?= $spell["spell_fades"] ?></dd>
			<?php } ?>
			<?php if ($spell['mana']) { ?>
				<dd><strong>Mana Cost:</strong> <?= $spell["mana"] ?></dd>
			<?php } ?>
			<dd><strong>Spell Type:</strong> <?= ($spell["goodEffect"]) ? "Beneficial" : "Detrimental" ?></dd>
			<dd><strong>Skill:</strong> <?= ($spell["skill"] < 73) ? $dbskills[$spell["skill"]] : null ?></dd>
			<?php if ($spell['cast_time']) { ?>
				<dd><strong>Cast Time:</strong> <?= $spell["cast_time"] / 1000 . " seconds" ?></dd>
			<?php } ?>
			<?php if ($spell['recovery_time']) { ?>
				<dd><strong>Recovery Time:</strong> <?= $spell["recovery_time"] / 1000 . " seconds" ?></dd>
			<?php } ?>
			<?php if ($spell['recast_time']) { ?>
				<dd><strong>Recast Time:</strong> <?= $spell["recast_time"] / 1000 . " seconds" ?></dd>
			<?php } ?>
			<?php if ($spell['basediff']) { ?>
				<dd><strong>Fizzle Adjust:</strong> <?= $spell["basediff"] ?></dd>
			<?php } ?>
			<?php if ($spell['aoerange']) { ?>
				<dd><strong>AoE Range:</strong> <?= $spell['aoerange']; ?> units</dd>
			<?php } ?>
			<?php if ($spell['pushback']) { ?>
				<dd><strong>Pushback:</strong> <?= $spell['pushback']; ?></dd>
			<?php } ?>
			<?php if ($spell['range']) { ?>
				<dd><strong>Spell Range:</strong> <?= $spell["range"] . " units" ?></dd>
			<?php } ?>
			<dd><strong>Target:</strong> <?= ($dbspelltargets[$spell["targettype"]]) ? $dbspelltargets[$spell["targettype"]] : "Unknown Target ({$spell["targettype"]})" ?></dd>
			<dd><strong>Resist:</strong>
				<?= ($dbspellresists[$spell["resisttype"]]) ? $dbspellresists[$spell["resisttype"]] : "Unspecified" ?>
				<?= ($spell["ResistDiff"]) ? "(" . $spell["ResistDiff"] . ")" : "(Adjust: 0)" ?>
			</dd>
			<dd><strong>Cast Time Restriction:</strong> <?= ($spell["TimeOfDay"] === 2) ? "Night" : "None" ?></dd>
			<dd><strong>Duration:</strong> <?= ($duration === 0) ? "Instant" : translate_time($duration * 6) . " ({$duration} ticks)" ?></dd>
			<?php foreach (GetReagents() as $reagent) { ?>
				<dd><strong>Reagents:</strong> <?= $reagent; ?></dd>
			<?php } ?>
			<dd>
				<strong>Spell Effects:</strong>
				<ol>
					<?php OutputEffects(); ?>
				</ol>
			</dd>
			<?php
			$types = array("scroll", "focus", "proc", "worn", "click");
			foreach ($types as $type)
			{
				$output = OutputItems($type, $id);
				if ($output !== "None") {
					print "<dd><strong>Items with this \"$type\" effect:</strong>";
					print $output;
					print "</dd>";
				}
			}
			?>
		</dl>
	</div>
</div>
</div>

<?php
include($includes_dir . "footers.php");

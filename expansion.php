<?php
$defaultExpansion = 4;

if (isset($_COOKIE['expansion']) && is_numeric($_COOKIE['expansion']) && $_COOKIE['expansion'] >= 0 && $_COOKIE['expansion'] <= 4) {
	$expansion = (int)$_COOKIE['expansion'];
} else {
	$expansion = $defaultExpansion;
	setcookie('expansion', $defaultExpansion, time() + (86400 * 30), "/");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expansion'])) {
	$newExpansion = (int)$_POST['expansion'];
	if ($newExpansion >= 0 && $newExpansion <= 4) {
		$expansion = $newExpansion;
		setcookie('expansion', $newExpansion, time() + (86400 * 30), "/"); // Update cookie for 30 days
	}
}
$expansionNames = [
	"Classic",
	"Ruins of Kunark",
	"Scars of Velious",
	"Shadows of Luclin",
	"Planes of Power"
];

?>

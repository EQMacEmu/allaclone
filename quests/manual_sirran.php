<?php
/**
 * Raw exception overlay for airplane/encounters/Sirran.lua.
 *
 * Generic extraction already covers Event.say / Event.trade per isle.
 * Death→spawn (dynamic GetX/Y/Z), timer depop, and combat shout are
 * recorded here as explicit notes — no Lua execution / state machine.
 */

/**
 * Apply Sirran manual notes onto a parse_lua_text result when $rel is Sirran.lua.
 * Returns true if applied.
 */
function apply_sirran_airplane_manual_overlay(&$parsed, $rel) {
	if (!preg_match('#(?:^|/)encounters/Sirran\.lua$#i', str_replace('\\', '/', $rel))) {
		return false;
	}
	if (!isset($parsed['npc_content']) || !is_array($parsed['npc_content'])) {
		$parsed['npc_content'] = array();
	}

	// Boss death_complete → Sirran isle NPC (unique_spawn at corpse; coords dynamic).
	$deathToSirran = array(
		71032 => 71058, // Thunder Spirit → isle 1
		71059 => 71023, // Protector of Sky → isle 2
		71021 => 71025, // Gorgalosk → isle 3
		71075 => 71026, // Keeper of Souls → isle 4
		71012 => 71029, // Spiroc Lord → isle 5
		71072 => 71036, // Bazzt Zzzt → isle 6
		71076 => 71078, // Sister of the Spire → isle 7
	);

	$script = str_replace('\\', '/', $rel);
	foreach ($deathToSirran as $bossId => $sirranId) {
		if (!isset($parsed['npc_content'][$bossId])) {
			$parsed['npc_content'][$bossId] = array('trades' => array(), 'say_branches' => array());
		}
		$parsed['npc_content'][$bossId]['say_branches'][] = array(
			'keyword' => 'death',
			'response_kind' => 'unique_spawn',
			'response_text' => "spawns NPC $sirranId",
			'line_no' => 0,
			'script_file' => $script,
		);

		if (!isset($parsed['npc_content'][$sirranId])) {
			$parsed['npc_content'][$sirranId] = array('trades' => array(), 'say_branches' => array());
		}
		$parsed['npc_content'][$sirranId]['say_branches'][] = array(
			'keyword' => 'spawn',
			'response_kind' => 'note',
			'response_text' => "Appears when NPC $bossId dies",
			'line_no' => 0,
			'script_file' => $script,
		);
		$parsed['npc_content'][$sirranId]['say_branches'][] = array(
			'keyword' => 'timer',
			'response_kind' => 'depop',
			'response_text' => 'despawns after 20 minutes',
			'line_no' => 0,
			'script_file' => $script,
		);
		$parsed['npc_content'][$sirranId]['say_branches'][] = array(
			'keyword' => 'combat',
			'response_kind' => 'say',
			'response_text' => "What?! Now you've done it! The bunnies are angry! ANGRY I TELL YOU!",
			'line_no' => 0,
			'script_file' => $script,
		);
	}

	// Keep registered id list aware of death bosses for binding diagnostics.
	$ids = array();
	foreach ($parsed['registered_npc_ids'] as $id) {
		$ids[(int)$id] = true;
	}
	foreach ($deathToSirran as $bossId => $sirranId) {
		$ids[(int)$bossId] = true;
		$ids[(int)$sirranId] = true;
	}
	$parsed['registered_npc_ids'] = array_keys($ids);
	$parsed['has_named_say_trade'] = true;
	$parsed['manual_overlay'] = 'sirran_airplane';
	return true;
}

<?php
/**
 * Raw exception overlay for airplane/encounters/Sirran.lua.
 *
 * Generic extraction covers Event.say / Event.trade per isle and
 * Event.death_complete → unique_spawn edges (quest_death_spawn).
 * Timer depop and combat shout are recorded here as explicit notes —
 * no Lua execution / state machine.
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

	// Sirran isle NPCs (death→spawn edges come from named death_complete callbacks).
	$isleIds = array(71058, 71023, 71025, 71026, 71029, 71036, 71078);
	$script = str_replace('\\', '/', $rel);
	foreach ($isleIds as $sirranId) {
		if (!isset($parsed['npc_content'][$sirranId])) {
			$parsed['npc_content'][$sirranId] = array('trades' => array(), 'say_branches' => array());
		}
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

	$parsed['has_named_say_trade'] = true;
	$parsed['manual_overlay'] = 'sirran_airplane';
	return true;
}

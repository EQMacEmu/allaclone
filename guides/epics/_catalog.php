<?php
/**
 * Epic 1.0 class catalog (classic 14 + Beastlord).
 * item_id / item_ids verified against local peq items table.
 */

function epic_catalog() {
	return array(
		'warrior' => array(
			'slug' => 'warrior',
			'class' => 'Warrior',
			'item_ids' => array(10909, 10910),
			'item_id' => 10910,
			'status' => 'ready',
			'blurb' => 'Dual aggro blades with solid classic stats; replaced as better raid weapons appear each expansion.',
		),
		'cleric' => array(
			'slug' => 'cleric',
			'class' => 'Cleric',
			'item_id' => 5532,
			'status' => 'ready',
			'blurb' => '96% complete resurrection clicky — remains useful until late-game resurrection AAs.',
		),
		'paladin' => array(
			'slug' => 'paladin',
			'class' => 'Paladin',
			'item_id' => 10099,
			'status' => 'ready',
			'blurb' => '2H that enables bash while equipped plus a stun DD; strong in Kunark, outclassed by later knight weapons.',
		),
		'ranger' => array(
			'slug' => 'ranger',
			'class' => 'Ranger',
			'item_ids' => array(20487, 20488),
			'item_id' => 20488,
			'status' => 'ready',
			'blurb' => 'Swiftwind and Earthcaller: 40% worn haste, attack, and a prized slow proc that stays relevant for a long time.',
		),
		'shadowknight' => array(
			'slug' => 'shadowknight',
			'class' => 'Shadow Knight',
			'item_id' => 14383,
			'status' => 'ready',
			'blurb' => '2H with bash while equipped plus DoT/HoT utility; solid in Kunark before knight weapons move on.',
		),
		'druid' => array(
			'slug' => 'druid',
			'class' => 'Druid',
			'item_id' => 20490,
			'status' => 'ready',
			'blurb' => 'DoT clicky with a long cast; damage is modest even in Kunark and falls off in group XP.',
		),
		'monk' => array(
			'slug' => 'monk',
			'class' => 'Monk',
			'item_id' => 10652,
			'status' => 'ready',
			'blurb' => '40% haste clicky and a top Kunark ratio fist weapon; the click remains useful well past Kunark.',
		),
		'bard' => array(
			'slug' => 'bard',
			'class' => 'Bard',
			'item_id' => 20542,
			'status' => 'ready',
			'blurb' => 'Counts as all instruments — excellent through PoP and still handy on alts afterward.',
		),
		'rogue' => array(
			'slug' => 'rogue',
			'class' => 'Rogue',
			'item_id' => 11057,
			'status' => 'ready',
			'blurb' => 'Strong Kunark melee blade with 40% haste; typically replaced during Velious.',
		),
		'shaman' => array(
			'slug' => 'shaman',
			'class' => 'Shaman',
			'item_id' => 10651,
			'status' => 'ready',
			'blurb' => 'Heavy DoT click with a long cast; useful early, then weaker as content ramps up.',
		),
		'necromancer' => array(
			'slug' => 'necromancer',
			'class' => 'Necromancer',
			'item_id' => 20544,
			'status' => 'ready',
			'blurb' => 'Snare/DoT click with a long cast — generally awkward for grouping.',
		),
		'wizard' => array(
			'slug' => 'wizard',
			'class' => 'Wizard',
			'item_id' => 14341,
			'status' => 'ready',
			'blurb' => 'Rune-style absorb click; long cast, and later shields replace it in PoP.',
		),
		'magician' => array(
			'slug' => 'magician',
			'class' => 'Magician',
			'item_id' => 28034,
			'status' => 'ready',
			'blurb' => 'Best mage pet through Luclin; PoP pets take over afterward.',
		),
		'enchanter' => array(
			'slug' => 'enchanter',
			'class' => 'Enchanter',
			'item_id' => 10650,
			'status' => 'ready',
			'blurb' => 'Haste click usable on low levels — always handy for buffing; high-end replacements appear in PoP.',
		),
		'beastlord' => array(
			'slug' => 'beastlord',
			'class' => 'Beastlord',
			'item_ids' => array(8495, 8496),
			'item_id' => 8495,
			'status' => 'ready',
			'blurb' => 'Luclin-era claws with slow proc and 40% worn haste; ratio is already soft by the time you finish them.',
		),
	);
}

function epic_catalog_entry($slug) {
	$all = epic_catalog();
	return isset($all[$slug]) ? $all[$slug] : null;
}

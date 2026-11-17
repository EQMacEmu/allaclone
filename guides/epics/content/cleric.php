<?php
/**
 * Cleric Epic 1.0 — Water Sprinkler of Nem Ankh
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   lakerathe/Shmendrik_Lavawalker.lua     28044 -> 28045; death -> 51145 spirit
 *   lakerathe/#Natasha_Whitewater.lua      28046 -> 28047
 *   timorous/Omat_Vastsea.lua              28047 -> 28051; 1299 -> 28049; 18170+28017 -> 28048; 28019 -> 28050
 *   soltemple/80002.lua                    28051 -> spawn 80042 plasmatic priest
 *   timorous/Natasha_Whitewater.lua        28018 -> 28056; 28052 -> 28054; 10m depop
 *   burningwood/Naxot_Deepwater.lua        28056 -> 28052; spawn Ixiblat 87014
 *   soldungb/Zordak_Ragefire.lua           28054 -> 28059 + 17175
 *   skyfire/Warder_Cecilia.lua             24996 -> 24997; spawn human Zordak 91005
 *   skyfire/Zordak_Ragefire.lua            death -> Zordakalicus 91006
 *   timorous/Jhassad_Oceanson.lua          28048+28049+28050 -> 28023; spawn Avatar
 *   timorous/Avatar_of_Water.lua           28023 -> 5532; 60m depop
 * Combines (tradeskill_recipe): 4x 24995 in 17175 -> 24996; 24997+28059 in 17122 -> 28019
 * Epic reward item id: 5532
 */

return array(
	'toc' => array(
		'Orb of Frozen Water',
		'Orb of Clear Water',
		'Orb of Vapor',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 28044,
			'note' => '{{npc:51143:lakerathe:Lord Bergurgle}} (underwater tower; rare PH)',
		),
		array(
			'item' => 28018,
			'note' => '{{npc:31126:soldunga:Lord Gimblox}} (goblin area; rare PH)',
		),
		array(
			'item' => 18170,
			'note' => '{{npc:103056:chardok:Overking Bathezid}} (always drops)',
		),
		array(
			'item' => 24995,
			'note' => 'Need 4 — Skyfire trash; combine in {{item:17175}}',
		),
		array(
			'html' => 'Optional early camps: Bergurgle crown, Gimblox ring, Bathezid scroll, and Skyfire fragments before Timorous hubs',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Orb of Frozen Water',
			'intro' => 'Collect {{item:28047}} via the Lake Rathe crown path, turn it in to {{npc:96033:timorous:Omat Vastsea}} for the coral statue, clear the Plasmatic Priest in Solusek Ro\'s temple for {{item:1299}}, and return the robe to Omat for {{item:28049}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Oil of Fennin Ro.',
					'text' => 'Shmendrik awards {{item:28045}} with the crown turn-in. On TAKP the Damaged Goblin Crown comes from killing {{npc:51145:lakerathe:a spirit of flame}} after Shmendrik dies — the oil is not required for the epic.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Protect Natasha.',
					'text' => 'After the crown turn-in, {{npc:51138:lakerathe:#Natasha Whitewater}} spawns and attacks Shmendrik. Do not let her die before you hand her {{item:28046}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Lord Bergurgle\'s Crown',
					'text' => 'Camp {{npc:51143:lakerathe:Lord Bergurgle}} in the underwater goblin tower (middle PH of the three goblins). Loot {{item:28044}} (always on him when he is up).',
				),
				array(
					'title' => 'Shmendrik and Natasha',
					'text' => 'Give {{item:28044}} to {{npc:51012:lakerathe:Shmendrik Lavawalker}} (north-central lake) for {{item:28045}}. This spawns {{npc:51138:lakerathe:#Natasha Whitewater}}, who paths to and attacks him.',
					'sub' => array(
						'Kill Shmendrik once Natasha engages. His death spawns {{npc:51145:lakerathe:a spirit of flame}}.',
						'Kill the spirit and loot {{item:28046}} (always drops).',
						'Give {{item:28046}} to Natasha for {{item:28047}}.',
					),
				),
				array(
					'title' => 'Coral Statue of Tarew',
					'text' => 'Find {{npc:96033:timorous:Omat Vastsea}} on Oasis Island in {{zone:timorous}}. Give {{item:28047}} for {{item:28051}}.',
				),
				array(
					'title' => 'Plasmatic Priest',
					'text' => 'In {{zone:soltemple}}, give {{item:28051}} to the male daytime {{npc:80002:soltemple:a seeker}} (small upstairs room; day window roughly 7am–9pm game time — do not hand the night/female seeker). The turn-in spawns hostile {{npc:80042:soltemple:a plasmatic priest}} and depops the seeker.',
					'sub' => array(
						'Kill the priest and loot {{item:1299}} (always drops). He Complete Heals; burn him in the room or adjacent hall.',
					),
				),
				array(
					'title' => 'Lord Gimblox\'s ring',
					'text' => 'In {{zone:soldunga}}, camp {{npc:31126:soldunga:Lord Gimblox}} in the goblin wing and loot {{item:28018}} (always drops when he is up). Prefarm anytime before the Timorous Natasha hand-in.',
				),
				array(
					'title' => 'Orb of Frozen Water',
					'text' => 'Return to {{npc:96033:timorous:Omat Vastsea}} and give {{item:1299}} for {{item:28049}}. This also spawns {{npc:96080:timorous:Natasha Whitewater}} in the nearby hut (~10 minute depop timer).',
					'sub' => array(
						'Immediately give Natasha {{item:28018}} for {{item:28056}} (second ornate shell) before she despawns.',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Orb of Clear Water',
			'steps' => array(
				array(
					'title' => 'Naxot and Ixiblat',
					'text' => 'Give {{item:28056}} to {{npc:87002:burningwood:Naxot Deepwater}} for {{item:28052}}. The turn-in also spawns {{npc:87014:burningwood:Ixiblat Fer}}.',
					'sub' => array(
						'Kill Ixiblat (large fire elemental; raid/mini-raid depending on era) and loot {{item:28017}}.',
					),
				),
				array(
					'title' => 'Singed Scroll',
					'text' => 'Kill {{npc:103056:chardok:Overking Bathezid}} and loot {{item:18170}} (always drops). Pick lock is needed for the royal wing.',
				),
				array(
					'title' => 'Orb of Clear Water',
					'text' => 'Give {{npc:96033:timorous:Omat Vastsea}} {{item:18170}} and {{item:28017}} together for {{item:28048}}. Natasha respawns in the hut.',
					'sub' => array(
						'Give {{npc:96080:timorous:Natasha Whitewater}} {{item:28052}} for {{item:28054}} before her 10-minute depop.',
					),
				),
			),
		),
		array(
			'title' => 'Part 3 — Orb of Vapor',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Two Zordaks.',
					'text' => 'Human {{npc:32038:soldungb:Zordak Ragefire}} in Nagafen\'s Lair takes the pearl. Skyfire later releases another human Zordak whose death spawns the dragon {{npc:91006:skyfire:Zordakalicus Ragefire}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Zordak in Sol B',
					'text' => 'Find {{npc:32038:soldungb:Zordak Ragefire}} on the fire-giant ledge. Give {{item:28054}} for {{item:28059}} and {{item:17175}}.',
					'sub' => array(
						'Long respawn (up to ~24 hours on TAKP spawn logic). If Nagafen is up, alternate # / ## Zordak scripts may apply — same pearl turn-in when the human is available.',
					),
				),
				array(
					'title' => 'Pearlescent fragments',
					'text' => 'Farm four {{item:24995}} from Skyfire trash. Combine all four inside {{item:17175}} (no-fail) to create {{item:24996}}.',
				),
				array(
					'title' => 'Warder Cecilia',
					'text' => 'Give {{item:24996}} to {{npc:91037:skyfire:Warder Cecilia}} (northeast Skyfire) for {{item:24997}}. This spawns hostile human {{npc:91005:skyfire:Zordak Ragefire}}.',
					'sub' => array(
						'Kill the human; his death spawns {{npc:91006:skyfire:Zordakalicus Ragefire}}.',
						'Kill the dragon (fears) and loot {{item:17122}} (2-slot container; always drops).',
					),
				),
				array(
					'title' => 'Zordak Ragefire\'s Heart',
					'text' => 'Combine {{item:24997}} and {{item:28059}} inside {{item:17122}} to create {{item:28019}}.',
				),
				array(
					'title' => 'Orb of Vapor',
					'text' => 'Give {{item:28019}} to {{npc:96033:timorous:Omat Vastsea}} for {{item:28050}}. This spawns {{npc:96074:timorous:Jhassad Oceanson}} on the beach.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Avatar timer.',
					'text' => '{{npc:96086:timorous:Avatar of Water}} despawns after about 60 minutes. Have all three orbs ready before Jhassad.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Orb of the Triumvirate',
					'text' => 'Give {{npc:96074:timorous:Jhassad Oceanson}} {{item:28048}}, {{item:28049}}, and {{item:28050}} together for {{item:28023}}. The Avatar spawns in the water.',
				),
				array(
					'title' => 'Water Sprinkler of Nem Ankh',
					'text' => 'Give {{npc:96086:timorous:Avatar of Water}} {{item:28023}} to receive {{item:5532}}.',
				),
			),
		),
	),
	'verified' => array(
		'lakerathe/Shmendrik_Lavawalker.lua',
		'lakerathe/#Natasha_Whitewater.lua',
		'timorous/Omat_Vastsea.lua',
		'timorous/Natasha_Whitewater.lua',
		'soltemple/80002.lua',
		'burningwood/Naxot_Deepwater.lua',
		'soldungb/Zordak_Ragefire.lua',
		'skyfire/Warder_Cecilia.lua',
		'skyfire/Zordak_Ragefire.lua',
		'timorous/Jhassad_Oceanson.lua',
		'timorous/Avatar_of_Water.lua',
	),
);

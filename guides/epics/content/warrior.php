<?php
/**
 * Warrior Epic 1.0 — Blade of Tactics / Blade of Strategy
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   freporte/Kargek_Redblade.lua       "favor" -> SummonCursorItem 20673 (note);
 *                                      20674 -> 18083; 20684+20679 -> Red Scabbard 17859
 *   feerrott/Oknoggin_Stonesmacker.lua 20673 -> 20674
 *   eastkarana/Tenal_Redblade.lua      18083 -> 20680; 20680+10549 -> 20681;
 *                                      20681+11622+11582 -> 20683; 20683+20676 -> 20684
 *   oot/Denken_Strongpick.lua          16889+16889+20665+20664 -> 20667 Ancient Sword Blade
 *   freporte/Wenden_Blackhammer.lua    20668+10037+10036+10053 -> 20671 (jeweled hilt);
 *                                      20669+20666+20677 -> 20672 (finely crafted hilt);
 *                                      20669 alone is returned
 *   frontiermtns/Mentrax_Mountainbone.lua  say "wenden sent me"; 20678 -> 20666 Rejesiam Ore
 *   hateplane/Maestro_of_Rancor.lua    death_complete spawns #Hand 76382 (Kunark expansion only)
 *   hateplane/#Hand_of_the_Maestro.lua death_complete spawns A Very Unpleasant Hand 76388 (Velious+)
 *   airplane/The_Spiroc_Guardian.lua   if Spiroc Lord not up, pushes his spawn timer to 1s
 * Combines (tradeskill_recipe): the blades are CONTAINER COMBINES in the Red Scabbard (no Lua reward):
 *   recipe 9834 in 17859 Red Scabbard: 20667 + 20670 + 20671 + 20672 -> 10909 + 10910
 *   (+ replaces the container with 17869 Red Scabbard; no-fail). 17869-only recipes can merge
 *   10909+10910 -> 10908 (13380) and split 10908 -> 10909+10910 (13379).
 * Epic reward item ids: 10909, 10910 (also 10908 Jagged Blade of War from scabbard recombine)
 *
 * Faction note (TAKP DB): Kargek, Wenden, Oknoggin, Denken, and Mentrax have no kill hits in
 * npc_faction. Queen Velazul Di`zok hits Brood of Di`Zok / Sarnak Collective downward.
 */

return array(
	'toc' => array(
		'Red Scabbard (Redblade chain)',
		'Ancient Sword Blade',
		'Ancient Blade',
		'Dragon head hilts',
		'Scabbard combine',
	),
	'prefarm' => array(
		array(
			'item' => 20679,
			'note' => '{{npc:71012:airplane:The Spiroc Lord}} (Plane of Sky; need exactly one Wingblade)',
		),
		array(
			'item' => 20676,
			'note' => '{{npc:76382:hateplane:#Hand of the Maestro}} / {{npc:76388:hateplane:A Very Unpleasant Hand}} (Plane of Hate; see Part 1)',
		),
		array(
			'item' => 10549,
			'note' => 'Permafrost goblins — {{npc:73044:permafrost:a goblin wizard}} / {{npc:73221:permafrost:a goblin warlock}} (single heart)',
		),
		array(
			'item' => 11622,
			'note' => 'Red Dragon Scales — best bet is {{npc:39148:hole:Nortlav the Scalekeeper}} (always); also {{npc:32040:soldungb:Lord Nagafen}}, {{npc:91093:skyfire:Talendor}}',
		),
		array(
			'item' => 11582,
			'note' => 'Green Dragon Scales — {{npc:94002:emeraldjungle:Severilous}} or {{npc:108517:veeshan:Hoshkar}}',
		),
		array(
			'item' => 20665,
			'note' => 'Block of Permafrost — {{npc:73069:permafrost:an ice giant}}',
		),
		array(
			'item' => 20664,
			'note' => 'Keg of Vox Tail Ale — vendors such as {{npc:10066:freporte:Iseba Gargurd}} / {{npc:10065:freporte:Tanlok Harson}} in {{zone:freporte}}',
		),
		array(
			'item' => 16889,
			'note' => 'Need 2 Rebreathers — player Tinkering (skill 175 trivial); not found on vendors or loot tables locally',
		),
		array(
			'item' => 20670,
			'note' => '{{npc:103055:chardok:Queen Velazul Di`zok}} (always drops)',
		),
		array(
			'item' => 20678,
			'note' => '{{npc:86144:dreadlands:a mountain giant patriarch}} (Dreadlands; wanders many camps)',
		),
		array(
			'item' => 20677,
			'note' => 'Fear Plane named — {{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, or {{npc:72004:fearplane:Fright}}',
		),
		array(
			'html' => 'Gems for Wenden: {{item:10037}}, {{item:10036}}, {{item:10053}} (one of each; common raid/dungeon drops such as {{npc:69018:oot:Allizewsaur}})',
		),
		array(
			'html' => 'Ground hilts: {{item:20669}} (Timorous Deep) and {{item:20668}} (Lake Rathetear); respawn about 5 minutes',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Red Scabbard (Redblade chain)',
			'intro' => 'Kargek hands you the scabbard only after you walk a long Redblade relay: carry a note, return a lute, then run Tenal\'s four tests. The scabbard is the container for every later combine, so do not discard or sell it.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Hand-in order matters.',
					'text' => 'Tenal\'s Lua checks exact item sets. Give {{item:20680}} + {{item:10549}} together; then {{item:20681}} + {{item:11622}} + {{item:11582}}; then {{item:20683}} + {{item:20676}}. Extra items are returned and nothing advances.',
				),
				array(
					'kind' => 'note',
					'title' => 'Hand of the Maestro on TAKP.',
					'text' => '{{npc:76006:hateplane:Maestro of Rancor}} spawns {{npc:76382:hateplane:#Hand of the Maestro}} on death only while the current expansion is Kunark; the Hand also has its own long respawn at the same spot. When Velious is enabled, killing the Hand spawns {{npc:76388:hateplane:A Very Unpleasant Hand}}, which also drops {{item:20676}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Kargek\'s favor',
					'text' => 'Talk to {{npc:10117:freporte:Kargek Redblade}} in the {{zone:freporte}} forge (Wenden Blackhammer works next to him). Say hail, then scabbard, then favor. He gives you {{item:20673}} to deliver.',
				),
				array(
					'title' => 'Oknoggin Stonesmacker',
					'text' => 'Find {{npc:47126:feerrott:Oknoggin Stonesmacker}} wandering the Feerrott. Give him {{item:20673}} for {{item:20674}}.',
				),
				array(
					'title' => 'Redblade\'s Legacy',
					'text' => 'Return {{item:20674}} to Kargek for {{item:18083}}, the book that points you at his brother.',
				),
				array(
					'title' => 'Tenal — first test',
					'text' => 'In {{zone:eastkarana}}, give {{item:18083}} to {{npc:15077:eastkarana:Tenal Redblade}} for {{item:20680}}.',
				),
				array(
					'title' => 'Heart of Frost',
					'text' => 'Kill a goblin spellcaster in {{zone:permafrost}} (wizards and warlocks carry {{item:10549}} often). Give Tenal {{item:20680}} and {{item:10549}} for {{item:20681}}. Tenal briefly speaks with a god\'s voice during the hand-in — that is normal.',
				),
				array(
					'title' => 'Red and green dragon scales',
					'text' => 'Collect {{item:11622}} and {{item:11582}}, then give Tenal {{item:20681}}, {{item:11622}}, and {{item:11582}} for {{item:20683}}.',
				),
				array(
					'title' => 'Hand of the Maestro',
					'text' => 'Enter {{zone:hateplane}} and kill {{npc:76006:hateplane:Maestro of Rancor}} (or the Hand variants noted above). Loot {{item:20676}}.',
					'sub' => array(
						'Give Tenal {{item:20683}} and {{item:20676}} for {{item:20684}}.',
					),
				),
				array(
					'title' => 'Spiroc Wingblade',
					'text' => 'In {{zone:airplane}}, kill {{npc:71012:airplane:The Spiroc Lord}} and loot {{item:20679}}. The Spiroc Guardian\'s script can shorten the Lord\'s respawn when the Lord is not up, so clear the Guardian\'s group first.',
				),
				array(
					'title' => 'Red Scabbard',
					'text' => 'Give {{npc:10117:freporte:Kargek Redblade}} {{item:20684}} and {{item:20679}} together. He rewards {{item:17859}} and a long speech about stones for later tradeskill upgrades — ignore it for the epic.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Ancient Sword Blade',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'No coin involved.',
					'text' => 'Denken\'s dialogue (hail, goblin relics, one thing, buy, use) only explains the price: two Rebreathers, a permafrost block, and a keg. {{npc:69062:oot:Fendlemend Figlefop}} can point you to Denken on the hill near the aviaks in {{zone:oot}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Collect the trade goods',
					'text' => 'You need two {{item:16889}}, one {{item:20665}}, and one {{item:20664}}. Rebreathers are Tinkering products; ice giants in {{zone:permafrost}} carry the permafrost block; ale is on several city vendors.',
				),
				array(
					'title' => 'Denken Strongpick',
					'text' => 'In {{zone:oot}}, give {{npc:69061:oot:Denken Strongpick}} both Rebreathers plus {{item:20665}} and {{item:20664}} in one trade for {{item:20667}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Ancient Blade',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Faction.',
					'text' => 'Killing {{npc:103055:chardok:Queen Velazul Di`zok}} lowers {{faction:451}} (and Sarnak Collective) — she is a mid-late Kunark dungeon fight, not a faction chain.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Queen Velazul Di`zok',
					'text' => 'In {{zone:chardok}} kill {{npc:103055:chardok:Queen Velazul Di`zok}} (royal wing, same neighborhood as {{npc:103056:chardok:Overking Bathezid}}). Loot {{item:20670}} — it drops every time she is killed.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Dragon head hilts',
			'intro' => 'Wenden repairs two different junk hilts you pick off the ground. Each repair needs its own materials; do not mix the sets.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Wenden is picky.',
					'text' => 'Handing {{item:20669}} alone returns it with a hint. The repair needs {{item:20666}} and {{item:20677}} in the same trade. The jeweled hilt needs exactly {{item:10037}}, {{item:10036}}, and {{item:10053}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Unjeweled Dragon Head Hilt',
					'text' => 'Ground item {{item:20668}} on the bottom of {{zone:lakerathe}} near /loc 2512, 924, -195 (deep water). Give {{npc:10116:freporte:Wenden Blackhammer}} the hilt plus {{item:10037}}, {{item:10036}}, and {{item:10053}} for {{item:20671}}.',
				),
				array(
					'title' => 'Severely Damaged Dragon Head Hilt',
					'text' => 'Ground item {{item:20669}} in {{zone:timorous}} near /loc -6449, 2836. Keep it for the next steps.',
				),
				array(
					'title' => 'Rejesiam ore',
					'text' => 'Wenden\'s keywords (severely damaged dragon head, golem, rejesiam) explain the ore and the golem metal. Say "wenden sent me" to {{npc:92148:frontiermtns:Mentrax Mountainbone}} in {{zone:frontiermtns}}, then give him {{item:20678}} (dropped by {{npc:86144:dreadlands:a mountain giant patriarch}}) for {{item:20666}}.',
				),
				array(
					'title' => 'Ball of Everliving Golem',
					'text' => 'Kill a Fear Plane named for {{item:20677}} (about a 20% chance on {{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, and {{npc:72004:fearplane:Fright}} each).',
				),
				array(
					'title' => 'Finely Crafted Dragon Head Hilt',
					'text' => 'Give Wenden {{item:20669}}, {{item:20666}}, and {{item:20677}} together for {{item:20672}}.',
				),
			),
		),
		array(
			'title' => 'Part 5 — Scabbard combine',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'No NPC gives the blades.',
					'text' => 'Kargek only hands out {{item:17859}}. {{item:10909}} and {{item:10910}} are produced by a container combine inside that bag.',
				),
				array(
					'kind' => 'note',
					'title' => 'Scabbard replaced after the combine.',
					'text' => 'The combine uses {{item:17859}} as the container and returns a second item also named Red Scabbard ({{item:17869}}) alongside the blades. Further recombines (merge to {{item:10908}} or split back) use the new scabbard.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Fill the Red Scabbard',
					'text' => 'Open {{item:17859}} (four slots) and place {{item:20667}}, {{item:20670}}, {{item:20671}}, and {{item:20672}} inside, then press Combine. The recipe has no tradeskill skill check and cannot fail.',
					'sub' => array(
						'Rewards: {{item:10909}} and {{item:10910}} (Warrior only; primary or secondary).',
						'Optional: place both blades in the new scabbard to merge them into {{item:10908}} (two-hand), or place the Jagged Blade alone to split back.',
					),
				),
			),
		),
	),
	'verified' => array(
		'freporte/Kargek_Redblade.lua',
		'feerrott/Oknoggin_Stonesmacker.lua',
		'eastkarana/Tenal_Redblade.lua',
		'oot/Denken_Strongpick.lua',
		'oot/Fendlemend_Figlefop.lua',
		'freporte/Wenden_Blackhammer.lua',
		'frontiermtns/Mentrax_Mountainbone.lua',
		'hateplane/Maestro_of_Rancor.lua',
		'hateplane/#Hand_of_the_Maestro.lua',
		'airplane/The_Spiroc_Guardian.lua',
	),
);

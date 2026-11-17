<?php
/**
 * Wizard Epic 1.0 — Staff of the Four
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   soltemple/Solomen.lua              say wizard/agree -> 18088; 14340 -> 14341 (Truespirit -50)
 *   erudnext/Camin.lua                 18088 -> wizepic=1; 1000pp (wizepic 1) -> wizepic=2; 14330 (wizepic 2) -> 14331
 *   halas/Dargon.lua                   14331 / 14335 / 18168 -> spawns Arantir 29089; sells 14330 (merchantlist)
 *   halas/Arantir_Karondor.lua         hail -> 14334; 14335 -> wizepicA; say gnome -> 18169; 18168 -> wizepicB;
 *                                      14337+14338+14339 -> 14340; 640s depop timer
 *   felwithea/Challice.lua             14334 -> 14335
 *   butcher/Kandin_Firepot.lua         18169 faction hand-in; say what oil -> 14319; 14349 -> 14339 + 18168;
 *                                      14332 -> 14336; 14333 -> 14339; 14336 -> 18168
 *   fearplane/a_broken_golem.lua       14319 -> spawns an_enraged_golem 72106 (drops 14349)
 * DB (peq): 14337 Phinigel Autropos (kedge, 25%); 14338 Venril Sathir (karnor, 50%); 14349 enraged golem (100%).
 * No local loot, vendor, or ground-spawn source was found for 14332 (Cazic's Skin) or 14333 (Mistletoe Powder).
 * Epic reward item id: 14341
 */

return array(
	'toc' => array(
		'Letter to Camin',
		'Finding Arantir',
		'Ring and clues',
		'Three staves',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 14337,
			'note' => '{{npc:64001:kedge:Phinigel Autropos}} (Kedge Keep named; rare, long respawn)',
		),
		array(
			'item' => 14338,
			'note' => '{{npc:102010:karnor:Venril Sathir}} (Karnor\'s Castle named; very long respawn)',
		),
		array(
			'item' => 14349,
			'note' => 'Optional early: Kandin\'s sprocket-and-golem side quest, finished in Plane of Fear',
		),
		array(
			'html' => 'Platinum: 1000 pp for {{npc:24004:erudnext:Camin}}, plus the price of {{item:14330:Ro\'s Breath}} (about 850 pp base, before charisma adjustments)',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Letter to Camin',
			'intro' => 'Pick up a courier errand from {{npc:80023:soltemple:Solomen}}, deliver it, and start the global-flag clock with {{npc:24004:erudnext:Camin}}.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Thirty-day flags.',
					'text' => 'Camin tracks you with a temporary flag that lasts about thirty days (on TAKP\'s quest script), and a second flag with the same limit after you pay him. If you stall for weeks, you may have to start his portion over.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Take the envelope',
					'text' => 'Speak with {{npc:80023:soltemple:Solomen}} in his study. Ask about the "wizard" topic, then answer that you agree to deliver the package. He summons {{item:18088}} onto your cursor.',
				),
				array(
					'title' => 'Deliver to Camin',
					'text' => 'Hand {{item:18088}} to {{npc:24004:erudnext:Camin}}, who lingers near the wizard tower. You receive a little experience and your first flag is set.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Finding Arantir',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Order matters.',
					'text' => 'Camin only accepts the 1000 platinum for story progress while your first flag is active. Pay at the wrong time and he simply hands the coin back. Likewise, the Ro\'s Breath hand-in only counts after payment; otherwise he returns the vial.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Pay for his research',
					'text' => 'Ask {{npc:24004:erudnext:Camin}} what he is searching for, then give him 1000 platinum. He names Arantir Karondor as the surviving follower of Solusek Ro and sets your second flag.',
				),
				array(
					'title' => 'Buy Ro\'s Breath',
					'text' => 'Purchase {{item:14330:Ro\'s Breath}} from {{npc:29000:halas:Dargon}}. Other vendors in the item database also stock it if Dargon is inconvenient.',
				),
				array(
					'title' => 'Identify the maker',
					'text' => 'Give {{item:14330:Ro\'s Breath}} to {{npc:24004:erudnext:Camin}}. He recognizes the craftsmanship, rewards experience, and gives you {{item:14331:Ro\'s Breath (marked)}} to take back to the person who sold it.',
				),
				array(
					'title' => 'Unmask Dargon',
					'text' => 'Hand {{item:14331:Ro\'s Breath (marked)}} to {{npc:29000:halas:Dargon}}. He admits his past, vanishes for a respawn cycle, and {{npc:29089:halas:Arantir Karondor}} appears at the same spot.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Ring and clues',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Arantir does not stay.',
					'text' => '{{npc:29089:halas:Arantir Karondor}} despawns on a timer of roughly ten and a half minutes (restarted by some turn-ins). Do not burn time on a long detour with him up. If he vanishes, {{npc:29000:halas:Dargon}} accepts the second ring (and the later note) to spawn him again.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Get the first ring',
					'text' => 'Hail {{npc:29089:halas:Arantir Karondor}}. If you have no Arantir flag yet, he summons {{item:14334:Arantir\'s Ring (first)}} to your cursor.',
				),
				array(
					'title' => 'Challice',
					'text' => 'Give {{item:14334:Arantir\'s Ring (first)}} to {{npc:61012:felwithea:Challice}}. She mocks the giver and returns {{item:14335:Arantir\'s Ring (second)}} with a hefty experience award.',
				),
				array(
					'title' => 'Return the ring',
					'text' => 'Give {{item:14335:Arantir\'s Ring (second)}} to Arantir (or to Dargon if Arantir is gone). This sets your permanent first-stage Arantir flag.',
				),
				array(
					'title' => 'Ask for the story',
					'text' => 'Say "story" to Arantir for the lore of the four followers. Follow up with "Sylen Tyrn" and "Demunir Scry" for his clues about the other followers.',
					'sub' => array(
						'Say "gnome" to receive {{item:18169}} and have Arantir leave — only do this when you are ready to move on.',
					),
				),
			),
		),
		array(
			'title' => 'Part 4 — Three staves',
			'intro' => 'Gather {{item:14339}}, {{item:14337}}, and {{item:14338}}. Kandin Firepot supplies the first; the other two are named drops in Kedge Keep and Karnor\'s Castle.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Which route?',
					'text' => 'The Green Oil route below is the one with documented local sources. The alternate skin/powder route is scripted, but this guide\'s database check found no drop, vendor, or ground spawn for {{item:14332}} or {{item:14333}} — treat it as theory unless you can prove otherwise in game. The bag reward in that chain (item 14336) is also absent from the item database.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Gears are expansion-gated.',
					'text' => 'Kandin\'s sprocket line is scripted behind the Scars of Velious flag. If the server runs without it, he may stay silent when you ask about the oil.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Greet Kandin',
					'text' => 'Give {{item:18169}} to {{npc:68109:butcher:Kandin Firepot}}. It earns a small amount of {{faction:404:Truespirit}} faction, and his reply sets up the skin-or-oil errand.',
				),
				array(
					'title' => 'Get the sprocket',
					'text' => 'Ask Kandin "what oil" to receive {{item:14319}}. Faction toward him must be at least about −60 on the scripted scale.',
				),
				array(
					'title' => 'Broken golem',
					'text' => 'Find {{npc:72074:fearplane:a broken golem}}. Give it {{item:14319}} — the script spawns {{npc:72106:fearplane:an enraged golem}} (level 65) in its place and depops the broken one.',
					'sub' => array(
						'The broken golem has a long respawn (about three days in the database).',
						'Kill the enraged golem and loot {{item:14349}} (always drops).',
					),
				),
				array(
					'title' => 'Staff of Gabstik',
					'text' => 'Return {{item:14349}} to {{npc:68109:butcher:Kandin Firepot}} for {{item:14339}} and {{item:18168}}. If your faction is too low, he refuses and returns the oil.',
				),
				array(
					'title' => 'Alternate: skin and powder',
					'text' => 'Scripted fallback: {{item:14332}} to Kandin gives Kandin\'s Bag (item 14336, which is missing from the item database); {{item:14333}} then gives {{item:14339}}; finally the bag gives {{item:18168}}. Faction thresholds are slightly tighter at each step (about −60, −55, −50).',
				),
				array(
					'title' => 'Blue Crystal Staff',
					'text' => 'Kill {{npc:64001:kedge:Phinigel Autropos}} until {{item:14337}} drops (about one in four).',
				),
				array(
					'title' => 'Gnarled Staff',
					'text' => 'Kill {{npc:102010:karnor:Venril Sathir}} until {{item:14338}} drops (about one in two). Respawn is long (about three days in the database).',
				),
			),
		),
		array(
			'title' => 'Part 5 — Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Do not open the bag.',
					'text' => 'Arantir warns that {{item:14340}} cannot be reopened after he seals it. Hand it to Solomen unopened.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Note to Arantir',
					'text' => 'Give {{item:18168}} to {{npc:29089:halas:Arantir Karondor}} (or to {{npc:29000:halas:Dargon}} if Arantir is not up). This swaps your flag to the final stage.',
				),
				array(
					'title' => 'Seal the bag',
					'text' => 'Hail Arantir. Give him {{item:14339}}, {{item:14337}}, and {{item:14338}} together for {{item:14340}}.',
				),
				array(
					'title' => 'Staff of the Four',
					'text' => 'Return to {{npc:80023:soltemple:Solomen}} and give him {{item:14340}} to receive {{item:14341}}. The turn-in also reduces your {{faction:404:Truespirit}} standing by 50.',
				),
			),
		),
	),
	'verified' => array(
		'soltemple/Solomen.lua',
		'erudnext/Camin.lua',
		'halas/Arantir_Karondor.lua',
		'felwithea/Challice.lua',
		'halas/Dargon.lua',
		'butcher/Kandin_Firepot.lua',
		'fearplane/a_broken_golem.lua',
	),
);

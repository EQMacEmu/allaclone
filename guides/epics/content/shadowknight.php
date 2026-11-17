<?php
/**
 * Shadow Knight Epic 1.0 — Innoruuk's Curse
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   overthere/Kurron_Ni.lua          3141+3145+3140 + 900pp -> spawn #Kurron_Ni 93006; depop_with_timer
 *   overthere/#Kurron_Ni.lua         spawn timer 60m; drops 18099 (always)
 *   soltemple/an_undead_knight_.lua  12283+12284+13921x2 -> 3140; 12285+12286+16507x2 -> 3141
 *   soltemple/an_undead_knight.lua   12293+12294+12297x2 -> 3145
 *   neriaka/Smaka.lua                1000pp -> 14365
 *   paineel/Duriek_Bloodpool.lua     18099 (xp); 14365 (text); 14382 (text); 5403+11609+5430+14366 -> 14367
 *   hole/Caradon.lua                 14373 -> spawns Kyrenna 39155, Caradon aggro
 *   tox/Knarthenne_Skurl.lua         say "heart of an innocent" -> summons 17051
 *   qcat/Teydar.lua                  14372+14371+16507 -> 14366
 *   kerraridge/Marl_Kastane.lua      say "prophecy" -> 14375; 14376 -> global Marl; 14381 -> 14377; 14379 -> 14370
 *   paineel/Gerot_Kastane.lua        14375 -> 14376; 14378 -> 14369
 *   hole/Ghost_of_Glohnor.lua        14377 -> spawn Mummy of Glohnor 39165
 *   citymist/Lhranc.lua              14367+14368+14369+14370 -> 14384; spawn #Lhranc 90015
 *   citymist/#Lhranc.lua             2h depop; death -> spawn #Marl_Kastane 90016
 *   citymist/#Marl_Kastane.lua       14384 -> 14383; 80m depop
 * Combines (tradeskill_recipe): 14380 in 17051 -> 14368 (recipe 9825); 2410+2411+2412+2413 in 17878 -> 5403 (recipe 9809)
 * Epic reward item id: 14383
 *
 * Faction note: quest NPCs in this chain use True Spirit (404); Smaka instead moves Dark Bargainers
 * and Dreadguard. Final turn-in applies -53 to True Spirit (#Marl script); Lhranc gives +10.
 */

return array(
	'toc' => array(
		'Darkforge armor and Duriek',
		'Corrupted Ghoulbane',
		'Heart of the Innocent',
		'Head of the Valiant and Will of Innoruuk',
		'Final turn-ins',
	),
	'prefarm' => array(
		array(
			'item' => 3140,
			'note' => 'Quested from an undead knight in {{zone:soltemple}}; see Part 1',
		),
		array(
			'item' => 3141,
			'note' => 'Quested from the same knight; needs two {{item:16507}}',
		),
		array(
			'item' => 3145,
			'note' => 'Quested from the second undead knight in the same temple; needs two {{item:12297}}',
		),
		array(
			'item' => 5403,
			'note' => '{{npc:62000:felwitheb:Joren Nobleheart}}, {{npc:65128:guktop:a froglok shin lord}}, or the {{item:17878}} combine',
		),
		array(
			'item' => 11609,
			'note' => 'Plane of Fear — {{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, {{npc:72004:fearplane:Fright}}',
		),
		array(
			'item' => 5430,
			'note' => 'Plane of Sky drake, harpy, spiroc, and spirit mobs (low rate per kill)',
		),
		array(
			'item' => 14371,
			'note' => '{{npc:76002:hateplane:an ashenbone drake}} (about 3%)',
		),
		array(
			'item' => 14372,
			'note' => '{{npc:50019:rathemtn:Rharzar}} (always drops)',
		),
		array(
			'item' => 16507,
			'note' => 'Enchanter casts Enchant Platinum on a {{item:16503}}; you need one for Teydar and two more for the breastplate',
		),
		array(
			'item' => 14373,
			'note' => '{{npc:39022:hole:a mimic}} (about 25%)',
		),
		array(
			'item' => 14382,
			'note' => '{{npc:39011:hole:a ratman guard}} (always drops when the guard spawns)',
		),
		array(
			'html' => 'Platinum for the chain: 900 for {{npc:93083:overthere:Kurron Ni}} and 1000 for {{npc:40034:neriaka:Smaka}}',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Darkforge armor and Duriek',
			'intro' => 'Kurron Ni wants three Darkforge pieces and 900 platinum before his stand-in will spawn. That stand-in carries the letter that starts {{npc:75003:paineel:Duriek Bloodpool}}\'s requests. Duriek then needs a cough elixir, a dusty tome, and four items fused into the Corrupted Ghoulbane.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Faction.',
					'text' => 'Almost every NPC in this epic keys off {{faction:404:True Spirit}}. Duriek\'s four-item hand-in refuses anyone at -84 or worse, so check standing before you collect the blades.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Do not say "mission" to Kurron Ni.',
					'text' => 'That keyword makes him attack you. Use "seek", "I am worthy", then "agreed", and only hand over the armor and platinum when you are ready.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Darkforge helm',
					'text' => 'In {{zone:soltemple}}, two undead knights stand near each other. The one who offers helms, breastplates, vambraces, and bracers is {{npc:80001:soltemple:an undead knight}}. Give him {{item:12283}}, {{item:12284}}, and two {{item:13921}} for {{item:3140}}.',
					'sub' => array(
						'{{item:12283}} and {{item:12284}} drop from {{npc:47182:feerrott:a Tae Ew spiritualist}}, {{npc:47202:feerrott:a Tae Ew crusader}}, {{npc:48004:cazicthule:a lizard crusader}}, and {{npc:48028:cazicthule:a lizard ritualist}}.',
						'{{item:13921}} drops from West Freeport militia such as {{npc:9014:freportw:Guard Kroon}} and {{npc:9007:freportw:Armorer Dellin}}. Expect a Freeport faction hit.',
					),
				),
				array(
					'title' => 'Darkforge breastplate',
					'text' => 'Give the same knight {{item:12285}}, {{item:12286}}, and two {{item:16507}} for {{item:3141}}.',
					'sub' => array(
						'The decayed breastplate and chainmail share the same four drop sources as the helm pieces.',
						'An Enchanter casts Enchant Platinum on {{item:16503}} to make each bar; merchants sell the raw bars.',
					),
				),
				array(
					'title' => 'Darkforge greaves',
					'text' => 'The other knight, {{npc:80000:soltemple:an undead knight}}, is the one who offers boots, greaves, and gauntlets. He takes {{item:12293}}, {{item:12294}}, and two {{item:12297}} for {{item:3145}}.',
					'sub' => array(
						'The legplates come from the same Feerrott and Cazic-Thule lizard mobs as the other decayed pieces.',
						'{{item:12297}} drops from the clockwork "CWG Model" mobs in {{zone:soldunga}}, for example {{npc:31048:soldunga:CWG Model CX}}.',
					),
				),
				array(
					'title' => 'Kurron Ni',
					'text' => 'Find {{npc:93083:overthere:Kurron Ni}} and give him {{item:3141}}, {{item:3145}}, {{item:3140}}, and 900 platinum together. He vanishes and an angry {{npc:93006:overthere:Kurron Ni}} appears in his place.',
					'sub' => array(
						'Kill the new Kurron Ni and loot {{item:18099}} (always drops). He despawns after about an hour, so do not leave him standing.',
						'The original Kurron Ni has a multi-day respawn, so do not give the armor away until you have all 900 platinum.',
					),
				),
				array(
					'title' => 'Letter to Duriek',
					'text' => 'Give {{item:18099}} to {{npc:75003:paineel:Duriek Bloodpool}} for his story and a block of experience. Say "apprentice", then "replace him".',
				),
				array(
					'title' => 'Cough elixir',
					'text' => 'Give 1000 platinum to {{npc:40034:neriaka:Smaka}} for {{item:14365}}. Smaka asks for no faction or items, only the coin.',
				),
				array(
					'title' => 'Hand Duriek the elixir',
					'text' => 'Give {{item:14365}} to {{npc:75003:paineel:Duriek Bloodpool}}. Then say "searching" and he sends you after a sealed tome in the ruins.',
				),
				array(
					'title' => 'Dusty Tome',
					'text' => 'In {{zone:hole}}, clear the ratman camp and loot {{item:14382}} from {{npc:39011:hole:a ratman guard}} (always drops). The guard shares a spawn point with ratman warriors and {{npc:39010:hole:Slizik the Mighty}}, so he may take a few cycles.',
					'sub' => array(
						'Give {{item:14382}} to {{npc:75003:paineel:Duriek Bloodpool}}, then say "items" to hear the four items he wants.',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Corrupted Ghoulbane',
			'intro' => 'Duriek wants {{item:5403}}, {{item:11609}}, {{item:5430}}, and {{item:14366}} all in one hand-in. Collect them in any order; the sheath is the only one that needs another quest NPC.',
			'steps' => array(
				array(
					'title' => 'Ghoulbane',
					'text' => '{{npc:62000:felwitheb:Joren Nobleheart}} (level 60) carries {{item:5403}} and always drops it. {{npc:65128:guktop:A froglok shin lord}} in {{zone:guktop}} drops it about a quarter of the time.',
					'sub' => array(
						'Alternative: combine {{item:2410}}, {{item:2411}}, {{item:2412}}, and {{item:2413}} in {{item:17878}} (no-fail). Ghoul hearts come from ghouls in {{zone:unrest}}, the scroll from goblins in {{zone:highkeep}}, the blade from goblins in {{zone:butcher}}, and the hilt from skeletons in {{zone:najena}}.',
					),
				),
				array(
					'title' => 'Soul Leech',
					'text' => 'In {{zone:fearplane}}, {{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, and {{npc:72004:fearplane:Fright}} each have about a 10% chance for {{item:11609}}. {{npc:72005:fearplane:Cazic Thule}} can drop it at a lower rate.',
				),
				array(
					'title' => 'Blade of Abrogation',
					'text' => '{{item:5430}} drops from many Plane of Sky mobs at roughly 5% each, including {{npc:71002:airplane:a windrider drake}}, {{npc:71012:airplane:The Spiroc Lord}}, and {{npc:71032:airplane:a thunder spirit princess}}. Plan on several clears.',
				),
				array(
					'title' => 'Drake Spine',
					'text' => '{{npc:50019:rathemtn:Rharzar}} in {{zone:rathemtn}} always drops {{item:14372}}. He rotates among many spawn points.',
				),
				array(
					'title' => 'Decrepit Hide',
					'text' => '{{npc:76002:hateplane:An ashenbone drake}} in {{zone:hateplane}} drops {{item:14371}} about 3% of the time. Hate is dangerous; bring a group and plan for a long camp.',
				),
				array(
					'title' => 'Decrepit Sheath',
					'text' => 'Give {{npc:45044:qcat:Teydar}} {{item:14372}}, {{item:14371}}, and {{item:16507}} for {{item:14366}}. Teydar needs at least -88 True Spirit. Say "decrepit sheath" first if you want the recipe in his words.',
				),
				array(
					'title' => 'Corrupted Ghoulbane',
					'text' => 'Give {{npc:75003:paineel:Duriek Bloodpool}} {{item:5403}}, {{item:11609}}, {{item:5430}}, and {{item:14366}} together for {{item:14367}}. He needs -83 True Spirit or better for this step.',
					'sub' => array(
						'Duriek despawns after the hand-in with a long respawn, so be sure you have everything before trading.',
						'Duriek mentions Lhranc, who is the final NPC in the chain.',
					),
				),
			),
		),
		array(
			'title' => 'Part 3 — Heart of the Innocent',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'One kill, two items.',
					'text' => '{{npc:39155:hole:Kyrenna}} drops both {{item:14380}} and {{item:14381}} every time. The blood is needed in Part 4, so loot both before leaving.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Caradon fights too.',
					'text' => 'The key turn-in spawns Kyrenna, who aggroes you, and {{npc:39069:hole:Caradon}} attacks as well. Plan for both level-55 fights at once.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Cell Key',
					'text' => 'Kill {{npc:39022:hole:a mimic}} in {{zone:hole}} (three spawn points close together) until {{item:14373}} drops (about 25%).',
				),
				array(
					'title' => 'Free Kyrenna',
					'text' => 'Give {{item:14373}} to {{npc:39069:hole:Caradon}} (needs better than -83 True Spirit). Kyrenna appears and both fight you. Kill Kyrenna and loot {{item:14380}} and {{item:14381}}.',
					'sub' => array(
						'Caradon has a very long respawn, so you only get one chance without waiting days.',
					),
				),
				array(
					'title' => 'Soulcase',
					'text' => 'Say "heart of an innocent" to {{npc:38058:tox:Knarthenne Skurl}} and he places {{item:17051}} on your cursor.',
				),
				array(
					'title' => 'Combine the heart',
					'text' => 'Place {{item:14380}} inside {{item:17051}} and combine for {{item:14368}} (no-fail).',
				),
			),
		),
		array(
			'title' => 'Part 4 — Head of the Valiant and Will of Innoruuk',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Marl vanishes after the shroud.',
					'text' => 'Handing {{item:14381}} to {{npc:74001:kerraridge:Marl Kastane}} depops him with the standard timer, and his Kerra Isle respawn is over a day. You still need him afterward for the wrappings, so line up the Mummy fight before you assume you can finish in one sitting.',
				),
				array(
					'kind' => 'note',
					'title' => 'Order inside the Marl chain.',
					'text' => 'Before he accepts the note, saying "prophecy" just gives you another {{item:14375}}. After the note, the dialogue keywords run in order: prophecy, Lhranc, one, curse, prediction, secret plan, complete the ritual.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Seal of Kastane',
					'text' => 'Find {{npc:74001:kerraridge:Marl Kastane}} on Kerra Isle. Say "prophecy" and he summons {{item:14375}} onto your cursor (needs -83 True Spirit).',
				),
				array(
					'title' => 'Gerot Kastane',
					'text' => 'Give {{item:14375}} to {{npc:75004:paineel:Gerot Kastane}} in {{zone:paineel}} for {{item:14376}}. He is relieved and urges you back to Marl.',
				),
				array(
					'title' => 'Return the note',
					'text' => 'Give {{item:14376}} to {{npc:74001:kerraridge:Marl Kastane}}. This sets a permanent quest flag so he will tell the full story. Say "prophecy" and follow the keywords until he asks for the blood of an innocent.',
				),
				array(
					'title' => 'Dark Shroud',
					'text' => 'After you have {{item:14381}} from Kyrenna, give it to {{npc:74001:kerraridge:Marl Kastane}} for {{item:14377}}. He leaves and clears your flag.',
				),
				array(
					'title' => 'Wake Glohnor',
					'text' => 'In the deep tomb area of {{zone:hole}}, find {{npc:39054:hole:the Ghost of Glohnor}} (several spawn points) and give him only {{item:14377}}. He vanishes and {{npc:39165:hole:the Mummy of Glohnor}} spawns.',
					'sub' => array(
						'The ghost script does not return unused items, so do not hand him anything else.',
						'Kill the Mummy (level 56) and loot {{item:14378}} and {{item:14379}}; both always drop.',
					),
				),
				array(
					'title' => 'Head of the Valiant',
					'text' => 'Give {{item:14378}} to {{npc:75004:paineel:Gerot Kastane}} for {{item:14369}}. He needs -72 True Spirit or better for this reward.',
				),
				array(
					'title' => 'Will of Innoruuk',
					'text' => 'Wait for {{npc:74001:kerraridge:Marl Kastane}} to respawn, then give him {{item:14379}} for {{item:14370}} (needs -72 True Spirit).',
				),
			),
		),
		array(
			'title' => 'Part 5 — Final turn-ins',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'One Lhranc a week.',
					'text' => '{{npc:90002:citymist:Lhranc}} vanishes when you hand over the four items, and his respawn is about seven days. Do not start until your group is ready for the fight.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Two timers.',
					'text' => 'The hostile {{npc:90015:citymist:Lhranc}} despawns after about two hours. When he dies, {{npc:90016:citymist:Marl Kastane}} spawns and leaves after roughly 80 minutes.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Lhranc\'s Token',
					'text' => 'Find {{npc:90002:citymist:Lhranc}} in {{zone:citymist}}. Give him {{item:14367}}, {{item:14368}}, {{item:14369}}, and {{item:14370}} together (needs -58 True Spirit) for {{item:14384}}. A new hostile {{npc:90015:citymist:Lhranc}} spawns.',
				),
				array(
					'title' => 'Kill Lhranc',
					'text' => 'Defeat the spawned Lhranc (level 63, about 30000 HP). His death spawns {{npc:90016:citymist:Marl Kastane}}.',
				),
				array(
					'title' => 'Innoruuk\'s Curse',
					'text' => 'Give {{item:14384}} to {{npc:90016:citymist:Marl Kastane}} to receive {{item:14383}}. The hand-in costs {{faction:404:True Spirit}} faction but there is no faction check on this step.',
				),
			),
		),
	),
	'verified' => array(
		'paineel/Duriek_Bloodpool.lua',
		'paineel/Gerot_Kastane.lua',
		'kerraridge/Marl_Kastane.lua',
		'hole/Caradon.lua',
		'hole/Ghost_of_Glohnor.lua',
		'citymist/Lhranc.lua',
		'citymist/#Lhranc.lua',
		'citymist/#Marl_Kastane.lua',
		'tox/Knarthenne_Skurl.lua',
		'qcat/Teydar.lua',
		'overthere/Kurron_Ni.lua',
		'overthere/#Kurron_Ni.lua',
		'neriaka/Smaka.lua',
		'soltemple/an_undead_knight.lua',
		'soltemple/an_undead_knight_.lua',
	),
);

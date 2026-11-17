<?php
/**
 * Beastlord Epic 1.0 — Claws of the Savage Spirit
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   sharvahl/Historian_Sharar.lua            "yes" -> summon 9061
 *   sharvahl/Elder_Animist_Dumul.lua         9061 talk; medal 4393/4375/4378 -> 9031+17361; 9044 -> 9045;
 *                                            9048 -> 9049; 9055+9056 -> 9055+9056+9057+17362; 9060 -> 8496+8495
 *   grimling/encounters/Husman|Danarin|Derrin.lua   medals 4393 / 4375 / 4378
 *   feerrott/Scout_Ahlikal.lua, #Scout_Ahlikal.lua       9031 -> spirit 47205; 9032 -> 9033+9031
 *   emeraldjungle/Scout_Vyrak.lua, #Scout_Vyrak.lua      9031 -> spirit 94126; 9038 -> 9039+9031
 *   lfaydark/Scout_Rahjiq.lua, #Scout_Rahjiq.lua         9031 -> spirit 57119; 9034 -> 9035+9031
 *   permafrost/Scout_Janomin.lua, #Scout_Janomin.lua     9031 -> spirit 73107; 9036 -> 9037+9031
 *   stonebrunt/Scout_Malom.lua, #Scout_Malom.lua         9031 -> spirit 100019; 9040 -> 9041+9031
 *   swampofnohope/Scout_Eyru.lua, #Scout_Eyru.lua        9031 -> spirit 83244; 9042 -> 9043+9031
 *   timorous/Scout_Sihmoj.lua                9045 -> 17360; spawns Draz Nurakk 96003 + #Scout_Sihmoj 96011
 *   timorous/Draz_Nurakk.lua                 death -> six 96010 images (9046)
 *   timorous/#Scout_Sihmoj.lua               9047 -> 9048
 *   sharvahl/Arms_Historian_Qua.lua          9049 -> 9050
 *   sharvahl/Weapons_Master_Rahoul.lua       9050 -> 17363; 9051 -> 9055+9056
 *   fungusgrove/Scout_Halmia.lua             9057 -> 9062; spawns #Draz_Nurakk 157009 + warder 157069
 *   fungusgrove/#Draz_Nurakk.lua             death signals warder to depop in 2 min
 *   thegrey/a_creature.lua                   random living-stone ambush (acrylia ore drops)
 * Combines (tradeskill_recipe): 6x wrapped totem in 17361 -> 9044; 6x 9046 in 17360 -> 9047;
 *   9052 + 2x 9053 + 9054 in 17363 -> 9051; 9055+9056+9059+9062 in 17362 -> 9060
 * Epic reward item ids: 8495 and 8496 (both named Claw of the Savage Spirit)
 */

return array(
	'toc' => array(
		'Medal, seal and chest',
		'Corrupted spirits and totems',
		'Draz Nurakk in Timorous',
		'Rahoul\'s claws',
		'Draz Nurakk in the Fungus Grove',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 4393,
			'note' => 'One medal is enough — {{item:4375}} and {{item:4378}} are accepted too. Earned from {{npc:167203:grimling:Scout Husman}}, {{npc:167202:grimling:Scout Danarin}} or {{npc:167201:grimling:Scout Derrin}}; each camp raid hands out a different medal',
		),
		array(
			'item' => 3681,
			'note' => 'Polished Acrylia Sphere — the Grimling Forest scouts expect one before they lead a raid',
		),
		array(
			'item' => 9052,
			'note' => 'Needed 1 — dropped by the living-stone ambush creatures in {{zone:thegrey}} (see Part 4)',
		),
		array(
			'item' => 9053,
			'note' => 'Needed 2 — Xi Vius / Xi Xaui in {{zone:maiden}}, e.g. {{npc:173004:maiden:Xi Vius}} and {{npc:173030:maiden:Xi Xaui}}',
		),
		array(
			'item' => 9054,
			'note' => 'Needed 1 — shriekers and mushrooms in {{zone:thedeep}}, e.g. {{npc:164016:thedeep:a shrieker}} and {{npc:164041:thedeep:a deepspore mushroom}}',
		),
		array(
			'html' => 'Nothing else in the chain can be gathered early. The totems, shards, head and the combine boxes are all unlocked step by step.',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Medal, seal and chest',
			'intro' => 'The Khati Sha need an errand-runner. Take the lore book to the Elder Animist, bring him proof of combat skill, and he will outfit you for the spirit hunt.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Any medal works.',
					'text' => 'Dumul accepts {{item:4393}}, {{item:4375}} or {{item:4378}}. You only need to finish one Grimling Forest raid; the Copper medal is the shortest.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Collection of Spirit Lore',
					'text' => 'Talk to {{npc:155225:sharvahl:Historian Sharar}} in {{zone:sharvahl}}. When he asks if you will carry a tome to the Animists, say yes and he conjures {{item:9061}} into your hands.',
				),
				array(
					'title' => 'Deliver the lore',
					'text' => 'Hand {{item:9061}} to {{npc:155040:sharvahl:Elder Animist Dumul}}. He asks whether you picked up any rumors; reply with the keyword "spiritual" and he tells you to go prove yourself in the Grimling Forest.',
				),
				array(
					'title' => 'Earn a medal of war',
					'text' => 'Join a Grimling Forest raid led by {{npc:167203:grimling:Scout Husman}}, {{npc:167202:grimling:Scout Danarin}} or {{npc:167201:grimling:Scout Derrin}}. Each scout wants a {{item:3681}} to start his raid, and the Garrison questline hands those out.',
					'sub' => array(
						'Husman rewards {{item:4393}}; Danarin rewards {{item:4375}}; Derrin rewards {{item:4378}}.',
						'Keep your scout alive: Husman states outright that the mission fails if he falls.',
					),
				),
				array(
					'title' => 'Seal and chest',
					'text' => 'Give Dumul the medal. You receive {{item:9031}} and {{item:17361}}, and your standing with the Khati Sha improves. Keep both in your bags; the chest is where the totems end up.',
					'sub' => array(
						'Lost the seal? Ask Dumul about a "lost seal". He only replaces it if your faction with him is good enough.',
						'Say "ready", then follow the keywords "new magic", "name" and "mission" to hear the full briefing about Draz Nurakk and his captured spirits.',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Corrupted spirits and totems',
			'intro' => 'Six Khati Sha scouts are camped around Norrath. Show each one the seal; the scout spawns a hostile spirit nearby, and killing the spirit drops a totem. Give the totem back to the scout for a wrapped copy and your seal.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'One seal, six uses.',
					'text' => 'Every scout takes {{item:9031}} to start and returns it with the wrapped totem. Do not lose it mid-chain. Scouts despawn on an hour timer, so do not leave a camp unfinished.',
				),
				array(
					'kind' => 'note',
					'title' => 'Order is free.',
					'text' => 'Do the six zones in any order. The spirits have 32k to 60k HP; plan a small group, not a solo run.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Feerrott — spider',
					'text' => 'Show the seal to {{npc:47197:feerrott:Scout Ahlikal}}. {{npc:47205:feerrott:The Spirit of Decay}} appears in nearby ruins. Loot {{item:9032}} (always drops), give it to the scout for {{item:9033}} and your seal.',
				),
				array(
					'title' => 'Emerald Jungle — gorilla',
					'text' => 'Show the seal to {{npc:94001:emeraldjungle:Scout Vyrak}}. {{npc:94126:emeraldjungle:The Spirit of Rage}} spawns in the ruins to the south. Loot {{item:9038}}, trade it for {{item:9039}} and the seal.',
				),
				array(
					'title' => 'Lesser Faydark — wolf',
					'text' => 'Show the seal to {{npc:57112:lfaydark:Scout Rahjiq}}. {{npc:57119:lfaydark:The Spirit of Malevolence}} appears. Loot {{item:9034}}, give it to the scout for {{item:9035}} and the seal.',
				),
				array(
					'title' => 'Permafrost — ice bear',
					'text' => 'Show the seal to {{npc:73083:permafrost:Scout Janomin}}. {{npc:73107:permafrost:The Icebound Spirit}} spawns in the cavern. Loot {{item:9036}}, trade it for {{item:9037}} and the seal.',
				),
				array(
					'title' => 'Stonebrunt — watcher',
					'text' => 'Show the seal to {{npc:100020:stonebrunt:Scout Malom}}. {{npc:100019:stonebrunt:The Spirit of Torment}} spawns up on the mountain slopes. Loot {{item:9040}}, trade it for {{item:9041}} and the seal.',
				),
				array(
					'title' => 'Swamp of No Hope — alligator',
					'text' => 'Show the seal to {{npc:83037:swampofnohope:Scout Eyru}}. {{npc:83244:swampofnohope:The Spirit of Sorrow}} appears. Loot {{item:9042}}, trade it for {{item:9043}} and the seal.',
				),
				array(
					'title' => 'Pack the chest',
					'text' => 'Combine all six wrapped totems — {{item:9033}}, {{item:9035}}, {{item:9037}}, {{item:9039}}, {{item:9041}} and {{item:9043}} — inside {{item:17361}} to make {{item:9044}}.',
				),
				array(
					'title' => 'Report back',
					'text' => 'Give {{item:9044}} to {{npc:155040:sharvahl:Elder Animist Dumul}}. He gives you {{item:9045}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Draz Nurakk in Timorous',
			'intro' => 'Dumul sends you to the islands of {{zone:timorous}} where a scout watches the animist\'s hideout.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Six adds on death.',
					'text' => 'When {{npc:96003:timorous:Draz Nurakk}} dies, six {{npc:96010:timorous:Draz Nurakk\'s Image}} spawn at once, each carrying a shard. You need all six. They despawn after about an hour, so loot quickly.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Scout Sihmoj',
					'text' => 'Find {{npc:96000:timorous:Scout Sihmoj}} on the island that has the great statue. Hand over {{item:9045}} and receive {{item:17360}}. A replacement scout appears and the animist spawns on the neighbouring island.',
				),
				array(
					'title' => 'Draz Nurakk',
					'text' => 'Kill {{npc:96003:timorous:Draz Nurakk}} in the ruined building. Watch for traps. He depops after about 30 minutes if nobody is fighting him.',
				),
				array(
					'title' => 'Shards of glass',
					'text' => 'Kill the six {{npc:96010:timorous:Draz Nurakk\'s Image}} and loot {{item:9046}} from each (100% drop).',
				),
				array(
					'title' => 'Sack of glass',
					'text' => 'Combine the six {{item:9046}} inside {{item:17360}} to make {{item:9047}}.',
				),
				array(
					'title' => 'Return the sack',
					'text' => 'Give {{item:9047}} to the replacement scout ({{npc:96011:timorous:#Scout Sihmoj}}) for {{item:9048}}. He lingers for about 90 minutes, so do not dawdle.',
				),
				array(
					'title' => 'Back to Dumul',
					'text' => 'Give {{item:9048}} to {{npc:155040:sharvahl:Elder Animist Dumul}} to receive {{item:9049}}.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Rahoul\'s claws',
			'steps' => array(
				array(
					'title' => 'Arms Historian Qua',
					'text' => 'Give {{item:9049}} to {{npc:155050:sharvahl:Arms Historian Qua}} for {{item:9050}}.',
				),
				array(
					'title' => 'Weapons Master Rahoul',
					'text' => 'Take {{item:9050}} to {{npc:155240:sharvahl:Weapons Master Rahoul}} and receive {{item:17363}}. He asks for a chunk of acrylia ore, two Void gems and fungal padding.',
				),
				array(
					'title' => 'Perfect Acrylia Ore',
					'text' => 'In {{zone:thegrey}}, wander the rocky areas until a living-stone ambusher leaps out. One of the stone creatures drops {{item:9052}}: {{npc:171006:thegrey:a boulder fiend}}, {{npc:171007:thegrey:a stone beast}}, {{npc:171008:thegrey:Boulder}} or {{npc:171009:thegrey:Stonehand}}.',
					'sub' => array(
						'The ambush is a random pick, so expect several triggers before the ore shows up.',
					),
				),
				array(
					'title' => 'Gems of the Void',
					'text' => 'Collect two {{item:9053}} from {{npc:173004:maiden:Xi Vius}} and {{npc:173030:maiden:Xi Xaui}} in {{zone:maiden}}.',
				),
				array(
					'title' => 'Dense Fungal Padding',
					'text' => 'Loot one {{item:9054}} from the fungus creatures of {{zone:thedeep}}, such as {{npc:164016:thedeep:a shrieker}} and {{npc:164068:thedeep:an ancient shrieker}}.',
				),
				array(
					'title' => 'Combine the box',
					'text' => 'Place {{item:9052}}, two {{item:9053}} and {{item:9054}} in {{item:17363}} to form {{item:9051}}.',
				),
				array(
					'title' => 'Jagged Claws of Rending',
					'text' => 'Hand {{item:9051}} to {{npc:155240:sharvahl:Weapons Master Rahoul}} for the pair of {{item:9055}} and {{item:9056}}.',
				),
				array(
					'title' => 'Show Dumul',
					'text' => 'Give both {{item:9055}} and {{item:9056}} to {{npc:155040:sharvahl:Elder Animist Dumul}}. He returns the claws, and also hands over {{item:9057}} and {{item:17362}}.',
				),
			),
		),
		array(
			'title' => 'Part 5 — Draz Nurakk in the Fungus Grove',
			'intro' => 'The animist has fled to an abandoned village in {{zone:fungusgrove}}. Find Scout Halmia, show her the war seal, and finish him off.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'He brings a friend.',
					'text' => '{{npc:157009:fungusgrove:Draz Nurakk}} has about 41k HP and pulls his ward-spirit {{npc:157069:fungusgrove:Draz Nurakk\'s Warder}} into the fight. Kill both. The warder disappears about two minutes after Draz dies.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Scout Halmia',
					'text' => 'Find {{npc:157008:fungusgrove:Scout Halmia}} and give her {{item:9057}}. She returns {{item:9062}} and Draz Nurakk and his warder spawn inside the huts.',
				),
				array(
					'title' => 'Kill Draz Nurakk',
					'text' => 'Fight both inside the old huts and loot {{item:9059}} from {{npc:157009:fungusgrove:Draz Nurakk}} (always drops). He despawns after an hour if the fight stalls.',
				),
				array(
					'title' => 'Pack the evidence',
					'text' => 'Combine {{item:9055}}, {{item:9056}}, {{item:9059}} and {{item:9062}} inside {{item:17362}} to make {{item:9060}}.',
				),
			),
		),
		array(
			'title' => 'Part 6 — Final turn-in',
			'steps' => array(
				array(
					'title' => 'Claws of the Savage Spirit',
					'text' => 'Give {{item:9060}} to {{npc:155040:sharvahl:Elder Animist Dumul}} for the pair {{item:8495}} and {{item:8496}}. He announces your deed to the whole city.',
				),
			),
		),
	),
	'verified' => array(
		'sharvahl/Historian_Sharar.lua',
		'sharvahl/Elder_Animist_Dumul.lua',
		'sharvahl/Arms_Historian_Qua.lua',
		'sharvahl/Weapons_Master_Rahoul.lua',
		'grimling/encounters/Husman.lua',
		'grimling/encounters/Danarin.lua',
		'grimling/encounters/Derrin.lua',
		'feerrott/Scout_Ahlikal.lua',
		'emeraldjungle/Scout_Vyrak.lua',
		'lfaydark/Scout_Rahjiq.lua',
		'permafrost/Scout_Janomin.lua',
		'stonebrunt/Scout_Malom.lua',
		'swampofnohope/Scout_Eyru.lua',
		'timorous/Scout_Sihmoj.lua',
		'timorous/#Scout_Sihmoj.lua',
		'timorous/Draz_Nurakk.lua',
		'fungusgrove/Scout_Halmia.lua',
		'fungusgrove/#Draz_Nurakk.lua',
		'thegrey/a_creature.lua',
	),
);

<?php
/**
 * Necromancer Epic 1.0 — Scythe of the Shadowed Soul
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   lakerathe/Kazen_Fecae.lua              20641 -> 20642; 20647 -> spawns bone golem 51153;
 *                                          20653 -> 18087; 20652 -> 20544
 *   nektulos/Venenzi_Oberzendi.lua         20642 -> 20643; 1320 -> 20649
 *   lakerathe/Emkel_Kabae.lua              20643+20649 -> 20644; 20650 -> 20647
 *   swampofnohope/Ssessthrass.lua          20644 -> 20645; 20654+20645 -> 20650
 *   lakerathe/a_bone_golem.lua             death -> a_failed_apprentice 51152
 *   lakerathe/a_failed_apprentice.lua      death -> a_tortured_soul 51144 (drops 20648)
 *   timorous/Drendico_Metalbones.lua       20648 -> 18086; 1278+20656+20655+18086 -> 20653
 *   airplane/a_thunder_spirit_princess.lua 10 gold -> unique spawn Gkzzallk 71073
 *   airplane/Gkzzallk.lua                  18087 -> 20652; 60m depop
 *   airplane/Drakis_Bloodcaster.lua        summons 18536; 18536 returned -> spawns Jzil G`Six 71074
 *   airplane/Jzil_GSix.lua                 20938+20782+20783 -> 1278 (Cloak of Spiroc Feathers)
 *   airplane/Key_Master.lua                signal 2 -> Keeper of Souls 71075 (unique)
 * Epic reward item id: 20544
 *
 * Gotchas: Kazen's Lua comment calls Venenzi "Oberendi" in dialogue; the NPC file / DB name is
 * Venenzi_Oberzendi (id 25113). Item 20656 is named "Eye of Innoruuk" but its lore text reads
 * "Innoruuk's Favor". Quest-only spawns (51153, 51152, 51144, 71073, 71074, 71075) have no
 * static spawn2 row in peq.
 */

return array(
	'toc' => array(
		'Apprentice tasks',
		'Test of the bone golems',
		'Drendico\'s components',
		'Final turn-ins',
	),
	'prefarm' => array(
		array(
			'item' => 20641,
			'note' => 'Wandering knight {{npc:4077:qeytoqrg:Sir Edwin Motte}} — also seen in {{zone:highpass}}, {{zone:freporte}}, and {{zone:rathemtn}}; always drops the head',
		),
		array(
			'item' => 1320,
			'note' => '{{npc:44100:najena:Najena}} (about 85%); other droppers include {{npc:93001:overthere:Modani Qu`Loni}} and {{npc:93113:overthere:Vaean the Night}}',
		),
		array(
			'item' => 20654,
			'note' => '{{npc:103051:chardok:Grand Herbalist Mak`ha}} (just over half the time) — plan a full group',
		),
		array(
			'item' => 20938,
			'note' => 'Silver Disc — {{npc:71031:airplane:an azarack}} (roughly 1 in 7)',
		),
		array(
			'item' => 20782,
			'note' => 'Spiroc Feathers — {{npc:71024:airplane:a sprited harpie}}, {{npc:71027:airplane:an avenging gazer}}, {{npc:71030:airplane:a gorgalask}}, or {{npc:71035:airplane:a watchful guard}}',
		),
		array(
			'item' => 20783,
			'note' => 'Black Silk Cape — {{npc:71075:airplane:Keeper of Souls}} (timed rare; see Part 3)',
		),
		array(
			'item' => 20655,
			'note' => 'Slime Blood of Cazic Thule — Fear named ({{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, {{npc:72004:fearplane:Fright}}, {{npc:72005:fearplane:Cazic Thule}})',
		),
		array(
			'item' => 20656,
			'note' => 'Eye of Innoruuk — {{npc:76003:hateplane:#Innoruuk}} (about half the time; weaker odds from Hate named)',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Apprentice tasks',
			'intro' => 'Everything starts at {{npc:51048:lakerathe:Kazen Fecae}} in {{zone:lakerathe}}. His apprentices send you on a loop: a head, a robe, a herb, and back again.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Say, don\'t trade.',
					'text' => 'Kazen and his apprentices react to short keywords as well as items. Hail Kazen, say "dark arts", then "prove" to hear the first request. Emkel reacts to the symbol names (for example "symbol of the serpent") and will not accept the Symbol of Testing.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Sir Edwin\'s head',
					'text' => 'Kill {{npc:4077:qeytoqrg:Sir Edwin Motte}} (level 33 knight) and loot {{item:20641}}. The same NPC type is placed in several zones, so use whichever one is closest. Hand the head to {{npc:51048:lakerathe:Kazen Fecae}} to receive {{item:20642}}.',
				),
				array(
					'title' => 'Venenzi\'s reagents',
					'text' => 'Give {{item:20642}} to {{npc:25113:nektulos:Venenzi Oberzendi}} (Nektulos Forest, roughly (−685, −1073)) for {{item:20643}}. She then asks you to hunt down the dark elf who copies her wardrobe; say "task" to hear it again.',
				),
				array(
					'title' => 'Najena\'s robe',
					'text' => 'Kill {{npc:44100:najena:Najena}} in {{zone:najena}} and loot {{item:1320}}. Give it to {{npc:25113:nektulos:Venenzi Oberzendi}} to receive {{item:20649}}.',
					'sub' => array(
						'The robe is not guaranteed on Najena, so other droppers (see Prefarmable) can save you a wait.',
					),
				),
				array(
					'title' => 'Symbol of the Serpent',
					'text' => 'Give {{npc:51047:lakerathe:Emkel Kabae}} both {{item:20643}} and {{item:20649}} together to receive {{item:20644}}.',
				),
				array(
					'title' => 'Ssessthrass',
					'text' => 'Show {{item:20644}} to {{npc:83074:swampofnohope:Ssessthrass}} in {{zone:swampofnohope}} to receive {{item:20645}}. He is hostile to rude speech, so stay polite and hand the symbol over right away.',
				),
				array(
					'title' => 'Manisi herb',
					'text' => 'Kill {{npc:103051:chardok:Grand Herbalist Mak`ha}} in {{zone:chardok}} and loot {{item:20654}}. Bring it back to {{npc:83074:swampofnohope:Ssessthrass}} along with {{item:20645}} to receive {{item:20650}}.',
				),
				array(
					'title' => 'Symbol of Testing',
					'text' => 'Give {{item:20650}} to {{npc:51047:lakerathe:Emkel Kabae}} for {{item:20647}}.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Test of the bone golems',
			'intro' => 'Gather a group before you hand over the symbol; Kazen does not fight you himself, but the creatures he raises will.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Give it to Kazen, not Emkel.',
					'text' => 'Emkel specifically warns against handing {{item:20647}} to him. Trade it to {{npc:51048:lakerathe:Kazen Fecae}} only when your group is buffed and ready.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Start the fight',
					'text' => 'Give {{item:20647}} to {{npc:51048:lakerathe:Kazen Fecae}}. A {{npc:51153:lakerathe:bone golem}} (level 55) rises near (−1499, +284) and engages whoever triggered it.',
				),
				array(
					'title' => 'Three fights in a row',
					'text' => 'Each kill spawns the next target: the golem is followed by {{npc:51152:lakerathe:a failed apprentice}} (level 48), which in turn is followed by {{npc:51144:lakerathe:a tortured soul}} (level 55) near (−1519, +348). Stay together — the replacements pop on top of the previous corpse.',
					'sub' => array(
						'The golem drops {{item:20658}} and the apprentice drops {{item:20659}}.',
						'The tortured soul always drops {{item:20648}}.',
					),
				),
				array(
					'title' => 'Journal of Drendico',
					'text' => 'Give {{item:20648}} to {{npc:96034:timorous:Drendico Metalbones}} in {{zone:timorous}} (rough location (+3837, +6476)). He hands over {{item:18086}}. Keep the journal: it is part of his final trade.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Drendico\'s components',
			'intro' => 'Drendico wants four things in one trade: {{item:1278}}, {{item:20656}}, {{item:20655}}, and {{item:18086}}. Two of them are raid-tier kills.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Plan the army.',
					'text' => 'Drendico\'s own dialogue calls for a large force. Cazic Thule and Innoruuk are the heavyweights; Dread, Terror, and Fright (Fear) as well as Hate named can be used for the slime blood and eye at lower odds.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Slime Blood of Cazic Thule',
					'text' => 'Kill the Fear named and loot {{item:20655}} — about 10% from {{npc:72000:fearplane:Dread}}, {{npc:72002:fearplane:Terror}}, and {{npc:72004:fearplane:Fright}}; slightly lower from {{npc:72005:fearplane:Cazic Thule}} (roughly (−335, +1278)).',
				),
				array(
					'title' => 'Eye of Innoruuk',
					'text' => 'Kill {{npc:76003:hateplane:#Innoruuk}} (roughly (−319, −9)) and loot {{item:20656}} — about 50%. Many Hate named (for example {{npc:76016:hateplane:#Lord of Loathing}} and {{npc:76045:hateplane:#Mistress of Scorn}}) carry it at about 5%.',
				),
				array(
					'title' => 'Cloak of Spiroc Feathers — start',
					'text' => 'In {{zone:airplane}}, find {{npc:71053:airplane:Drakis Bloodcaster}} (about (+655, +1305)). Hail him, say "ready", then say "Jzil" to receive {{item:18536}}. Hand the tome back and {{npc:71074:airplane:Jzil G`Six}} spawns; he despawns after about five minutes.',
				),
				array(
					'title' => 'Cloak — gather the test of power items',
					'text' => 'You need {{item:20938}}, {{item:20782}}, and {{item:20783}} in hand before you ask Jzil for the "test of power".',
					'sub' => array(
						'{{item:20938}} — {{npc:71031:airplane:an azarack}}.',
						'{{item:20782}} — harpies, gazers, gorgalasks, or watchful guards (about 8% each).',
						'{{item:20783}} — {{npc:71075:airplane:Keeper of Souls}} (about 50%). He is not on a fixed timer: killing {{npc:71100:airplane:a soul tamer}} or {{npc:71071:airplane:an essence tamer}} (the carrier → essence carrier → tamer chain starting at {{npc:71017:airplane:a soul carrier}}) signals the Key Master, which sets a four-hour lockout and spawns the Keeper roughly 48–80 minutes later at about (−1484, +720). He despawns after about 48 minutes if no one fights him.',
					),
				),
				array(
					'title' => 'Cloak — turn-in',
					'text' => 'Say "test of power" to {{npc:71074:airplane:Jzil G`Six}}, then give him {{item:20938}}, {{item:20782}}, and {{item:20783}} together for {{item:1278}}.',
				),
				array(
					'title' => 'Prepared Reagent Box',
					'text' => 'Give {{npc:96034:timorous:Drendico Metalbones}} {{item:1278}}, {{item:20656}}, {{item:20655}}, and {{item:18086}} in a single trade to receive {{item:20653}}.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Final turn-ins',
			'steps' => array(
				array(
					'title' => 'Tome of Instruction',
					'text' => 'Give {{item:20653}} to {{npc:51048:lakerathe:Kazen Fecae}} to receive {{item:18087}}. He shows you the scythe but keeps it until you finish the last errand.',
				),
				array(
					'title' => 'Gkzzallk',
					'text' => 'In {{zone:airplane}} on the first island, give 10 gold to {{npc:71032:airplane:a thunder spirit princess}} (about (+609, +1372)). Gkzzallk appears near (+288, +662) and stays for roughly an hour. Hail him if you like, then give him {{item:18087}} to receive {{item:20652}}.',
				),
				array(
					'title' => 'Epic reward',
					'text' => 'Give {{item:20652}} to {{npc:51048:lakerathe:Kazen Fecae}} to receive {{item:20544}}.',
				),
			),
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Mind the hour.',
					'text' => 'Gkzzallk depops 60 minutes after spawning, and the tome is consumed on the turn-in. Have the box trade, the travel, and the fight route planned before you pay the princess.',
				),
			),
		),
	),
	'verified' => array(
		'lakerathe/Kazen_Fecae.lua',
		'nektulos/Venenzi_Oberzendi.lua',
		'lakerathe/Emkel_Kabae.lua',
		'swampofnohope/Ssessthrass.lua',
		'timorous/Drendico_Metalbones.lua',
		'lakerathe/a_bone_golem.lua',
		'lakerathe/a_failed_apprentice.lua',
		'airplane/a_thunder_spirit_princess.lua',
		'airplane/Gkzzallk.lua',
		'airplane/Drakis_Bloodcaster.lua',
		'airplane/Jzil_GSix.lua',
		'airplane/Key_Master.lua',
		'airplane/Keeper_of_Souls.lua',
	),
);

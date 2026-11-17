<?php
/**
 * Rogue Epic 1.0 — Ragebringer
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins) and the allaclone DB (drops):
 *   qcat/Malka_Rale.lua                  say "help" -> summons 28014 (Stanos' Pouch)
 *   qey2hh1/Vilnius_the_Small.lua        28014 -> xp only; 5411+5410+5401+5308 -> 7506;
 *                                        1357+1253+1354+1360 -> 7505; 7041+7509+7508+7020 -> 28014
 *   highpass/Anson_McBale.lua            28014 -> spawn Stanos 5088; say "see stanos" respawns him
 *   highpass/Stanos_Herkanor.lua         28010+28011 -> 28012; 18961 -> 28057; 28013+7506+7505 -> 11057
 *   lakerathe/Eldreth.lua                2x 13087 + 28012 + 100pp -> 28053
 *   steamfont/Yendar_Starpyre.lua        28053 -> 28055; 28055+28016 -> spawn Renux 56172
 *   steamfont/Renux_Herkanor.lua         say "highpass hold" -> attacks; loot 18961
 *   kithicor/#<aide>.lua x7              28057 -> spawn General V'ghera 20205; loot 28013
 *   sro/Ortallius.lua                    12348+12349 -> 7041
 * DB: 28010 Founy Jestands (25%), 28011 Tani N`Mar (35%), 28016 ground spawn (hateplane),
 *     18961 + 7506 Renux 56172 (100%), 28013 General 20205 (100%), 7505 General (25%).
 * Epic reward item id: 11057
 */

return array(
	'toc' => array(
		'Vilnius\'s acquisition jobs',
		'Stanos and the parchment',
		'Translation and the Book of Souls',
		'Sealed box and the General',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 5411,
			'note' => 'Drops from {{npc:32066:soldungb:the Solusek kobold king}}',
		),
		array(
			'item' => 5410,
			'note' => 'Drops from {{npc:32045:soldungb:a kobold champion}}',
		),
		array(
			'item' => 5401,
			'note' => 'Drops from {{npc:66159:gukbottom:the froglok king}}',
		),
		array(
			'item' => 5308,
			'note' => 'Drops from {{npc:30100:everfrost:Karg IceBear}}',
		),
		array(
			'html' => 'Four robes: {{item:1357}} ({{npc:18000:paw:The Ishva Mal}}), {{item:1253}} ({{npc:64001:kedge:Phinigel Autropos}}), {{item:1354}} ({{npc:69044:oot:Oracle of K\'Arnon}}), {{item:1360}} ({{npc:66156:gukbottom:the ghoul arch magus}})',
		),
		array(
			'html' => 'Four rapiers: {{item:7041}} (Ortallius gem quest), {{item:7509}}, {{item:7508}}, {{item:7020}}',
		),
		array(
			'item' => 13087,
			'note' => 'Need two — sold by most food vendors; also bring 100 platinum for {{npc:51025:lakerathe:Eldreth}}',
		),
		array(
			'item' => 28016,
			'note' => 'Ground pickup in the Plane of Hate; no need to wait for the Yendar step',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Vilnius\'s acquisition jobs',
			'intro' => 'Get {{item:28014}} from the fugitive thief in the Qeynos sewers, hand it to Vilnius, and then work through his three shopping lists. The last list hands you a fresh pouch to carry to Highpass.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Keep the daggers.',
					'text' => '{{item:7506}} and {{item:7505}} come back at the very end. Do not sell or trade them away — Stanos asks for both with the final turn-in.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Malka Rale',
					'text' => 'Find {{npc:45002:qcat:Malka Rale}} hiding in the aqueducts. Say "Hanns", then "help", and she hands over {{item:28014}}.',
				),
				array(
					'title' => 'Vilnius the Small',
					'text' => 'Give the pouch to {{npc:12019:qey2hh1:Vilnius the Small}} at the bandit camp. He offers you work; say "job" to hear the first list. If you chat him up about "rump", he attacks, so skip that keyword.',
				),
				array(
					'title' => 'Job 1 — big swords for a collector',
					'text' => 'Give Vilnius {{item:5411}}, {{item:5410}}, {{item:5401}}, and {{item:5308}} together to receive {{item:7506}}.',
					'sub' => array(
						'{{item:5411}}: {{npc:32066:soldungb:the Solusek kobold king}}.',
						'{{item:5410}}: {{npc:32045:soldungb:a kobold champion}}.',
						'{{item:5401}}: {{npc:66159:gukbottom:the froglok king}}.',
						'{{item:5308}}: {{npc:30100:everfrost:Karg IceBear}}.',
					),
				),
				array(
					'title' => 'Job 2 — four robes',
					'text' => 'Give Vilnius {{item:1357}}, {{item:1253}}, {{item:1354}}, and {{item:1360}} together to receive {{item:7505}}.',
					'sub' => array(
						'{{item:1357}}: {{npc:18000:paw:The Ishva Mal}}.',
						'{{item:1253}}: {{npc:64001:kedge:Phinigel Autropos}}.',
						'{{item:1354}}: {{npc:69044:oot:Oracle of K\'Arnon}}.',
						'{{item:1360}}: {{npc:66156:gukbottom:the ghoul arch magus}}.',
						'Shortcut: the Kithicor aide {{npc:20054:kithicor:Coercer Q\'ioul}} also carries the Ishva and Kedge robes, but he is part of the later Kithicor group.',
					),
				),
				array(
					'title' => 'Job 3 — four rapiers',
					'text' => 'Give Vilnius {{item:7041}}, {{item:7509}}, {{item:7508}}, and {{item:7020}} together to receive a fresh {{item:28014}}.',
					'sub' => array(
						'{{item:7041}}: give {{item:12348}} and {{item:12349}} to {{npc:35036:sro:Ortallius}}.',
						'{{item:7509}}: {{npc:76302:hateplane:a scorn banshee}} (low drop rate).',
						'{{item:7508}}: {{npc:72094:fearplane:a scareling}}.',
						'{{item:7020}}: buy from {{npc:22089:ecommons:A Shady Swashbuckler}}.',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Stanos and the parchment',
			'intro' => 'Deliver the pouch, win Stanos\'s trust, and gather both halves of the coded document from the Kaladim and Neriak guildmasters.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Faction.',
					'text' => 'Stanos only takes turn-ins from characters with at least neutral standing with {{faction:332:Highpass Guards}}. Anson wants you above roughly −100 with his own faction before he will talk about Stanos.',
				),
				array(
					'kind' => 'note',
					'title' => 'Stanos despawns.',
					'text' => 'Every successful hand-in makes Stanos disappear. Go back to {{npc:5037:highpass:Anson McBale}} and say "see Stanos" to bring him out again.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Anson McBale',
					'text' => 'Give {{item:28014}} to {{npc:5037:highpass:Anson McBale}}. He pays you and spawns {{npc:5088:highpass:Stanos Herkanor}} nearby (quest spawn about (+10, +336)).',
				),
				array(
					'title' => 'Hear out Stanos',
					'text' => 'Hail {{npc:5088:highpass:Stanos Herkanor}}, then follow his keywords: "deal", "story", and "clear my name". He sends you after two document halves held by guildmasters in Kaladim and Neriak. If you attack him, his friends in the village come to help.',
				),
				array(
					'title' => 'Stained Parchment Top',
					'text' => 'Kill {{npc:67000:kaladimb:Founy Jestands}} and loot {{item:28010}} (about a 25% drop; may take several kills).',
				),
				array(
					'title' => 'Stained Parchment Bottom',
					'text' => 'Kill {{npc:42000:neriakc:Tani N\'Mar}} and loot {{item:28011}} (about a 35% drop; may take several kills).',
				),
				array(
					'title' => 'Combine the halves',
					'text' => 'Give {{npc:5088:highpass:Stanos Herkanor}} {{item:28010}} and {{item:28011}} together to receive {{item:28012}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Translation and the Book of Souls',
			'intro' => 'The combined parchment goes through two translators. Along the way you will need a book from the Plane of Hate and you will have to deal with the assassin who shows up at the end.',
			'steps' => array(
				array(
					'title' => 'Eldreth',
					'text' => 'Find {{npc:51025:lakerathe:Eldreth}} in his lakeside tower. Give him {{item:28012}}, two {{item:13087}}, and 100 platinum in a single trade for {{item:28053}}.',
					'sub' => array(
						'He wants the milk and platinum in the same window as the parchment; if anything is missing the trade bounces back.',
						'Eldreth leaves after the translation, so expect a respawn wait if you ever need a second look.',
					),
				),
				array(
					'title' => 'Yendar, first pass',
					'text' => 'Give {{item:28053}} to {{npc:56012:steamfont:Yendar Starpyre}} for {{item:28055}}. He cannot read it without a key text.',
				),
				array(
					'title' => 'Book of Souls',
					'text' => 'In {{zone:hateplane}}, pick up {{item:28016}} from the ground at about (−58, +325). It has a short respawn, so you can grab spares if someone in the group fails the pickup.',
					'sub' => array(
						'Older write-ups say the book is stolen from the Maestro of Rancor, but the {{npc:76006:hateplane:Maestro of Rancor}} loot tables on this server do not contain it.',
					),
				),
				array(
					'title' => 'Yendar, second pass',
					'text' => 'Give {{npc:56012:steamfont:Yendar Starpyre}} {{item:28055}} and {{item:28016}} together. He completes the translation, then a woman snatches the letter and Renux appears.',
				),
				array(
					'title' => 'Renux Herkanor',
					'text' => 'Hail {{npc:56172:steamfont:Renux Herkanor}}. When she asks where Stanos is hiding, say "Highpass Hold" to start the fight. Kill her and loot {{item:18961}} (always drops).',
					'sub' => array(
						'She also always carries another {{item:7506}}, which is a handy replacement if the original was lost.',
					),
				),
				array(
					'title' => 'Sealed box',
					'text' => 'Show the parchment to {{npc:5088:highpass:Stanos Herkanor}} by giving him {{item:18961}}. He identifies the plotter and hands you {{item:28057}}.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Sealed box and the General',
			'intro' => 'Stanos wants the General from the document dragged out of hiding in Kithicor. Any of his aides will take the box and cause him to appear.',
			'steps' => array(
				array(
					'title' => 'Pick an aide',
					'text' => 'Give {{item:28057}} to any one of the aides standing together in the Kithicor camp. They ignore you otherwise. Each hand-in gives a small XP award.',
					'sub' => array(
						'{{npc:20062:kithicor:#Ioltos V\'ghera}}',
						'{{npc:20063:kithicor:#Tasi V\'ghera}}',
						'{{npc:20065:kithicor:#Adjutant D\'kan}}',
						'{{npc:20055:kithicor:#Advisor C\'zatl}}',
						'{{npc:20061:kithicor:#Brigadier G\'tav}}',
						'{{npc:20054:kithicor:#Coercer Q\'ioul}}',
						'{{npc:20064:kithicor:#War Priestess T\'zan}}',
					),
				),
				array(
					'title' => 'General V\'ghera',
					'text' => 'The turn-in spawns {{npc:20205:kithicor:General V\'ghera}} close by (quest spawn about (+827, +2297)). Kill him and loot {{item:28013}} (always drops).',
					'sub' => array(
						'He also carries a chance at {{item:7505}} if you need a spare.',
					),
				),
			),
		),
		array(
			'title' => 'Part 5 — Final turn-in',
			'intro' => 'Return to Highpass with the pouch from the General and the two daggers from Vilnius.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Do not kill Stanos.',
					'text' => '{{npc:5088:highpass:Stanos Herkanor}} has a chance to drop {{item:28058}}, which pays off with Hanns or the General instead. That is a separate side reward and has nothing to do with the epic — every epic step is a hand-in, so there is no reason to fight him.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Ragebringer',
					'text' => 'Give {{npc:5088:highpass:Stanos Herkanor}} {{item:28013}}, {{item:7506}}, and {{item:7505}} together to receive {{item:11057}}. Use "see Stanos" with {{npc:5037:highpass:Anson McBale}} if he is not up.',
				),
			),
		),
	),
	'verified' => array(
		'qcat/Malka_Rale.lua',
		'qey2hh1/Vilnius_the_Small.lua',
		'highpass/Anson_McBale.lua',
		'highpass/Stanos_Herkanor.lua',
		'lakerathe/Eldreth.lua',
		'steamfont/Yendar_Starpyre.lua',
		'steamfont/Renux_Herkanor.lua',
		'kaladimb/Founy_Jestands.lua',
		'neriakc/Tani_N-Mar.lua',
		'kithicor/#Ioltos_V-ghera.lua',
		'kithicor/#Tasi_V-ghera.lua',
		'kithicor/#Adjutant_D-kan.lua',
		'kithicor/#Advisor_C-zatl.lua',
		'kithicor/#Brigadier_G-tav.lua',
		'kithicor/#Coercer_Q-ioul.lua',
		'kithicor/#War_Priestess_T-zan.lua',
		'kithicor/General_V-ghera.lua',
		'sro/Ortallius.lua',
	),
);

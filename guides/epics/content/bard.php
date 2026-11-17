<?php
/**
 * Bard Epic 1.0 — Singing Short Sword
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   dreadlands/Baldric_Slezaf.lua      20376+20383+20377+20538 -> 20542
 *   qey2hh1/Konia_Swiftfoot.lua        20379 -> 20376; 20366 -> 20383; "ready" summons torch 20532
 *   misty/Fajio_Knejo.lua              20532 -> 20533
 *   sro/Andad_Filla.lua                20533 -> 20534
 *   lakerathe/Misty_Tekcihta.lua       20534 -> 20379
 *   qeynos/Belious_Naliedin.lua        50pp -> 20373 (optional lore step); 20374 -> dialogue only
 *   unrest/Serra.lua                   "note" summons 20374; 20370 -> 20371; depop timer
 *   southkarana/Baenar_Swiftsong.lua   "doll" summons 20372; 20371 -> 20368; 20367 -> 20366
 *   soldunga/Marfen_Binkdirple.lua     20372 -> 20370 (summoned)
 *   qey2hh1/Maligar.lua                20368 -> spawns doppelganger 12173; Maligar depops
 *   southkarana/Kelkim_Menkia.lua      20527+20528+20529 -> 20377
 *   butcher/Vedico_Windwisper.lua      20530 -> 20378
 *   steamfont/Forpar_Fizfla.lua        20378 -> 20380; 20380+20524+20525+5520 -> 20535;
 *                                      11602+11622+16905 -> 20536; 20535+20536+20526 -> 20538
 *   sebilis/An_Undead_Bard.lua         20536 returned, bard turns hostile; death spawns #Trakanon 89039
 * Combines (tradeskill_recipe): 2x 10500 + 13006 in Forge (17) -> 16905 Metal Bits (Smithing, trivial 18)
 * Drops (peq loot tables): 20524 Phinigel Autropos 20%; 20525 a drolvarg warlord 45%;
 *   5520 Amygdalan warrior/knight 7.5%; 20527 Eldrig the Old; 20528 Nezekezena; 20529 Blackwing;
 *   20530 Quag Maelstrom; 20526 #Trakanon; 20367 Maligar's enraged doppelganger (all "100%" rows
 *   are guaranteed when the NPC is killed).
 * Epic reward item id: 20542
 *
 * Note: southkarana/a_hermit.lua (Unkempt Druids) hands out Winds of Karana sheet music and a
 * cracked flute. It is a separate bard quest and is NOT part of the Epic 1.0 chain.
 */

return array(
	'toc' => array(
		'Page 24, top half (torch relay)',
		'Page 24, bottom half (Mahlin\'s bongos)',
		'Page 25 (Kelkim\'s strings)',
		'Mystical Lute',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 20530,
			'note' => 'Drops from {{npc:69093:oot:Quag Maelstrom}}; first link of the lute chain',
		),
		array(
			'item' => 20524,
			'note' => '{{npc:64001:kedge:Phinigel Autropos}} (about 20%)',
		),
		array(
			'item' => 20525,
			'note' => '{{npc:102001:karnor:a drolvarg warlord}} (about 45%)',
		),
		array(
			'item' => 5520,
			'note' => '{{npc:72015:fearplane:an Amygdalan warrior}} or {{npc:72029:fearplane:an Amygdalan knight}} (low odds)',
		),
		array(
			'item' => 11602,
			'note' => 'White dragon scales — {{npc:73057:permafrost:Lady Vox}} or {{npc:86000:dreadlands:Gorenaire}} (not guaranteed)',
		),
		array(
			'item' => 11622,
			'note' => 'Red dragon scales — {{npc:32040:soldungb:Lord Nagafen}}, {{npc:39148:hole:Nortlav the Scalekeeper}}, or {{npc:91093:skyfire:Talendor}} (not guaranteed)',
		),
		array(
			'item' => 16905,
			'note' => 'Smithing: 2x {{item:10500}} + {{item:13006}} in a forge (trivial 18); not sold by vendors in the DB',
		),
		array(
			'item' => 20527,
			'note' => '{{npc:91009:skyfire:Eldrig the Old}} (always drops)',
		),
		array(
			'item' => 20528,
			'note' => '{{npc:87007:burningwood:Nezekezena}} (always drops)',
		),
		array(
			'item' => 20529,
			'note' => '{{npc:50323:rathemtn:Blackwing}} (always drops)',
		),
		array(
			'html' => 'Bring 50 platinum for the Qeynos letter of introduction, and be ready to fight a level 65 skeletal dragon in Old Sebilis near the end',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Page 24, top half (torch relay)',
			'intro' => 'Konia Swiftfoot in West Karana runs a four-stop relay across Norrath. Come back with the final baton and she pays you half of the sheet music.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Hand-offs only.',
					'text' => 'Konia says to hurry, but the scripts never check elapsed time. Every NPC in the relay simply swaps one baton for the next, so travel time is the only real cost.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Join the relay',
					'text' => 'Talk to {{npc:12117:qey2hh1:Konia Swiftfoot}} in {{zone:qey2hh1}}. Say you would "like to participate", then tell her you are "ready" to receive {{item:20532}}.',
				),
				array(
					'title' => 'Misty Thicket',
					'text' => 'Give {{item:20532}} to {{npc:33092:misty:Fajio Knejo}} in {{zone:misty}} for {{item:20533}}.',
				),
				array(
					'title' => 'Southern Desert of Ro',
					'text' => 'Give {{item:20533}} to {{npc:35030:sro:Andad Filla}} in {{zone:sro}} for {{item:20534}}.',
				),
				array(
					'title' => 'Lake Rathe',
					'text' => 'Give {{item:20534}} to {{npc:51052:lakerathe:Misty Tekcihta}} near the arena in {{zone:lakerathe}} for {{item:20379}}.',
				),
				array(
					'title' => 'Back to Konia',
					'text' => 'Return {{item:20379}} to {{npc:12117:qey2hh1:Konia Swiftfoot}} for {{item:20376}}.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Page 24, bottom half (Mahlin\'s bongos)',
			'intro' => 'Konia holds the other half of the page and will only part with it for {{item:20366}}. Getting them means helping a grieving father, so this is the longest dialogue chain in the quest.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Maligar fights back.',
					'text' => 'Handing {{item:20368}} to {{npc:12076:qey2hh1:Maligar}} makes him depop and spawn {{npc:12173:qey2hh1:Maligar\'s Enraged Doppleganger}} on top of you, already angry. Go in with your group ready.',
				),
				array(
					'kind' => 'note',
					'title' => 'Optional lore steps.',
					'text' => 'The scripts do not check that you did the flyer or letter of introduction before Baenar will talk about the doll; they only give flavor. The guide lists them in story order so you can follow the quest text if you prefer.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Serra the ghost',
					'text' => 'In {{zone:unrest}}, find {{npc:63029:unrest:Serra}}. Ask about her lost "something", then the "doll", then the "note" to receive {{item:20374}}.',
				),
				array(
					'title' => 'Belious Naliedin (optional)',
					'text' => 'Show {{item:20374}} to {{npc:1125:qeynos:Belious Naliedin}} in {{zone:qeynos}} to learn who the bard is. Then hand him 50 platinum to buy {{item:20373}}.',
				),
				array(
					'title' => 'Baenar Swiftsong',
					'text' => 'Meet {{npc:14046:southkarana:Baenar Swiftsong}} at the fountain in {{zone:southkarana}}. Give him {{item:20373}} to open conversation, then work through his stories until you can ask about the "doll". He gives you {{item:20372}}.',
				),
				array(
					'title' => 'The mechanical doll',
					'text' => 'Take {{item:20372}} to {{npc:31080:soldunga:Marfen Binkdirple}} in {{zone:soldunga}} and he hands over {{item:20370}}.',
				),
				array(
					'title' => 'Serra again',
					'text' => 'Return to {{npc:63029:unrest:Serra}} and give her {{item:20370}} for {{item:20371}}. She fades away and despawns for a short time.',
				),
				array(
					'title' => 'Note to Maligar',
					'text' => 'Give {{item:20371}} to {{npc:14046:southkarana:Baenar Swiftsong}} for {{item:20368}}.',
				),
				array(
					'title' => 'Maligar\'s head',
					'text' => 'Give {{item:20368}} to {{npc:12076:qey2hh1:Maligar}} in {{zone:qey2hh1}}. Kill the doppelganger that spawns and loot {{item:20367}}.',
				),
				array(
					'title' => 'The bongos',
					'text' => 'Give {{item:20367}} to {{npc:14046:southkarana:Baenar Swiftsong}} for {{item:20366}}.',
				),
				array(
					'title' => 'Page 24, bottom half',
					'text' => 'Give {{item:20366}} to {{npc:12117:qey2hh1:Konia Swiftfoot}} for {{item:20383}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Page 25 (Kelkim\'s strings)',
			'intro' => 'Kelkim lost her lute strings to gnolls and wants three replacement guts in return for the last page.',
			'steps' => array(
				array(
					'title' => 'Chromodrac Gut',
					'text' => 'Kill {{npc:91009:skyfire:Eldrig the Old}} in {{zone:skyfire}} and loot {{item:20527}}.',
				),
				array(
					'title' => 'Red Wurm Gut',
					'text' => 'Kill {{npc:87007:burningwood:Nezekezena}} in {{zone:burningwood}} and loot {{item:20528}}.',
				),
				array(
					'title' => 'Onyx Drake Gut',
					'text' => 'Kill {{npc:50323:rathemtn:Blackwing}} in {{zone:rathemtn}} and loot {{item:20529}}.',
				),
				array(
					'title' => 'Maestro\'s Symphony Page 25',
					'text' => 'Give all three guts to {{npc:14082:southkarana:Kelkim Menkia}} in {{zone:southkarana}} for {{item:20377}}.',
				),
			),
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Not the hermit.',
					'text' => 'The hermit in South Karana trades druid sheet music and a flute for a different quest. You do not need him for this epic.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Mystical Lute',
			'intro' => 'Forpar Fizfla builds the lute in three pieces. The finished instrument is flagged for bards only.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Keep the lute body safe.',
					'text' => '{{npc:89168:sebilis:An Undead Bard}} in {{zone:sebilis}} asks to hold {{item:20536}} "one last time". The script returns the body but turns him hostile; when he dies he spawns {{npc:89039:sebilis:#Trakanon}} aimed at you. Be ready to fight both in sequence.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Alluring Horn',
					'text' => 'Kill {{npc:69093:oot:Quag Maelstrom}} in {{zone:oot}} and loot {{item:20530}} (always drops).',
				),
				array(
					'title' => 'Vedico\'s favor',
					'text' => 'Give {{item:20530}} to {{npc:68115:butcher:Vedico Windwisper}} in {{zone:butcher}} for {{item:20378}}.',
				),
				array(
					'title' => 'Forpar Fizfla',
					'text' => 'Give {{item:20378}} to {{npc:56130:steamfont:Forpar Fizfla}} in {{zone:steamfont}} for {{item:20380}}. Ask him about "mystical instrument", then "components".',
				),
				array(
					'title' => 'Lute head',
					'text' => 'Give Forpar {{item:20380}}, {{item:20524}}, {{item:20525}}, and {{item:5520}} together for {{item:20535}}.',
					'sub' => array(
						'{{item:20524}} drops from {{npc:64001:kedge:Phinigel Autropos}} in {{zone:kedge}}.',
						'{{item:20525}} drops from {{npc:102001:karnor:a drolvarg warlord}} in {{zone:karnor}}.',
						'{{item:5520}} drops from Amygdalan warriors and knights in {{zone:fearplane}}.',
					),
				),
				array(
					'title' => 'Lute body',
					'text' => 'After he asks if you want the "next pieces", give Forpar {{item:11602}}, {{item:11622}}, and {{item:16905}} for {{item:20536}}.',
					'sub' => array(
						'{{item:16905}} is a Smithing combine: two {{item:10500}} and one {{item:13006}} in a forge.',
					),
				),
				array(
					'title' => 'Undead Dragongut Strings',
					'text' => 'In Old Sebilis, tell {{npc:89168:sebilis:An Undead Bard}} you are on a similar quest and agree to kill the dragon for him (final line: "kill it for you"). Give him {{item:20536}}. He returns it, attacks, and on death summons {{npc:89039:sebilis:#Trakanon}}. Kill him and loot {{item:20526}}.',
				),
				array(
					'title' => 'Mystical Lute',
					'text' => 'Give Forpar {{item:20535}}, {{item:20536}}, and {{item:20526}} together for {{item:20538}}.',
				),
			),
		),
		array(
			'title' => 'Part 5 — Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Four items, one hand-in.',
					'text' => 'Baldric requires all four items in a single trade. Check you are holding both page halves, page 25, and the lute before you open the window.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Baldric Slezaf',
					'text' => 'Find {{npc:86097:dreadlands:Baldric Slezaf}} in {{zone:dreadlands}}. Give him {{item:20376}}, {{item:20383}}, {{item:20377}}, and {{item:20538}} together to receive {{item:20542}}.',
				),
			),
		),
	),
	'verified' => array(
		'dreadlands/Baldric_Slezaf.lua',
		'steamfont/Forpar_Fizfla.lua',
		'southkarana/Kelkim_Menkia.lua',
		'southkarana/Baenar_Swiftsong.lua',
		'qey2hh1/Konia_Swiftfoot.lua',
		'misty/Fajio_Knejo.lua',
		'sro/Andad_Filla.lua',
		'lakerathe/Misty_Tekcihta.lua',
		'unrest/Serra.lua',
		'qeynos/Belious_Naliedin.lua',
		'soldunga/Marfen_Binkdirple.lua',
		'qey2hh1/Maligar.lua',
		'butcher/Vedico_Windwisper.lua',
		'sebilis/An_Undead_Bard.lua',
	),
);

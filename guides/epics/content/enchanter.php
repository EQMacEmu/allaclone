<?php
/**
 * Enchanter Epic 1.0 — Staff of the Serpent
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   erudnext/Stofo_Olan.lua              10600+10601+10602 -> 10603
 *   akanon/Rilgor_Plegnog.lua            1360 -> 10600
 *   qcat/Reania_Jukle.lua                say -> cursor 10626
 *   gukbottom/a_ghoul_scribe.lua         10626 -> 10601
 *   qey2hh1/Chrislin_Baker.lua           13063+13051 -> 12100; spawns Thrackin Griften 12001 (10602)
 *   burningwood/a_sarnak_imitator.lua    10603 -> 10604; 10639 (faction < 5) -> 10650
 *   overthere/Modani_Qu-Loni.lua         10604 -> 17861+10604; 10635 -> 10610
 *   felwitheb/Joren_Nobleheart.lua       10627 -> 10608
 *   oggok/Bozlum_Blossom.lua             10628 -> 10629; 10630 -> 10609
 *   oggok/Brokk_Boxtripper.lua           10629 -> 10630
 *   akanon/Clockwork_VIIX.lua            10604 -> 17861+10604; 10636 -> 10611
 *   firiona/Nadia_Starfeast.lua          10604 -> 17861+10604; keywords -> 10631..10634; 10637 -> 10612
 *   kaesora/spectral_librarian.lua       10631 -> 10618; spawns enraged librarian 88087
 *   skyfire/Felia_Goldenwing.lua         10632 -> 10619
 *   citymist/Wraith_of_Jaxion.lua        10633 -> 10620
 *   overthere/Impaler_Tzilug.lua         10634 (faction < 5) -> 10621
 *   hole/Polzin_Mrid.lua                 10604 -> 17861+10604; 10638 -> 10613
 * Combines (tradeskill_recipe, container 17861): 10606+10607+10608+10609 -> 10635; 10614..10617 -> 10636;
 *   10618..10621 -> 10637; 10622..10625 -> 10638; 10610..10613 -> 10639
 * Epic reward item id: 10650
 */

return array(
	'toc' => array(
		'Lead-in and Jeb\'s Seal',
		'Test of Illusion (Modani)',
		'Test of Enlightenment (Mizzle)',
		'Test of Charm (Nadia)',
		'Test of the Phantasm (Polzin)',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'html' => 'Faction: every master and Jeb is {{faction:404:True Spirit}}. Each hand-in raises it, but check you are Amiable or better with Jeb before the last trade.',
		),
		array(
			'item' => 1360,
			'note' => 'For the pen — {{npc:56012:steamfont:Yendar Starpyre}} or {{npc:66156:gukbottom:the ghoul arch magus}}',
		),
		array(
			'item' => 10608,
			'note' => 'Via {{npc:103080:chardok:Prince Selrach Di`zok}} head, handed to {{npc:62000:felwitheb:Joren Nobleheart}}',
		),
		array(
			'item' => 10609,
			'note' => 'Oggok sandals-to-flowers chain (Bozlum and Brokk)',
		),
		array(
			'item' => 10615,
			'note' => 'Ground spawn in {{zone:overthere}} (long respawn)',
		),
		array(
			'item' => 10616,
			'note' => 'Ground spawn in {{zone:dalnir}}',
		),
		array(
			'item' => 10617,
			'note' => 'Ground spawn in {{zone:airplane}} (very long respawn)',
		),
		array(
			'html' => 'Optional early camps: Xolion Rod, Innoruuk\'s Word, Shissir head, ghost essence, vampire essence, and mystic sands — none of them are gated behind Jeb\'s Seal, so you can collect them before you ever meet him.',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Lead-in and Jeb\'s Seal',
			'intro' => 'Gather a pen, a vial of ink, and a sheet of paper for {{npc:24013:erudnext:Stofo Olan}}, trade the resulting notes to Jeb Lumsed, and carry his seal to the four masters.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'One seal is enough.',
					'text' => '{{item:10604}} is returned to you every time you show it to a master, together with a fresh {{item:17861}}. You only earn it once.',
				),
				array(
					'kind' => 'note',
					'title' => 'Ask for the sack.',
					'text' => 'Each master hands over a sack only in exchange for the seal. Asking about a "sack" without having shown it just earns a reminder to see Jeb first, so do not hunt for a replacement from them any other way.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Mechanical Pen',
					'text' => 'Give {{npc:55121:akanon:Rilgor Plegnog}} a {{item:1360}} for {{item:10600}}. The robe drops from {{npc:56012:steamfont:Yendar Starpyre}} (always) and, less often, from {{npc:66156:gukbottom:the ghoul arch magus}}.',
				),
				array(
					'title' => 'Ink of the Dark',
					'text' => 'Ask {{npc:45082:qcat:Reania Jukle}} about the ink ("what is the ink of the dark", then "find the ink of the dark") to receive {{item:10626}}.',
					'sub' => array(
						'Trade the vial to {{npc:66170:gukbottom:a ghoul scribe}} for {{item:10601}}.',
					),
				),
				array(
					'title' => 'White Paper',
					'text' => 'Buy {{item:13063}} and {{item:13051}} from any scribe-supply vendor (for example {{npc:1052:qeynos:Hanlore Escaval}}). Give both to {{npc:12074:qey2hh1:Chrislin Baker}}; she thanks you with {{item:12100}} and leaves.',
					'sub' => array(
						'Her departure spawns {{npc:12001:qey2hh1:Thrackin Griften}} (unique spawn). Kill him and loot {{item:10602}}.',
					),
				),
				array(
					'title' => 'Copy of Notes',
					'text' => 'Give {{npc:24013:erudnext:Stofo Olan}} {{item:10600}}, {{item:10601}}, and {{item:10602}} together for {{item:10603}}.',
				),
				array(
					'title' => 'Jeb\'s Seal',
					'text' => 'Find {{npc:87001:burningwood:a sarnak imitator}}; he is the disguised Jeb Lumsed (3-day respawn). Give him {{item:10603}} for {{item:10604}}.',
					'sub' => array(
						'Hail him and say "Jeb Lumsed" if you want his hints about the four masters.',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Test of Illusion (Modani)',
			'intro' => 'Hand {{item:10604}} to {{npc:93001:overthere:Modani Qu`Loni}} to receive a sack and your seal back. Combine {{item:10606}}, {{item:10607}}, {{item:10608}}, and {{item:10609}} inside the sack to make {{item:10635}}.',
			'steps' => array(
				array(
					'title' => 'Xolion Rod',
					'text' => 'Kill {{npc:106007:cabeast:Vessel Drozlin}} and loot {{item:10606}}.',
				),
				array(
					'title' => 'Innoruuk\'s Word',
					'text' => 'Kill {{npc:42112:neriakc:Verina Tomb}} and loot {{item:10607}}.',
				),
				array(
					'title' => 'Chalice of Kings',
					'text' => 'Kill {{npc:103080:chardok:Prince Selrach Di`zok}} and loot {{item:10627}}. Give it to {{npc:62000:felwitheb:Joren Nobleheart}} for {{item:10608}}.',
				),
				array(
					'title' => 'Snow Blossoms',
					'text' => 'Pick up the {{item:10628}} ground spawn in {{zone:oggok}} (about (+216, +387)). Give them to {{npc:49023:oggok:Bozlum Blossom}} for {{item:10629}}, bring the parchment to {{npc:49061:oggok:Brokk Boxtripper}} for {{item:10630}}, and return the gift to Bozlum for {{item:10609}}.',
				),
				array(
					'title' => 'First staff piece',
					'text' => 'Combine the four items in the sack, then give {{item:10635}} to {{npc:93001:overthere:Modani Qu`Loni}} for {{item:10610}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Test of Enlightenment (Mizzle)',
			'intro' => 'Mizzle appears as {{npc:55017:akanon:Clockwork VIIX}}; ask "are you Mizzle" to open the conversation. Trade the seal for a sack, then combine {{item:10614}}, {{item:10615}}, {{item:10616}}, and {{item:10617}} into {{item:10636}}.',
			'steps' => array(
				array(
					'title' => 'Spoon',
					'text' => 'Kill {{npc:37157:oasis:Cazel}} and loot {{item:10614}}.',
				),
				array(
					'title' => 'The One Key',
					'text' => 'Ground spawn in {{zone:overthere}} (about (-194, -384), deep below the ridgeline; roughly one hour respawn). Loot {{item:10615}}.',
				),
				array(
					'title' => 'Lost Scroll',
					'text' => 'Ground spawn in {{zone:dalnir}} (about (-17, +54), z -180). Loot {{item:10616}}.',
				),
				array(
					'title' => 'Charm and Sacrifice',
					'text' => 'Ground spawn in {{zone:airplane}} (about (+1296, +559)). Loot {{item:10617}}; respawn is on the order of ten days.',
				),
				array(
					'title' => 'Second staff piece',
					'text' => 'Combine in the sack, then give {{item:10636}} to {{npc:55017:akanon:Clockwork VIIX}} for {{item:10611}}.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Test of Charm (Nadia)',
			'intro' => '{{npc:84116:firiona:Nadia Starfeast}} takes the seal for a sack, then hands out four dull gems on request. Each gem is traded to a specific creature, and the four enchanted gems are combined into {{item:10637}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Gem keywords.',
					'text' => 'After the seal, say "gems", then ask for each stone by color: "diamond", "sapphire", "ruby", "emerald". She re-issues a stone if you lose or fail one.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Tzilug faction.',
					'text' => '{{npc:93149:overthere:Impaler Tzilug}} only accepts the emerald if you are Amiable or better with {{faction:318:Venril Sathir}}; otherwise he attacks you.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Enchanted Diamond',
					'text' => 'Give {{item:10631}} to {{npc:88000:kaesora:a spectral librarian}} for {{item:10618}}. The turn-in spawns hostile {{npc:88087:kaesora:an enraged spectral librarian}}; kill it (or leave — it depops after about five minutes out of combat).',
					'sub' => array(
						'Its death can release several freed souls that attack you.',
					),
				),
				array(
					'title' => 'Enchanted Sapphire',
					'text' => 'Give {{item:10632}} to {{npc:91081:skyfire:Felia Goldenwing}} for {{item:10619}}.',
				),
				array(
					'title' => 'Enchanted Ruby',
					'text' => 'Give {{item:10633}} to {{npc:90004:citymist:Wraith of Jaxion}} for {{item:10620}}.',
				),
				array(
					'title' => 'Enchanted Emerald',
					'text' => 'Give {{item:10634}} to {{npc:93149:overthere:Impaler Tzilug}} for {{item:10621}}.',
				),
				array(
					'title' => 'Third staff piece',
					'text' => 'Combine the four enchanted gems in the sack and give {{item:10637}} to {{npc:84116:firiona:Nadia Starfeast}} for {{item:10612}}.',
				),
			),
		),
		array(
			'title' => 'Part 5 — Test of the Phantasm (Polzin)',
			'intro' => 'Show {{item:10604}} to {{npc:39063:hole:Polzin Mrid}}; say "test of the phantasm" for his shopping list. Combine {{item:10622}}, {{item:10623}}, {{item:10624}}, and {{item:10625}} into {{item:10638}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Long timers.',
					'text' => 'The Shissir wraith in Fear respawns about once a week, and the vampire essence is a low-percentage drop from the forsaken revenants. Start these early.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Head of the Serpent',
					'text' => 'Kill {{npc:72107:fearplane:Wraith of a Shissir}} and loot {{item:10622}}.',
				),
				array(
					'title' => 'Essence of a Ghost',
					'text' => 'Kill {{npc:39080:hole:the Ghost of Kindle}} and loot {{item:10623}}.',
				),
				array(
					'title' => 'Essence of a Vampire',
					'text' => 'Camp {{npc:76004:hateplane:a forsaken revenant}} until {{item:10624}} drops (rare; many identical spawns).',
				),
				array(
					'title' => 'Sands of the Mystics',
					'text' => 'Kill {{npc:78070:fieldofbone:the tangrin}} and loot {{item:10625}}.',
				),
				array(
					'title' => 'Fourth staff piece',
					'text' => 'Combine in the sack and give {{item:10638}} to {{npc:39063:hole:Polzin Mrid}} for {{item:10613}}.',
				),
			),
		),
		array(
			'title' => 'Part 6 — Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Faction check.',
					'text' => 'Jeb refuses the bundle unless you are Amiable or better with {{faction:404:True Spirit}}. If the staves come back to you, raise faction first.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Bundle of Staves',
					'text' => 'Place {{item:10610}}, {{item:10611}}, {{item:10612}}, and {{item:10613}} in {{item:17861}} and combine them to create {{item:10639}}.',
				),
				array(
					'title' => 'Staff of the Serpent',
					'text' => 'Return to {{npc:87001:burningwood:a sarnak imitator}} (Jeb Lumsed) and give him {{item:10639}} to receive {{item:10650}}.',
				),
			),
		),
	),
	'verified' => array(
		'erudnext/Stofo_Olan.lua',
		'akanon/Rilgor_Plegnog.lua',
		'qcat/Reania_Jukle.lua',
		'gukbottom/a_ghoul_scribe.lua',
		'qey2hh1/Chrislin_Baker.lua',
		'burningwood/a_sarnak_imitator.lua',
		'overthere/Modani_Qu-Loni.lua',
		'felwitheb/Joren_Nobleheart.lua',
		'oggok/Bozlum_Blossom.lua',
		'oggok/Brokk_Boxtripper.lua',
		'akanon/Clockwork_VIIX.lua',
		'firiona/Nadia_Starfeast.lua',
		'kaesora/spectral_librarian.lua',
		'kaesora/enraged_spectral_librarian.lua',
		'skyfire/Felia_Goldenwing.lua',
		'citymist/Wraith_of_Jaxion.lua',
		'overthere/Impaler_Tzilug.lua',
		'hole/Polzin_Mrid.lua',
	),
);

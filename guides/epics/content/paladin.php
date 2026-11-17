<?php
/**
 * Paladin Epic 1.0 — Fiery Defender
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   airplane/Dason_Goldblade.lua    18527 -> spawn Dirkog 71077 (18526 -> Gregori, not used here)
 *   airplane/Dirkog_Steelhand.lua   500pp -> spawn Inte Akera 71091; 5 min depop
 *   airplane/Inte_Akera.lua         5504 -> 18033; 5403 -> 18034; 18033+18034+19073+1254 -> 11050; faction < 3; 30 min depop
 *   qeynos2/Rineval_Talyas.lua      19071+19070+1000pp -> 18302
 *   neriakb/Rysva_To-Biath.lua      19071+19070+1000pp -> 18302
 *   oot/Oracle_of_K-Arnon.lua       18302 -> 19072
 *   everfrost/Lich_of_Miragul.lua   19072 -> spawn Miragul 30094
 *   paw/Brother_Hayle.lua           18936 -> 18937; 18937+13947+18828+12197 -> 5504 (faction >= 750)
 *   freportw/peasant_woman.lua      "take" -> 29008 (summoned to cursor)
 *   freportw/Joshua.lua             29008 -> 29009
 *   kaladimb/Jark.lua               "dinner" spawns Nella 67090; 29007 -> 29006
 *   kaladimb/Nella_Stonebraids.lua  "Jark"/"dinner" -> 29007 (summoned); depops
 *   felwithea/Elia_the_Pure.lua     29002 -> 29005
 *   hateplane/a_scorn_banshee.lua   5% -> spawn thought destroyer 76387 (Kunark-gated)
 *   erudnext/Reklon_Gnallen.lua     29001+29006 -> 29004; 29000+29009 -> 29003; 29003+29004+29005 -> 29010
 *   fearplane/Irak_Altil.lua        11050 + 29010 -> 10099
 * Drops (DB): 29000 Keeper of the Tombs (hole); 29001 thought destroyer; 29002 Kirak Vil (nektulos);
 *   5403 Joren Nobleheart / froglok shin lord; 19073 + 1254 Miragul (100%)
 * Epic reward item id: 10099
 *
 * Faction note (TAKP DB): Inte Akera uses npc_faction 740 (primary Deepwater Knights 242).
 * Script requires GetFaction < 3 for the weapon blessings and the final Avenger hand-in.
 */

return array(
	'toc' => array(
		'Fiery Avenger',
		'Mark of Atonement',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 5504,
			'note' => 'Paladin-only quest weapon from {{npc:18071:paw:Brother Hayle}} — consumed at Inte Akera',
		),
		array(
			'item' => 5403,
			'note' => 'Drops from {{npc:62000:felwitheb:Joren Nobleheart}} or (rarer) {{npc:65128:guktop:the froglok shin lord}} — consumed at Inte Akera',
		),
		array(
			'item' => 18302,
			'note' => 'Book of Scale — buy via Rineval/Rysva with {{item:19070}} + {{item:19071}} + 1000pp, then feed the Oracle',
		),
		array(
			'item' => 29000,
			'note' => '{{npc:39116:hole:Keeper of the Tombs}} (always drops)',
		),
		array(
			'item' => 29001,
			'note' => '{{npc:76387:hateplane:a thought destroyer}} — rare spawn off {{npc:76302:hateplane:a scorn banshee}} (always drops)',
		),
		array(
			'item' => 29002,
			'note' => '{{npc:25301:nektulos:Kirak Vil}} (always drops)',
		),
		array(
			'html' => 'Optional: 500 platinum for Dirkog, plus 1000 platinum for the Book of Scale route; Plane of Sky access to reach Dason / Dirkog / Inte',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Fiery Avenger',
			'intro' => 'Inte Akera wants a Paladin weapon sacrifice twice, then the head and robe of Miragul. Prepare {{item:5504}}, {{item:5403}}, {{item:19073}}, and {{item:1254}} before you pay Dirkog — Inte is only up for about 30 minutes.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Both weapons are consumed.',
					'text' => '{{item:5504}} and {{item:5403}} are handed to Inte for his blessing notes ({{item:18033}} and {{item:18034}}). Do not turn in a weapon you still plan to use. Each blessing needs {{faction:242:Deepwater Knights}} standing good enough for the script check (GetFaction below 3).',
				),
				array(
					'kind' => 'note',
					'title' => 'Inte stays up briefly.',
					'text' => '{{npc:71091:airplane:Inte Akera}} depops on a timer about 30 minutes after he spawns, and {{npc:71077:airplane:Dirkog Steelhand}} leaves after about 5 minutes. If Inte disappears mid-chain, pay Dirkog again; the blessing notes you already hold stay in your bags.',
				),
			),
			'steps' => array(
				array(
					'title' => 'SoulFire',
					'text' => 'Complete the Temple of Life paladin line with {{npc:18071:paw:Brother Hayle}} in {{zone:paw}}. After the sealed note step, he hands over {{item:5504}} in exchange for {{item:18937}}, {{item:13947}}, {{item:18828}}, and {{item:12197}} (needs faction 750+ with him).',
					'sub' => array(
						'The sealed note is {{item:18936}}; the hilt comes from the troll shadowknight Hayle mentions near Mistmoore.',
						'If faction is too low at the final hand-in, the script eats the items instead of paying out — check standing first.',
					),
				),
				array(
					'title' => 'Ghoulbane',
					'text' => 'Kill {{npc:62000:felwitheb:Joren Nobleheart}} in {{zone:felwitheb}} (always drops {{item:5403}}) or farm {{npc:65128:guktop:the froglok shin lord}} in {{zone:guktop}} (about 25%).',
				),
				array(
					'title' => 'Miragul\'s Phylactery',
					'text' => 'Obtain {{item:18302}}: bring {{item:19070}}, {{item:19071}}, and 1000 platinum to {{npc:2041:qeynos2:Rineval Talyas}} in {{zone:qeynos2}} or {{npc:41042:neriakb:Rysva To`Biath}} in {{zone:neriakb}}. Give the book to {{npc:69044:oot:the Oracle of K\'Arnon}} in {{zone:oot}} for {{item:19072}}.',
					'sub' => array(
						'Frost book candidates include {{npc:73057:permafrost:Lady Vox}}, {{npc:86000:dreadlands:Gorenaire}}, and {{npc:96089:timorous:Faydedar}}.',
						'Burnt book candidates include {{npc:32040:soldungb:Lord Nagafen}}, {{npc:94002:emeraldjungle:Severilous}}, and {{npc:91093:skyfire:Talendor}}.',
					),
				),
				array(
					'title' => 'Release the Lich',
					'text' => 'Give {{item:19072}} to {{npc:30093:everfrost:the Lich of Miragul}} in {{zone:everfrost}}. He vanishes and {{npc:30094:everfrost:Miragul}} spawns in place — kill him and loot {{item:19073}} and {{item:1254}} (both always drop).',
				),
				array(
					'title' => 'Dirkog the guide',
					'text' => 'In {{zone:airplane}}, speak with {{npc:71037:airplane:Dason Goldblade}}, choose Dirkog, and read {{item:18527}}. Return the book to Dason to spawn {{npc:71077:airplane:Dirkog Steelhand}}. Ask about holy swords, who to talk to, and where Inte Akera is, then hand over 500 platinum.',
					'sub' => array(
						'Dirkog depops and {{npc:71091:airplane:Inte Akera}} spawns nearby (script spawn at about (−586, +767)).',
					),
				),
				array(
					'title' => 'Two blessings',
					'text' => 'Give {{npc:71091:airplane:Inte Akera}} {{item:5504}} to receive {{item:18033}}, then {{item:5403}} to receive {{item:18034}} (separate hand-ins).',
				),
				array(
					'title' => 'Fiery Avenger',
					'text' => 'Give Inte {{item:18033}}, {{item:18034}}, {{item:19073}}, and {{item:1254}} together to receive {{item:11050}}. He leaves afterward. Keep Miragul\'s items until this step — Inte also asks you to return both blessing notes.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Mark of Atonement',
			'intro' => 'Three tainted darksteel relics are cleansed in separate ways, then {{npc:24044:erudnext:Reklon Gnallen}} in {{zone:erudnext}} fuses the cleansed pieces into {{item:29010}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Summoned items.',
					'text' => 'The peasant woman and Nella hand you quest items by summoning them to your cursor when you say the right keyword — keep your cursor clear.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Thought destroyer is Kunark-gated.',
					'text' => 'The rare {{npc:76387:hateplane:thought destroyer}} only spawns off scorn banshees if Ruins of Kunark content is active in the Lua. Confirm your server era before planning a long camp.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Tainted Darksteel Sword',
					'text' => 'Kill {{npc:39116:hole:Keeper of the Tombs}} in {{zone:hole}} for {{item:29000}}.',
				),
				array(
					'title' => 'Bucket of pure water',
					'text' => 'In {{zone:freportw}}, ask {{npc:9122:freportw:the peasant woman}} to "take" the water to her brother; she summons {{item:29008}}. Give it to {{npc:9121:freportw:Joshua}} for {{item:29009}}.',
				),
				array(
					'title' => 'Gleaming Crested Sword',
					'text' => 'Give {{npc:24044:erudnext:Reklon Gnallen}} {{item:29000}} and {{item:29009}} to receive {{item:29003}}.',
				),
				array(
					'title' => 'Tainted Darksteel Breastplate',
					'text' => 'Farm {{npc:76302:hateplane:a scorn banshee}} in {{zone:hateplane}} until {{npc:76387:hateplane:a thought destroyer}} spawns (about 5%), then loot {{item:29001}}.',
				),
				array(
					'title' => 'Pure Crystal',
					'text' => 'In {{zone:kaladimb}}, ask {{npc:67056:kaladimb:Jark}} about dinner so {{npc:67090:kaladimb:Nella Stonebraids}} spawns. Tell Nella about Jark or dinner for {{item:29007}} (she then depops). Give the plate to Jark for {{item:29006}}.',
				),
				array(
					'title' => 'Gleaming Crested Breastplate',
					'text' => 'Give Reklon {{item:29001}} and {{item:29006}} to receive {{item:29004}}.',
				),
				array(
					'title' => 'Tainted Darksteel Shield',
					'text' => 'Kill {{npc:25301:nektulos:Kirak Vil}} in {{zone:nektulos}} (long respawn; he hunts Paladins) and loot {{item:29002}}.',
				),
				array(
					'title' => 'Gleaming Crested Shield',
					'text' => 'Give {{npc:61013:felwithea:Elia the Pure}} in {{zone:felwithea}} {{item:29002}} to receive {{item:29005}}.',
				),
				array(
					'title' => 'Mark of Atonement',
					'text' => 'Give Reklon {{item:29003}}, {{item:29004}}, and {{item:29005}} together for {{item:29010}}.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Respawn.',
					'text' => '{{npc:72078:fearplane:Irak Altil}} has a long respawn (about 18 hours). Bring {{item:11050}} and {{item:29010}} before you travel into {{zone:fearplane}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Fiery Defender',
					'text' => 'Give {{npc:72078:fearplane:Irak Altil}} {{item:11050}} and {{item:29010}} together to receive {{item:10099}}.',
				),
			),
		),
	),
	'verified' => array(
		'airplane/Dason_Goldblade.lua',
		'airplane/Dirkog_Steelhand.lua',
		'airplane/Inte_Akera.lua',
		'qeynos2/Rineval_Talyas.lua',
		'neriakb/Rysva_To-Biath.lua',
		'oot/Oracle_of_K-Arnon.lua',
		'everfrost/Lich_of_Miragul.lua',
		'paw/Brother_Hayle.lua',
		'freportw/peasant_woman.lua',
		'freportw/Joshua.lua',
		'kaladimb/Jark.lua',
		'kaladimb/Nella_Stonebraids.lua',
		'felwithea/Elia_the_Pure.lua',
		'hateplane/a_scorn_banshee.lua',
		'erudnext/Reklon_Gnallen.lua',
		'fearplane/Irak_Altil.lua',
	),
);

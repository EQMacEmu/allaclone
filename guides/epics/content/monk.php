<?php
/**
 * Monk Epic 1.0 — Celestial Fists
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   dreadlands/Brother_Balatin.lua  12979+12980+12256 -> 12970
 *   southkarana/#Brother_Qwinn.lua  12316+10114 -> 12314
 *   rathemtn/Brother_Zephyl.lua     12317+10133 -> 12315
 *   lavastorm/a_fire_sprite.lua     spawns Eejag 27119
 *   airplane/a_presence.lua         1684 -> Gwan 71069
 *   nurga/a_sleeping_ogre.lua       1685 -> Trunt 107000
 *   overthere/Astral_Projection.lua 1686 -> 1687
 *   lakeofillomen/Astral_Projection.lua 1687 -> Vorash/Deep; Vorash death -> Xenevorash
 *   erudnext/Tomekeeper_Danl.lua    18195 -> 1682
 *   timorous/Lheao.lua              12970+1682 -> 1683
 *   trakanon/Kaiaren.lua (crazy)    1683 returns 1683; 1688+1689 -> 10652
 *   trakanon/#Kaiaren.lua (sane)    1683 -> 1689; 1688+1689 -> 10652
 * Epic reward item id: 10652
 *
 * Faction note (TAKP DB): Brother Zephyl / Brother Qwinn have no Ashen Order kill
 * hits (npc_faction_id 0 / Beta Neutral). Ran`un / Thena Lonnes do hit Ashen Order.
 */

return array(
	'toc' => array(
		'Robe of the Whistling Fists',
		'Demon Fangs',
		'Celestial Fists (book)',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'html' => 'Headband chain if questing the robe: {{item:10110}} → {{item:10111}} → {{item:10112}} → {{item:10113}} → {{item:10114}}',
		),
		array(
			'html' => 'Sash chain if questing the robe: {{item:10130}} → {{item:10131}} → {{item:10132}} → {{item:10133}}',
		),
		array(
			'item' => 12256,
			'note' => 'Drops from {{npc:50321:rathemtn:Brother Zephyl}} or {{npc:14054:southkarana:Brother Qwinn}}, or craft via Option 1',
		),
		array(
			'item' => 12979,
			'note' => 'Metal Pipe (Fi) — {{npc:103193:chardok:an iksar betrayer}}',
		),
		array(
			'item' => 12980,
			'note' => 'Metal Pipe (Zan) — {{npc:102004:karnor:a drolvarg pawbuster}}',
		),
		array(
			'item' => 1684,
			'note' => 'From {{npc:27119:lavastorm:Eejag}} (spawned via fire sprite challenge)',
		),
		array(
			'item' => 18195,
			'note' => 'Skyfire named, e.g. {{npc:91099:skyfire:a lava walker}}, {{npc:91003:skyfire:a shadow drake}}, {{npc:91100:skyfire:a soul devourer}}, {{npc:91004:skyfire:a wandering wurm}}, {{npc:91192:skyfire:Black Scar}}, {{npc:91009:skyfire:Eldrig the Old}}, {{npc:91012:skyfire:Faerie of Dismay}}, {{npc:91002:skyfire:Guardian of Felia}}',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Robe of the Whistling Fists',
			'intro' => 'Collect {{item:12256}}, {{item:12980:Metal Pipe (Zan)}}, and {{item:12979:Metal Pipe (Fi)}} (any order), then turn them in to {{npc:86136:dreadlands:Brother Balatin}} for {{item:12970}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Two paths for the robe.',
					'text' => 'Option 1 quests {{item:12314}} and {{item:12315}}, then combines them into {{item:12256}}. Option 2 camps the robe directly off either Brother. You still need both pipes either way.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Option 1A — Needle of the Void',
					'text' => 'Give {{npc:14054:southkarana:Brother Qwinn}} {{item:12316}} and {{item:10114}} to receive {{item:12314}}.',
					'sub' => array(
						'{{item:12316}} drops from {{npc:32067:soldungb:Targin the Rock}}.',
						'{{item:10114}} comes from the colored headband line ({{item:10110}} → {{item:10111}} → {{item:10112}} → {{item:10113}} → {{item:10114}}); headbands are tradable.',
					),
				),
				array(
					'title' => 'Option 1B — Rare Robe Pattern',
					'text' => 'Give {{npc:50321:rathemtn:Brother Zephyl}} {{item:12317}} and {{item:10133}} to receive {{item:12315}}.',
					'sub' => array(
						'{{item:12317}} drops from {{npc:66172:gukbottom:Raster of Guk}}.',
						'{{item:10133}} from the sash line ({{item:10130}} → {{item:10131}} → {{item:10132}} → {{item:10133}}), or loot from {{npc:9027:freportw:Ran`un}} / {{npc:9087:freportw:Thena Lonnes}}. Those two Freeport monks hit {{faction:361:Ashen Order}} on kill (−50); Brother Zephyl / Qwinn do not on TAKP.',
					),
				),
				array(
					'title' => 'Option 1C — Combine Robe of the Lost Circle',
					'text' => 'No-fail combine in a sewing kit / loom: {{item:12315}}, {{item:12314}}, {{item:16483}}, and {{item:15734}} → {{item:12256}}.',
					'sub' => array(
						'{{item:16483}} is Tailoring (trivial ~36): {{item:13779}} + {{item:16482}} + {{item:15522}}.',
						'{{item:15734}} is sold by bard song merchants.',
					),
				),
				array(
					'title' => 'Option 2 — Camp the robe',
					'text' => '{{item:12256}} can drop from {{npc:50321:rathemtn:Brother Zephyl}} (hill-giant area; rare PH) or {{npc:14054:southkarana:Brother Qwinn}} (west side / Splitpaw area; rare). Tough melee fights with high resists; the robe is not guaranteed. Red robe graphic when the drop is up.',
				),
				array(
					'title' => 'Metal Pipe (Zan)',
					'text' => 'Loot {{item:12980}} from {{npc:102004:karnor:a drolvarg pawbuster}} (Warlord room). Rare spawn; always drops the pipe.',
				),
				array(
					'title' => 'Metal Pipe (Fi)',
					'text' => 'Loot {{item:12979}} from {{npc:103193:chardok:an iksar betrayer}} (Bridge Keeper / outer bridge wall PH). Rare spawn; always drops the pipe.',
				),
				array(
					'title' => 'Turn-in — Brother Balatin',
					'text' => 'Find {{npc:86136:dreadlands:Brother Balatin}} (skeleton PH on a small wizard spire). He is KoS — open trade quickly, or FD / charm as needed. Hand {{item:12256}}, {{item:12979}}, and {{item:12980}} for {{item:12970}}.',
				),
			),
		),
		array(
			'title' => 'Part 2 — Demon Fangs',
			'steps' => array(
				array(
					'title' => 'Eejag',
					'text' => 'Hail {{npc:27113:lavastorm:a fire sprite}} and say "I challenge eejag" to spawn {{npc:27119:lavastorm:Eejag}} at the lava lake (quest spawn about (+1037, +298)). Kill and loot {{item:1684}}.',
				),
				array(
					'title' => 'Gwan',
					'text' => 'In Plane of Sky island 1.5, buy {{item:20911}} from {{npc:71056:airplane:Key Master}} if needed. Give {{item:1684}} to {{npc:71066:airplane:a presence}} to spawn {{npc:71069:airplane:Gwan}}. Kill and loot {{item:1685}}. Fight inside the hut to avoid knockback.',
				),
				array(
					'title' => 'Trunt',
					'text' => 'Give {{item:1685}} to {{npc:107101:nurga:a sleeping ogre}} (sneak if needed so the turn-in works) to spawn {{npc:107000:nurga:Trunt}}. Kill and loot {{item:1686}}.',
				),
				array(
					'title' => 'Eye of Kaiaren',
					'text' => 'Give {{item:1686}} to {{npc:93154:overthere:Astral Projection}} for {{item:1687}}.',
				),
				array(
					'title' => 'Xenevorash',
					'text' => 'On the lake platform (levitate helps), give {{item:1687}} to {{npc:85153:lakeofillomen:Astral Projection}} to spawn {{npc:85211:lakeofillomen:Vorash}} and {{npc:85223:lakeofillomen:Deep}}. Killing Vorash spawns {{npc:85208:lakeofillomen:Xenevorash}} (quest spawn about (−807, −542)). Drag fights to shore if they swim off. Loot {{item:1688}} from Xenevorash.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Celestial Fists (book)',
			'steps' => array(
				array(
					'title' => 'Immortals',
					'text' => 'Farm Skyfire named until you loot {{item:18195}}. Droppers include {{npc:91099:skyfire:a lava walker}}, {{npc:91003:skyfire:a shadow drake}}, {{npc:91100:skyfire:a soul devourer}}, {{npc:91004:skyfire:a wandering wurm}}, {{npc:91192:skyfire:Black Scar}}, {{npc:91009:skyfire:Eldrig the Old}}, {{npc:91012:skyfire:Faerie of Dismay}}, and {{npc:91002:skyfire:Guardian of Felia}}. Named place-hold each other; a tracker helps.',
				),
				array(
					'title' => 'Danl\'s Reference',
					'text' => 'Give {{item:18195}} to {{npc:24034:erudnext:Tomekeeper Danl}} (Erudin library, upper floor) for {{item:1682}}.',
				),
				array(
					'title' => 'Book graphic',
					'text' => 'Give {{npc:96001:timorous:Lheao}} {{item:1682}} and {{item:12970}} for {{item:1683}} (book graphic).',
				),
			),
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Robe clicky.',
					'text' => 'Turning the robe into Lheao permanently consumes {{item:12970}}. Finish using the robe click before this step.',
				),
			),
		),
		array(
			'title' => 'Part 4 — Final turn-in',
			'intro' => 'There are two Kaiaren NPCs in {{zone:trakanon}}. Order matters.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Do not mix the books up.',
					'text' => '{{item:1683}} (shorter name) and {{item:1689}} look similar. Wrong order wastes the turn-in path — read both steps before trading.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Crazy Kaiaren',
					'text' => 'Find {{npc:95105:trakanon:Kaiaren}} (southeast toward Old Sebilis; may path or get stuck). Give {{item:1683}}. He returns the book and attacks — kill or run him off. This spawns the sane form elsewhere.',
				),
				array(
					'title' => 'Sane Kaiaren — book exchange',
					'text' => 'Find {{npc:95040:trakanon:#Kaiaren}} in the hut (quest spawn about (+306, +2470)). Give {{item:1683}} alone to receive {{item:1689}}.',
				),
				array(
					'title' => 'Epic reward',
					'text' => 'Give {{npc:95040:trakanon:#Kaiaren}} {{item:1689}} and {{item:1688}} together to receive {{item:10652}}.',
				),
			),
		),
	),
	'verified' => array(
		'dreadlands/Brother_Balatin.lua',
		'southkarana/#Brother_Qwinn.lua',
		'rathemtn/Brother_Zephyl.lua',
		'lavastorm/a_fire_sprite.lua',
		'airplane/a_presence.lua',
		'nurga/a_sleeping_ogre.lua',
		'overthere/Astral_Projection.lua',
		'lakeofillomen/Astral_Projection.lua',
		'lakeofillomen/Vorash.lua',
		'erudnext/Tomekeeper_Danl.lua',
		'timorous/Lheao.lua',
		'trakanon/Kaiaren.lua',
		'trakanon/#Kaiaren.lua',
	),
);

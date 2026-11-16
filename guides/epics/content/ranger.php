<?php
/**
 * Ranger Epic 1.0 — Swiftwind and Earthcaller
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   burningwood/Telin_Darkforest.lua    say chain -> 20472 Worn Note; 20447 -> 20448; 20468 -> 20477 (+1M xp); 20481 -> 20481 + spawn Faelin 87019
 *   gfaydark/Faelin_Bloodbriar.lua      20472 -> 20446; 20 min depop
 *   kithicor/Giz_X-Tin.lua              20446 -> 20447 + spawn Teir`Dal Reaver 20190 (hostile)
 *   eastkarana/Althele.lua              20448 -> 20450 + spawn Sionae 15178; 20452 -> 18959 (+100k xp)
 *   eastkarana/Sionae.lua               20450 -> 20451 + spawn Nuien 15167
 *   eastkarana/Nuien.lua                20451 -> 20451 + spawn Teloa 15170
 *   eastkarana/Teloa.lua                20451 -> gathering walk; wp3 spawns corruptor 15153 + reavers 15150 / 15000
 *   (Dark_Elf_Corruptor 15153 loot)     20452 Fleshbound Tome 100%
 *   misty/Ella_Foodcrafter.lua          18959 -> 17860; 20473+20460 -> 20462
 *   timorous/Alrik_Farsight.lua         say "ancient bowl" / "take the artifact" -> 20457; 20474 -> 18960
 *   felwitheb/Farios_Elianos.lua        20457 -> 20458; 20459 -> 20474
 *   felwithea/Merchant_Nora.lua         20458 -> 20459
 *   iceclad/Sojan_the_Sleepless.lua     25104 -> 20474 (alternate Receipt)
 *   kaladimb/Kinlo_Strongarm.lua        5664 -> 20455; 20478 -> 20478; 20485 -> 20482 + spawn Usbak 67089; 20482 -> 20483
 *   qrg/Merdan_Fleetfoot.lua            12774 -> 20475
 *   frontiermtns/a_human_skeleton.lua   20475 -> 20476
 *   qrg/Niera_Farbreeze.lua             20476 -> 20456
 *   hole/Jaeil_the_Wretched.lua         17860 -> spawns Jaeil the Insane 39154 (drops 20478 100%)
 *   erudnint/Mairee_Silentone.lua       20478 -> 20479; 20479+20494 -> 20485
 *   airplane/a_soul_carrier.lua         death -> 2x essence carrier 71070
 *   airplane/an_essence_carrier.lua     death -> essence tamer 71071 (drops 20494 100%)
 *   kaladimb/Usbak_the_Old.lua          20477 -> 20481; 30 min depop
 *   karnor/Venril_Sathirs_remains.lua   10295 -> spawn Spirit 102019
 *   karnor/Spirit_of_Venril_Sathir.lua  15392 -> spawn #Venril Sathir 102021 (drops 20461 100%)
 *   karnor/Venril_Sathir.lua            remains depop if real Venril Sathir 102010 is up
 *   firiona/Foloal_Stormforest.lua      20462+20461 -> 20468
 *   burningwood/Faelin_Bloodbriar.lua   20481 -> 20487 Swiftwind (+1M xp); 30 min depop
 *   northkarana/Xanuusus.lua            20483+20484 -> 20488 Earthcaller (+2.5M xp)
 * Combines (tradeskill_recipe, Pottery, no-fail):
 *   Shiny Tin Bowl 17860: 20464+20465+20466+20467 -> 20473
 *   container obj type 21: 18960+20455+20456 -> 20460
 * Foraging (forage table): 20464 gfaydark, 20465 misty, 20466 innothule, 20467 everfrost, 12774 firiona
 * Epic reward item ids: 20487 (Swiftwind), 20488 (Earthcaller)
 *
 * Faction note (TAKP DB / Lua): Telin is gated by Faydarks Champions at Amiable or
 * better (GetFaction < 5); Alrik / Farios need Keepers of the Art Amiable or better;
 * Kinlo talks at Indifferent or better and trades at Apprehensive or better. Faelin,
 * Althele, Foloal, Mairee, and Xanuusus have no faction check on these turn-ins.
 */

return array(
	'toc' => array(
		'Opening — Faelin, Giz, and Althele',
		'Softly Glowing Stone',
		'Pulsing Green Stone',
		'Ancient Longsword and the Hammer',
		'Swiftwind',
		'Earthcaller',
	),
	'prefarm' => array(
		array(
			'item' => 5664,
			'note' => 'Needed by {{npc:67030:kaladimb:Kinlo Strongarm}} for the clay — drops from {{npc:90005:citymist:a black reaver}} (7.5%, also three more black reaver spawns in {{zone:citymist}})',
		),
		array(
			'item' => 12774,
			'note' => 'Forage in {{zone:firiona}} (about 4%)',
		),
		array(
			'html' => 'Four Hardened Mixture ingredients (forage, ~10% each): {{item:20464}} in {{zone:gfaydark}}, {{item:20465}} in {{zone:misty}}, {{item:20466}} in {{zone:innothule}}, {{item:20467}} in {{zone:everfrost}}',
		),
		array(
			'item' => 10295,
			'note' => 'Cast {{item:15237:Dance of the Fireflies}} (Ranger / Druid spell; scroll sold in {{zone:qrg}}, {{zone:gfaydark}}, and Rivervale) to create the globe for Venril Sathir\'s remains',
		),
		array(
			'item' => 15392,
			'note' => 'Resurrection scroll, sold by various city casters (e.g. {{npc:61022:felwithea:Assa Leafwind}}); handed to Venril Sathir\'s spirit',
		),
		array(
			'item' => 20484,
			'note' => 'Drops in {{zone:hateplane}} — 50% from {{npc:76003:hateplane:#Innoruuk}}, about 2% from several other named',
		),
		array(
			'item' => 25104,
			'note' => 'Optional: Coldain Toolset skips the Felwithe errand chain by giving {{npc:110066:iceclad:Sojan the Sleepless}} for the Receipt',
		),
	),
	'sections' => array(
		array(
			'title' => 'Opening — Faelin, Giz, and Althele',
			'intro' => 'Start with {{npc:87082:burningwood:Telin Darkforest}} in {{zone:burningwood}}. He needs {{faction:246:Faydarks Champions}} at Amiable or better; Faelin and Althele have no faction gate on the turn-ins themselves.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Say-chain wording.',
					'text' => 'Telin\'s keywords walk in order: hail, then "what are you doing" (or "demon"), "answers", "evil", "suspect", "Innoruuk", "action". The last line summons {{item:20472}}.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Giz fights back.',
					'text' => 'Handing {{npc:20058:kithicor:Giz X`Tin}} the ring spawns a hostile {{npc:20190:kithicor:Teir`Dal Reaver}} beside you. Be ready to fight as soon as the turn-in lands.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Worn Note',
					'text' => 'Talk to Telin through the keyword chain above to receive {{item:20472}}.',
				),
				array(
					'title' => 'Faelin\'s Ring',
					'text' => 'Find {{npc:54237:gfaydark:Faelin Bloodbriar}} in {{zone:gfaydark}} and hand her {{item:20472}} for {{item:20446}}. She leaves right after, and the visit window is only about 20 minutes once she is up.',
				),
				array(
					'title' => 'Giz X`Tin',
					'text' => 'Take {{item:20446}} to {{npc:20058:kithicor:Giz X`Tin}} in {{zone:kithicor}} for {{item:20447}}. Kill the reaver that spawns.',
				),
				array(
					'title' => 'Telin\'s coin',
					'text' => 'Return to {{npc:87082:burningwood:Telin Darkforest}} and give {{item:20447}} to receive {{item:20448}}.',
				),
				array(
					'title' => 'Althele',
					'text' => 'Give {{item:20448}} to {{npc:15044:eastkarana:Althele}} in {{zone:eastkarana}} for {{item:20450}}. This spawns {{npc:15178:eastkarana:Sionae}} nearby.',
				),
				array(
					'title' => 'Sionae, Nuien, Teloa',
					'text' => 'Give {{item:20450}} to Sionae for {{item:20451}} (this spawns {{npc:15167:eastkarana:Nuien}}). Give the amulet to Nuien for another {{item:20451}} (this spawns {{npc:15170:eastkarana:Teloa}}). Give it to Teloa and follow her to the gathering point.',
					'sub' => array(
						'Sionae and Nuien despawn after about an hour if left alone — do not stall between hand-offs.',
						'When Teloa reaches the gathering spot, {{npc:15153:eastkarana:a Dark Elf Corruptor}} and two Dark Elf Reavers ({{npc:15150:eastkarana:one}}, {{npc:15000:eastkarana:two}}) spawn nearby and attack.',
					),
				),
				array(
					'title' => 'Fleshbound Tome',
					'text' => 'Kill the corruptor (always drops) and loot {{item:20452}}. Give it to {{npc:15044:eastkarana:Althele}} for {{item:18959}} plus a small experience award.',
				),
			),
		),
		array(
			'title' => 'Softly Glowing Stone',
			'intro' => 'Take {{item:18959}} to {{npc:33077:misty:Ella Foodcrafter}} in {{zone:misty}} for {{item:17860}}. She walks a patrol route around the thicket. She asks for a mixture to be made inside that bowl and for an ancient pottery bowl.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'One bowl, two jobs.',
					'text' => 'Use {{item:17860}} as the combine container for {{item:20473}} first, remove the mixture, then keep the bowl for Jaeil in the Pulsing Green Stone section. Do not hand Jaeil the bowl while it still has the mixture inside.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Hardened Mixture',
					'text' => 'Forage {{item:20464}}, {{item:20465}}, {{item:20466}}, and {{item:20467}}. Combine all four inside {{item:17860}} (Pottery, no-fail) to make {{item:20473}}.',
					'sub' => array(
						'{{item:20464}} — {{zone:gfaydark}}. {{item:20465}} — {{zone:misty}}. {{item:20466}} — {{zone:innothule}}. {{item:20467}} — {{zone:everfrost}}.',
					),
				),
				array(
					'title' => 'Enchanted Clay',
					'text' => 'In {{zone:kaladimb}}, ask {{npc:67030:kaladimb:Kinlo Strongarm}} about "mud", then "do it for me". Give him {{item:5664}} to receive {{item:20455}}. Kinlo needs you at Indifferent or better with Merchants of Kaladim to talk, and Apprehensive or better to trade.',
					'sub' => array(
						'{{item:5664}} drops from the black reavers in {{zone:citymist}}.',
					),
				),
				array(
					'title' => 'Ancient Pattern — Crushed Pot',
					'text' => 'Speak to {{npc:96032:timorous:Alrik Farsight}} in {{zone:timorous}} (Keepers of the Art, Amiable or better). Ask about "ancient bowl", then say "take the artifact" to receive {{item:20457}}.',
				),
				array(
					'title' => 'Ancient Pattern — Felwithe errands',
					'text' => 'Give {{item:20457}} to {{npc:62018:felwitheb:Farios Elianos}} for {{item:20458}}. Take the list to {{npc:61034:felwithea:Merchant Nora}} for {{item:20459}}, then back to Farios for {{item:20474}}.',
				),
				array(
					'title' => 'Ancient Pattern — Receipt',
					'text' => 'Give {{item:20474}} to {{npc:96032:timorous:Alrik Farsight}} for {{item:18960}}.',
					'sub' => array(
						'Shortcut: {{npc:110066:iceclad:Sojan the Sleepless}} in {{zone:iceclad}} gives {{item:20474}} for {{item:25104}}. You still turn the receipt in to Alrik.',
					),
				),
				array(
					'title' => 'Rose of Firiona',
					'text' => 'Forage {{item:12774}} in {{zone:firiona}}. Give it to {{npc:3017:qrg:Merdan Fleetfoot}} in {{zone:qrg}} for {{item:20475}}.',
				),
				array(
					'title' => 'Silver Chained Locket',
					'text' => 'In {{zone:frontiermtns}}, give {{item:20475}} to {{npc:92084:frontiermtns:a human skeleton}} for {{item:20476}}.',
				),
				array(
					'title' => 'Platinum Speckled Powder',
					'text' => 'Give {{item:20476}} to {{npc:3018:qrg:Niera Farbreeze}} in {{zone:qrg}} for {{item:20456}}.',
				),
				array(
					'title' => 'Runecrested Bowl',
					'text' => 'Combine {{item:18960}}, {{item:20455}}, and {{item:20456}} in a kiln (Pottery, no-fail) to create {{item:20460}}.',
				),
				array(
					'title' => 'Ella\'s reward',
					'text' => 'Give {{npc:33077:misty:Ella Foodcrafter}} {{item:20473}} and {{item:20460}} together for {{item:20462}}.',
				),
			),
		),
		array(
			'title' => 'Pulsing Green Stone',
			'intro' => 'You need a half-stone from the undead iksar sorcerer in {{zone:karnor}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Remains versus the real Venril.',
					'text' => '{{npc:102099:karnor:Venril Sathir\'s remains}} step aside if {{npc:102010:karnor:Venril Sathir}} himself is standing. If the real one is alive you must deal with him (or wait for the remains to respawn) before using the globe.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Spirit timer.',
					'text' => '{{npc:102019:karnor:The Spirit of Venril Sathir}} fades after about 30 minutes. Carry {{item:15392}} before you summon him.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Firefly Globe',
					'text' => 'Give {{item:10295}} to {{npc:102099:karnor:Venril Sathir\'s remains}}. A spirit appears where the remains stood.',
				),
				array(
					'title' => 'Resurrection scroll',
					'text' => 'Hand {{item:15392}} to {{npc:102019:karnor:the Spirit of Venril Sathir}}. He revives hostile as {{npc:102021:karnor:#Venril Sathir}}.',
				),
				array(
					'title' => 'Kill and loot',
					'text' => 'Defeat the revived sorcerer and loot {{item:20461}} (always drops).',
				),
				array(
					'title' => 'Foloal Stormforest',
					'text' => 'In {{zone:firiona}}, give {{npc:84207:firiona:Foloal Stormforest}} {{item:20462}} and {{item:20461}} for {{item:20468}}.',
				),
			),
		),
		array(
			'title' => 'Ancient Longsword and the Hammer',
			'intro' => 'Telin turns the warm stone into your first blade. The hammer side-quest produces the mithril that Earthcaller needs later.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Hold the longsword.',
					'text' => '{{npc:67089:kaladimb:Usbak the Old}} only appears after {{npc:67030:kaladimb:Kinlo Strongarm}} shatters the hammer, and he leaves after about 30 minutes. Keep {{item:20477}} in your bags until the hammer chain is done and Usbak is standing in Kaladim.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Ancient Longsword',
					'text' => 'Give {{item:20468}} to {{npc:87082:burningwood:Telin Darkforest}} for {{item:20477}} and about one million experience.',
				),
				array(
					'title' => 'Jaeil the Wretched',
					'text' => 'In {{zone:hole}}, give the clean {{item:17860}} to {{npc:39041:hole:Jaeil the Wretched}}. He turns on you as {{npc:39154:hole:Jaeil the Insane}}. Kill him and loot {{item:20478}} (always drops).',
				),
				array(
					'title' => 'Mairee Silentone',
					'text' => 'Give {{item:20478}} to {{npc:23019:erudnint:Mairee Silentone}} in {{zone:erudnint}} for {{item:20479}}.',
					'sub' => array(
						'Optional flavor: {{npc:67030:kaladimb:Kinlo Strongarm}} will inspect {{item:20478}} and hand it right back. Nothing changes, so you can skip him.',
					),
				),
				array(
					'title' => 'Swirling Sphere of Color',
					'text' => 'In {{zone:airplane}}, kill {{npc:71017:airplane:a soul carrier}}. It spawns two {{npc:71070:airplane:essence carriers}}, each of which spawns an {{npc:71071:airplane:essence tamer}}. Loot {{item:20494}} (always drops).',
				),
				array(
					'title' => 'Hammer of the Ancients',
					'text' => 'Give {{npc:23019:erudnint:Mairee Silentone}} {{item:20479}} and {{item:20494}} to receive {{item:20485}}.',
				),
				array(
					'title' => 'Mithril ore and Usbak',
					'text' => 'Give {{item:20485}} to {{npc:67030:kaladimb:Kinlo Strongarm}} for {{item:20482}}. The turn-in spawns {{npc:67089:kaladimb:Usbak the Old}} (about 30 minutes before he leaves).',
				),
				array(
					'title' => 'Refined Ancient Sword',
					'text' => 'Give {{item:20477}} to Usbak for {{item:20481}}.',
				),
				array(
					'title' => 'Refined Mithril Blade',
					'text' => 'Give {{item:20482}} to Kinlo for {{item:20483}}.',
				),
			),
		),
		array(
			'title' => 'Swiftwind',
			'steps' => array(
				array(
					'title' => 'Telin',
					'text' => 'Give {{item:20481}} to {{npc:87082:burningwood:Telin Darkforest}}. He returns the sword and spawns {{npc:87019:burningwood:Faelin Bloodbriar}}.',
				),
				array(
					'title' => 'Faelin\'s enchantment',
					'text' => 'Give {{npc:87019:burningwood:Faelin Bloodbriar}} {{item:20481}} to receive {{item:20487}} and about one million experience. She leaves about 30 minutes after spawning.',
				),
			),
		),
		array(
			'title' => 'Earthcaller',
			'steps' => array(
				array(
					'title' => 'Shattered Emerald of Corruption',
					'text' => 'Defeat {{npc:76003:hateplane:#Innoruuk}} in {{zone:hateplane}} for a 50% chance at {{item:20484}}. Lesser Hate named have a small chance as well.',
				),
				array(
					'title' => 'Xanuusus',
					'text' => 'Give {{npc:13061:northkarana:Xanuusus}} {{item:20483}} and {{item:20484}} together for {{item:20488}} plus about 2.5 million experience.',
				),
			),
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Not the druid hand-in.',
					'text' => 'Xanuusus also accepts the druid epic items. The ranger submission is just the blade and the emerald.',
				),
			),
		),
	),
	'verified' => array(
		'burningwood/Telin_Darkforest.lua',
		'gfaydark/Faelin_Bloodbriar.lua',
		'burningwood/Faelin_Bloodbriar.lua',
		'kithicor/Giz_X-Tin.lua',
		'eastkarana/Althele.lua',
		'eastkarana/Sionae.lua',
		'eastkarana/Nuien.lua',
		'eastkarana/Teloa.lua',
		'misty/Ella_Foodcrafter.lua',
		'timorous/Alrik_Farsight.lua',
		'felwitheb/Farios_Elianos.lua',
		'felwithea/Merchant_Nora.lua',
		'iceclad/Sojan_the_Sleepless.lua',
		'kaladimb/Kinlo_Strongarm.lua',
		'kaladimb/Usbak_the_Old.lua',
		'qrg/Merdan_Fleetfoot.lua',
		'frontiermtns/a_human_skeleton.lua',
		'qrg/Niera_Farbreeze.lua',
		'hole/Jaeil_the_Wretched.lua',
		'erudnint/Mairee_Silentone.lua',
		'airplane/a_soul_carrier.lua',
		'airplane/an_essence_carrier.lua',
		'karnor/Venril_Sathirs_remains.lua',
		'karnor/Spirit_of_Venril_Sathir.lua',
		'karnor/Venril_Sathir.lua',
		'firiona/Foloal_Stormforest.lua',
		'northkarana/Xanuusus.lua',
	),
);

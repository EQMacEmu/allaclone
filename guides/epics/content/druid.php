<?php
/**
 * Druid Epic 1.0 — Nature Walker's Scimitar
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   burningwood/Telin_Darkforest.lua    say chain -> 20472 Worn Note ("Start of the ranger and druid epic"); 20447 -> 20448
 *   gfaydark/Faelin_Bloodbriar.lua      20472 -> 20446
 *   kithicor/Giz_X-Tin.lua              20446 -> 20447 + spawn Teir`Dal Reaver 20190 (hostile)
 *   eastkarana/Althele.lua              20448 -> 20450 + spawn Sionae 15178; 20452 -> 18959 (+100k xp)
 *   eastkarana/Sionae.lua               20450 -> 20451 + spawn Nuien 15167
 *   eastkarana/Nuien.lua                20451 -> 20451 + spawn Teloa 15170
 *   eastkarana/Teloa.lua                walk event; corruptor 15153 + reavers 15150 / 15000; depops Sionae / Nuien / Teloa
 *   (Dark_Elf_Corruptor 15153 loot)     20452 Fleshbound Tome 100%
 *   misty/Ella_Foodcrafter.lua          18959 -> 17860; 20473+20460 -> 20462; 20468 -> 20440 (druid branch)
 *   timorous/Alrik_Farsight.lua         say "ancient bowl" / "take the artifact" -> 20457; 20474 -> 18960
 *   felwitheb/Farios_Elianos.lua        20457 -> 20458; 20459 -> 20474
 *   felwithea/Merchant_Nora.lua         20458 -> 20459
 *   iceclad/Sojan_the_Sleepless.lua     25104 -> 20474 (alternate Receipt)
 *   kaladimb/Kinlo_Strongarm.lua        5664 -> 20455 (faction < 7)
 *   qrg/Merdan_Fleetfoot.lua            12774 -> 20475
 *   frontiermtns/a_human_skeleton.lua   20475 -> 20476
 *   qrg/Niera_Farbreeze.lua             20476 -> 20456
 *   karnor/Venril_Sathirs_remains.lua   10295 -> spawn Spirit 102019; depops if Venril Sathir 102010 is up
 *   karnor/Spirit_of_Venril_Sathir.lua  15392 -> spawn #Venril Sathir 102021 (drops 20461 100%); 30 min depop
 *   karnor/Venril_Sathir.lua            remains depop if real Venril Sathir is spawned
 *   firiona/Foloal_Stormforest.lua      20462+20461 -> 20468
 *   -- Faydwer spirit --
 *   lfaydark/tainted_brownie.lua        death -> corrupted brownie 57117 (drops 20693 100%)
 *   kedge/tainted_seahorse.lua          death -> corrupted seahorse 64050 (drops 20691 100%)
 *   oot/tainted_seafury_cyclops.lua     death -> corrupted seafury cyclops 69142 (drops 20692 100%)
 *   lfaydark/pained_unicorn.lua         20691+20692+20693 -> 20694 (+1000 xp)
 *   akanon/Silox_Azrix.lua              20694 -> 20697 (priest depops)
 *   -- Antonica spirit --
 *   rathemtn/tainted_hill_giant.lua     death -> corrupted hill giant 50322 (drops 20688 100%)
 *   rathemtn/corrupted_hill_giant.lua   10 min depop unless engaged
 *   lakerathe/#tainted_aquagoblin.lua   death -> unique corrupted shaman 51002 (drops 20689 100%)
 *   everfrost/tainted_wooly_mammoth.lua death -> corrupted wooly mammoth 30090 (drops 20690 100%)
 *   northkarana/withered_treant.lua     20688+20689+20690 -> 20695 (treant depops)
 *   southkarana/Yeka_Ias.lua            20695 -> 20698 (+2000 xp; priest depops)
 *   -- Kunark spirit --
 *   (swampofnohope Ulump_Pujluk 83030)  drops 20696 100%; 12 hour respawn
 *   timorous/Dolgin_Codslayer.lua       20696 (Druid only) -> 20696 returned + spawn #Faydedar 96073 hostile
 *   timorous/#Faydedar.lua              spawn -> depops Faydedar 96089; loot 20687 100%
 *   timorous/Faydedar.lua               depops itself if #Faydedar is up; loot 20687 100%
 *   emeraldjungle/#tainted_gorilla.lua  death -> corrupted gorilla 94008 (drops 20686 100%)
 *   lakeofillomen/#tainted_barracuda.lua death -> corrupted barracuda 85224 (drops 20685 100%)
 *   overthere/Nekexin_Virulence.lua     20696+20686+20685+20687 -> 20699 (priest depops)
 *   northkarana/Xanuusus.lua            20699+20697+20698+20440 -> 20490 (+2.5M xp)
 * Combines (tradeskill_recipe, Pottery, no-fail):
 *   container Shiny Tin Bowl 17860: 20464+20465+20466+20467 -> 20473
 *   container obj type 21: 18960+20455+20456 -> 20460
 * Foraging (forage table): 20464 gfaydark, 20465 misty, 20466 innothule, 20467 everfrost, 12774 firiona
 * Epic reward item id: 20490 (peq name: Nature Walker's Scimitar)
 *
 * Faction note (Lua): Telin needs a Faydarks Champions standing better than the
 * GetFaction < 5 gate; Alrik / Farios need Keepers of the Art Amiable or better;
 * Kinlo needs GetFaction < 7 to trade. Ella, Foloal, Xanuusus, the three plague
 * priests, and both nature spirits have no faction checks on the epic turn-ins.
 */

return array(
	'toc' => array(
		'Opening — Faelin, Giz, and Althele',
		'Softly Glowing Stone',
		'Pulsing Green Stone',
		'Elaborate Scimitar',
		'Spirit of Faydwer',
		'Spirit of Antonica',
		'Spirit of Kunark',
		'Final turn-in',
	),
	'prefarm' => array(
		array(
			'html' => 'Four Hardened Mixture ingredients (forage, ~10% each): {{item:20464}} in {{zone:gfaydark}}, {{item:20465}} in {{zone:misty}}, {{item:20466}} in {{zone:innothule}}, {{item:20467}} in {{zone:everfrost}}',
		),
		array(
			'item' => 5664,
			'note' => 'Needed by {{npc:67030:kaladimb:Kinlo Strongarm}} for the clay — drops from {{npc:90005:citymist:a black reaver}} in {{zone:citymist}}',
		),
		array(
			'item' => 12774,
			'note' => 'Forage in {{zone:firiona}} (about 4%)',
		),
		array(
			'item' => 10295,
			'note' => 'Cast {{item:15237:Dance of the Fireflies}} (Druid / Ranger spell) to create the globe for Venril Sathir\'s remains',
		),
		array(
			'item' => 15392,
			'note' => 'Resurrection scroll sold by city casters (e.g. {{npc:61022:felwithea:Assa Leafwind}}); handed to Venril Sathir\'s spirit',
		),
		array(
			'item' => 25104,
			'note' => 'Optional: Coldain Toolset skips the Felwithe errand chain via {{npc:110066:iceclad:Sojan the Sleepless}}',
		),
		array(
			'item' => 20696,
			'note' => 'Drops from {{npc:83030:swampofnohope:Ulump Pujluk}} (always; about 12 hour respawn)',
		),
		array(
			'html' => 'Nine corruption foci — the quest chain only starts the corrupted mobs when you kill the matching tainted mob, so start those hunts early. See the three spirit sections.',
		),
	),
	'sections' => array(
		array(
			'title' => 'Opening — Faelin, Giz, and Althele',
			'intro' => 'The druid and ranger epics share their first half. Begin with {{npc:87082:burningwood:Telin Darkforest}} in {{zone:burningwood}}; his Lua gates the whole dialogue behind your standing with the Faydarks Champions.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Say-chain wording.',
					'text' => 'Telin\'s keywords go in order: hail, then "what are you doing" (or "demon"), "answers", "evil", "suspect", "Innoruuk", and finally "action". The last line puts {{item:20472}} on your cursor.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Giz fights back.',
					'text' => 'Handing {{npc:20058:kithicor:Giz X`Tin}} the ring spawns a hostile {{npc:20190:kithicor:Teir`Dal Reaver}} right next to you. Be ready to fight the moment the trade lands.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Worn Note',
					'text' => 'Work through Telin\'s keyword chain above to receive {{item:20472}}.',
				),
				array(
					'title' => 'Faelin\'s Ring',
					'text' => 'Find {{npc:54237:gfaydark:Faelin Bloodbriar}} in {{zone:gfaydark}} and give her {{item:20472}} for {{item:20446}}. She wanders off almost at once, so line up the walk before you start.',
				),
				array(
					'title' => 'Giz X`Tin',
					'text' => 'Bring {{item:20446}} to {{npc:20058:kithicor:Giz X`Tin}} in {{zone:kithicor}} and receive {{item:20447}}. Kill the reaver that appears.',
				),
				array(
					'title' => 'Telin\'s coin',
					'text' => 'Go back to {{npc:87082:burningwood:Telin Darkforest}} and hand over {{item:20447}}. He gives you {{item:20448}}.',
				),
				array(
					'title' => 'Althele',
					'text' => 'Give {{item:20448}} to {{npc:15044:eastkarana:Althele}} in {{zone:eastkarana}} for {{item:20450}}. {{npc:15178:eastkarana:Sionae}} spawns nearby.',
				),
				array(
					'title' => 'Sionae, Nuien, Teloa',
					'text' => 'Give {{item:20450}} to Sionae for {{item:20451}} (that spawns {{npc:15167:eastkarana:Nuien}}). Hand the amulet to Nuien for another {{item:20451}} (that spawns {{npc:15170:eastkarana:Teloa}}). Give it to Teloa and follow her to the stopping point.',
					'sub' => array(
						'Sionae and Nuien are on despawn timers if left idle, so move along between hand-offs.',
						'When Teloa arrives, {{npc:15153:eastkarana:a Dark Elf Corruptor}} and two reavers ({{npc:15150:eastkarana:one}}, {{npc:15000:eastkarana:two}}) show up and attack.',
					),
				),
				array(
					'title' => 'Fleshbound Tome',
					'text' => 'Kill the corruptor (always drops) and loot {{item:20452}}. Give it to {{npc:15044:eastkarana:Althele}} for {{item:18959}} and a small experience reward.',
				),
			),
		),
		array(
			'title' => 'Softly Glowing Stone',
			'intro' => 'Take {{item:18959}} to {{npc:33077:misty:Ella Foodcrafter}} in {{zone:misty}} for {{item:17860}}. She patrols the thicket and wants a mixture made inside that bowl plus an ancient pottery bowl.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Bowl reuse.',
					'text' => 'The mixture is combined inside {{item:17860}}; remove the finished {{item:20473}} before you move on. Unlike the ranger line, the druid line does not send the bowl to Jaeil, so you may sell or shelve it later.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Hardened Mixture',
					'text' => 'Forage {{item:20464}}, {{item:20465}}, {{item:20466}}, and {{item:20467}}. Combine all four in {{item:17860}} (Pottery, no-fail) to make {{item:20473}}.',
					'sub' => array(
						'{{item:20464}} — {{zone:gfaydark}}. {{item:20465}} — {{zone:misty}}. {{item:20466}} — {{zone:innothule}}. {{item:20467}} — {{zone:everfrost}}.',
					),
				),
				array(
					'title' => 'Enchanted Clay',
					'text' => 'In {{zone:kaladimb}}, ask {{npc:67030:kaladimb:Kinlo Strongarm}} about "mud", then "do it for me". Give him {{item:5664}} for {{item:20455}}. Your Kaladim merchant faction has to be good enough for him to trade.',
					'sub' => array(
						'{{item:5664}} drops from the black reavers in {{zone:citymist}}.',
					),
				),
				array(
					'title' => 'Ancient Pattern — Crushed Pot',
					'text' => 'Speak with {{npc:96032:timorous:Alrik Farsight}} in {{zone:timorous}} (Keepers of the Art, Amiable or better). Ask about "ancient bowl", then say "take the artifact" to receive {{item:20457}}.',
				),
				array(
					'title' => 'Ancient Pattern — Felwithe errands',
					'text' => 'Give {{item:20457}} to {{npc:62018:felwitheb:Farios Elianos}} for {{item:20458}}. Carry the list to {{npc:61034:felwithea:Merchant Nora}} for {{item:20459}}, then return to Farios for {{item:20474}}.',
				),
				array(
					'title' => 'Ancient Pattern — Receipt',
					'text' => 'Give {{item:20474}} to {{npc:96032:timorous:Alrik Farsight}} for {{item:18960}}.',
					'sub' => array(
						'Shortcut: {{npc:110066:iceclad:Sojan the Sleepless}} in {{zone:iceclad}} swaps {{item:25104}} for {{item:20474}}. Alrik still gets the receipt.',
					),
				),
				array(
					'title' => 'Rose of Firiona',
					'text' => 'Forage {{item:12774}} in {{zone:firiona}} and give it to {{npc:3017:qrg:Merdan Fleetfoot}} in {{zone:qrg}} for {{item:20475}}.',
				),
				array(
					'title' => 'Silver Chained Locket',
					'text' => 'In {{zone:frontiermtns}}, give {{item:20475}} to {{npc:92084:frontiermtns:a human skeleton}} for {{item:20476}}.',
				),
				array(
					'title' => 'Platinum Speckled Powder',
					'text' => 'Take {{item:20476}} to {{npc:3018:qrg:Niera Farbreeze}} in {{zone:qrg}} for {{item:20456}}.',
				),
				array(
					'title' => 'Runecrested Bowl',
					'text' => 'Combine {{item:18960}}, {{item:20455}}, and {{item:20456}} in a kiln (Pottery, no-fail) to create {{item:20460}}.',
				),
				array(
					'title' => 'Ella\'s reward',
					'text' => 'Give {{npc:33077:misty:Ella Foodcrafter}} {{item:20473}} and {{item:20460}} together to receive {{item:20462}}.',
				),
			),
		),
		array(
			'title' => 'Pulsing Green Stone',
			'intro' => 'The second half-stone comes from the iksar sorcerer in {{zone:karnor}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Remains versus the real Venril.',
					'text' => '{{npc:102099:karnor:Venril Sathir\'s remains}} step aside whenever {{npc:102010:karnor:Venril Sathir}} himself is standing. If the real one is up, deal with him or wait for the remains to respawn before using the globe.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Spirit timer.',
					'text' => '{{npc:102019:karnor:The Spirit of Venril Sathir}} fades after about 30 minutes. Have {{item:15392}} in hand before you summon him.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Firefly Globe',
					'text' => 'Give {{item:10295}} to {{npc:102099:karnor:Venril Sathir\'s remains}}. A spirit takes shape where the remains lay.',
				),
				array(
					'title' => 'Resurrection scroll',
					'text' => 'Hand {{item:15392}} to {{npc:102019:karnor:the Spirit of Venril Sathir}}. He revives as the hostile {{npc:102021:karnor:#Venril Sathir}}.',
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
			'title' => 'Elaborate Scimitar',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Do not hand the stone to Telin.',
					'text' => '{{npc:87082:burningwood:Telin Darkforest}} accepts {{item:20468}} for the ranger sword and does not check your class. A druid who turns it in there loses it. The druid branch is {{npc:33077:misty:Ella Foodcrafter}}.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Ella and the scimitar',
					'text' => 'Return to {{npc:33077:misty:Ella Foodcrafter}} in {{zone:misty}} and give her {{item:20468}}. She hands over {{item:20440}}, the blade that the three cleansed spirits will be fused into. She also explains that the lord of disease is working beside Innoruuk and that Faydwer, Antonica, and Kunark each need to be healed.',
				),
			),
		),
		array(
			'title' => 'Spirit of Faydwer',
			'intro' => 'You need one focus from each of three corrupted creatures, then a unicorn and a gnome priest. Each corrupted creature only appears when its tainted version dies.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Tainted first, corrupted second.',
					'text' => 'Killing the tainted mob spawns the corrupted one on the same spot, and the corrupted one always drops its focus. The tainted creatures are low-percentage spawns, so expect to wait.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Green Heartwood Branch',
					'text' => 'In {{zone:lfaydark}}, kill {{npc:57096:lfaydark:a tainted brownie}}. {{npc:57117:lfaydark:A corrupted brownie}} spawns where it fell; loot {{item:20693}}.',
				),
				array(
					'title' => 'Kedge Cave Crystals',
					'text' => 'In {{zone:kedge}}, kill {{npc:64048:kedge:a tainted seahorse}} in the flooded halls. {{npc:64050:kedge:A corrupted seahorse}} spawns; loot {{item:20691}}.',
				),
				array(
					'title' => 'Ocean of Tears Seavines',
					'text' => 'In {{zone:oot}}, kill {{npc:69096:oot:a tainted seafury cyclops}}. {{npc:69142:oot:A corrupted seafury cyclops}} spawns; loot {{item:20692}}.',
				),
				array(
					'title' => 'Pained unicorn',
					'text' => 'Back in {{zone:lfaydark}}, give {{npc:57035:lfaydark:the pained unicorn}} {{item:20691}}, {{item:20692}}, and {{item:20693}} together. It gives you {{item:20694}} and a little experience.',
				),
				array(
					'title' => 'Silox Azrix',
					'text' => 'Find {{npc:55006:akanon:Silox Azrix}} in {{zone:akanon}} and hand him {{item:20694}}. He burns away and you pick up {{item:20697}}. He respawns later for other players.',
				),
			),
		),
		array(
			'title' => 'Spirit of Antonica',
			'intro' => 'The Antonican foci come from corrupted creatures in the Rathe Mountains, Lake Rathetear, and Everfrost. A withered treant collects them; a priest in the Karanas finishes the job.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Hill giant timer.',
					'text' => '{{npc:50322:rathemtn:The corrupted hill giant}} despawns after ten minutes unless you are fighting it, so be ready to engage right away.',
				),
				array(
					'kind' => 'note',
					'title' => 'Placeholder name.',
					'text' => 'At Lake Rathetear the trigger is the pound-sign version, {{npc:51151:lakerathe:#tainted aquagoblin}}; the plain tainted aquagoblins in the same camp do nothing.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Ancient Rock',
					'text' => 'In {{zone:rathemtn}}, kill {{npc:50304:rathemtn:a tainted hill giant}}. {{npc:50322:rathemtn:A corrupted hill giant}} spawns; loot {{item:20688}}.',
				),
				array(
					'title' => 'Clean Lakewater',
					'text' => 'In {{zone:lakerathe}}, kill the {{npc:51151:lakerathe:#tainted aquagoblin}}. A unique {{npc:51002:lakerathe:corrupted shaman}} spawns; loot {{item:20689}}.',
				),
				array(
					'title' => 'Chunk of Tundra',
					'text' => 'In {{zone:everfrost}}, kill {{npc:30002:everfrost:a tainted wooly mammoth}}. {{npc:30090:everfrost:A corrupted wooly mammoth}} spawns; loot {{item:20690}}.',
				),
				array(
					'title' => 'Withered treant',
					'text' => 'In {{zone:northkarana}}, give {{npc:13274:northkarana:the withered treant}} {{item:20688}}, {{item:20689}}, and {{item:20690}} together. The treant collapses and leaves {{item:20695}}.',
				),
				array(
					'title' => 'Yeka Ias',
					'text' => 'Search {{zone:southkarana}} for the plague priest {{npc:14078:southkarana:Yeka Ias}} and give him {{item:20695}}. He is destroyed and you collect {{item:20698}}.',
				),
			),
		),
		array(
			'title' => 'Spirit of Kunark',
			'intro' => 'Kunark asks for four ingredients, including a bait trick at Timorous Deep that only a Druid can start.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'The essence comes back.',
					'text' => '{{npc:96018:timorous:Dolgin Codslayer}} takes {{item:20696}} and gives one right back. Keep it; you still need it for the priest.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Faydedar fights you.',
					'text' => 'The turn-in spawns {{npc:96073:timorous:#Faydedar}} already angry at you, and Dolgin leaves. Be at full strength first.',
				),
				array(
					'kind' => 'note',
					'title' => 'Two Faydedars.',
					'text' => 'The quest version and the roaming {{npc:96089:timorous:Faydedar}} both always drop {{item:20687}}, and whichever is up removes the other. If the roamer is standing you can skip Dolgin entirely.',
				),
				array(
					'kind' => 'note',
					'title' => 'Placeholder names.',
					'text' => 'Emerald Jungle and Lake of Ill Omen use the pound-sign tainted mobs ({{npc:94007:emeraldjungle:#tainted gorilla}}, {{npc:85217:lakeofillomen:#tainted barracuda}}); the plain tainted versions in those spawn groups do nothing.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Froglok Essence',
					'text' => 'Kill {{npc:83030:swampofnohope:Ulump Pujluk}} in {{zone:swampofnohope}} and loot {{item:20696}} (always drops). Talking to him about "pain" and "corruption" explains what he wants done.',
				),
				array(
					'title' => 'Pod of Seawater',
					'text' => 'In {{zone:timorous}}, speak with {{npc:96018:timorous:Dolgin Codslayer}}: hail, then "beast", "faydedar", and "risk it". Give him {{item:20696}} and keep the one he returns. Kill {{npc:96073:timorous:#Faydedar}} and loot {{item:20687}}.',
				),
				array(
					'title' => 'Green Tree Bark',
					'text' => 'In {{zone:emeraldjungle}}, kill {{npc:94007:emeraldjungle:#tainted gorilla}}. {{npc:94008:emeraldjungle:A corrupted gorilla}} spawns; loot {{item:20686}}.',
				),
				array(
					'title' => 'Pure Lakewater',
					'text' => 'In {{zone:lakeofillomen}}, kill {{npc:85217:lakeofillomen:#tainted barracuda}}. {{npc:85224:lakeofillomen:A corrupted barracuda}} spawns; loot {{item:20685}}.',
				),
				array(
					'title' => 'Nekexin Virulence',
					'text' => 'Find {{npc:93043:overthere:Nekexin Virulence}} by the water in {{zone:overthere}} and give him {{item:20696}}, {{item:20686}}, {{item:20685}}, and {{item:20687}} together. He dissolves and leaves {{item:20699}}.',
				),
			),
		),
		array(
			'title' => 'Final turn-in',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Not the ranger hand-in.',
					'text' => '{{npc:13061:northkarana:Xanuusus}} also takes the ranger sword and emerald. The druid hand-in is the scimitar plus the three spirits.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Nature Walker\'s Scimitar',
					'text' => 'Give {{npc:13061:northkarana:Xanuusus}} {{item:20699}}, {{item:20697}}, {{item:20698}}, and {{item:20440}} in a single trade to receive {{item:20490}} and about 2.5 million experience.',
				),
			),
		),
	),
	'verified' => array(
		'burningwood/Telin_Darkforest.lua',
		'gfaydark/Faelin_Bloodbriar.lua',
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
		'qrg/Merdan_Fleetfoot.lua',
		'frontiermtns/a_human_skeleton.lua',
		'qrg/Niera_Farbreeze.lua',
		'karnor/Venril_Sathirs_remains.lua',
		'karnor/Spirit_of_Venril_Sathir.lua',
		'karnor/Venril_Sathir.lua',
		'firiona/Foloal_Stormforest.lua',
		'lfaydark/tainted_brownie.lua',
		'kedge/tainted_seahorse.lua',
		'oot/tainted_seafury_cyclops.lua',
		'lfaydark/pained_unicorn.lua',
		'akanon/Silox_Azrix.lua',
		'rathemtn/tainted_hill_giant.lua',
		'rathemtn/corrupted_hill_giant.lua',
		'lakerathe/#tainted_aquagoblin.lua',
		'everfrost/tainted_wooly_mammoth.lua',
		'northkarana/withered_treant.lua',
		'southkarana/Yeka_Ias.lua',
		'swampofnohope/Ulump_Pujluk.lua',
		'timorous/Dolgin_Codslayer.lua',
		'timorous/#Faydedar.lua',
		'timorous/Faydedar.lua',
		'emeraldjungle/#tainted_gorilla.lua',
		'lakeofillomen/#tainted_barracuda.lua',
		'overthere/Nekexin_Virulence.lua',
		'northkarana/Xanuusus.lua',
	),
);

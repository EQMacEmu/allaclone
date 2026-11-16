<?php
/**
 * Shaman Epic 1.0 — Spear of Fate
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   rathemtn/Blinde_the_Cutpurse.lua       death -> lesser spirit 50306 (Kunark gate)
 *   oot/Capt_Surestout.lua                 death -> lesser spirit 69149 (Kunark gate)
 *   butcher/Peg_Leg.lua                    death -> lesser spirit 68251 (Kunark gate)
 *   fieldofbone/an_Iksar_manslayer.lua     death -> lesser spirit 78211
 *   rathemtn|oot|fieldofbone|butcher/a_lesser_spirit.lua   dialogue -> 1665; 10m depop
 *   freportn/Bondl_Felligan.lua            1665 -> faction; "you can buy booze" -> spirit 8117
 *   freportn/a_greater_spirit.lua          "heyokah" -> 1666; "i follow fear/war/hate" -> spirit 8118
 *   freportn/a_greater_spirit_.lua         same gem dialogue as 8117
 *   erudsxing/Ooglyn.lua                   1666 -> swim escort -> Srafen 98046
 *   erudsxing/Srafen_the_Soaked.lua, Dillon_the_Drowned.lua, Froham_the_Forgotten.lua,
 *     Abe_the_Abandoned.lua (1680 arrow, no turn-in) -> greater spirit 98052
 *   erudsxing/a_greater_spirit.lua         "next path" -> 1667
 *   qey2hh1/a_wandering_spirit.lua         1667 (fac>=100); 1675+1676+1677 (fac>=200); say shield/last path -> 1679 / 1668
 *   rathemtn/Glaron_the_Wicked.lua         1675 -> 1669 (fail pebble); drops 1676+1677
 *   rathemtn/Tabien_the_Goodly.lua         1676+1677 -> 1670 (fail pebble); drops 1675
 *   emeraldjungle/Spirit_Sentinel.lua      1668 (fac>=300); 1672 (fac>=400) -> 1678; 3599 (fac>=500); say how it all began -> 17805
 *   mistmoore/an_advisor.lua               death -> Black Dire 59129; Black_Dire.lua 35m depop
 *   emeraldjungle/Spirit_Sentinel_.lua     18456+18457+18458 (fac>=600); 18459 (fac>=900); 1674 (fac>=1900) -> 10651
 *   citymist/90007|90008|90009.lua         reaver death 20% -> Ghiosk 90010 / Neh 90011 / Rak 90013
 *   hole/#High_Scale_Kirn_.lua             1671 (fac>=700) -> hostile Kirn 39083 (drops 1681)
 *   citymist/#Neh-Ashiir.lua               1681 (fac>=800) -> hostile Neh 90012 (drops 18459)
 *   citymist/#Lord_Rak-Ashiir.lua          1673 (fac>=1800) -> hostile Rak 90014 (drops 1674)
 *   fearplane/Dread|Fright|Terror.lua      death 76% -> Iksar broodling 72105 (drops 1673)
 * Combine (tradeskill_recipe 9727): 18450..18455 in 17805 -> 3599 (no-fail)
 * Ground spawn: 1671 in citymist (DB x -370, y 132, z -14; 5 min respawn)
 * Epic reward item id: 10651
 */

return array(
	'toc' => array(
		'Trust — Heyokah gem',
		'Patience — Erud\'s Crossing',
		'Wisdom — Shield of Falsehood',
		'Might — Spirit Sentinel',
		'Torsis and the final turn-in',
	),
	'prefarm' => array(
		array(
			'item' => 1672,
			'note' => 'From {{npc:59129:mistmoore:Black Dire}}; he spawns when {{npc:59055:mistmoore:an advisor}} dies (and on his own timer)',
		),
		array(
			'item' => 1675,
			'note' => 'From {{npc:50276:rathemtn:Tabien the Goodly}} (long respawn)',
		),
		array(
			'html' => '{{item:1676}} and {{item:1677}} from {{npc:50053:rathemtn:Glaron the Wicked}} (long respawn)',
		),
		array(
			'html' => 'Six journal pages for the combine: {{item:18450}}, {{item:18451}}, {{item:18452}}, {{item:18453}}, {{item:18454}}, {{item:18455}} — all Torsis ({{zone:citymist}})',
		),
		array(
			'html' => 'Torsis logs: {{item:18456}}, {{item:18457}}, {{item:18458}} from {{npc:90010:citymist:Lord Ghiosk}} (black reaver PH)',
		),
		array(
			'item' => 1673,
			'note' => 'From {{npc:72105:fearplane:an Iksar broodling}} after Dread, Fright, or Terror dies',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Trust: the Heyokah gem',
			'intro' => 'Start by killing a mob that spawns a lesser spirit, win a gem from it, and carry the gem to a drunk in North Freeport who eventually leads you to a greater spirit.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Faction.',
					'text' => 'Every True Spirit turn-in below nudges {{faction:404:True Spirit}} upward. Later NPCs refuse to talk or accept items until you have enough, so do not skip steps.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Short timers.',
					'text' => 'Lesser spirits depop after about 10 minutes; each greater spirit in Freeport lasts about 30 minutes; the Erud\'s Crossing spirit only 10. Be ready to talk the moment they appear.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Spawn a lesser spirit',
					'text' => 'Kill any one of these and the spirit appears on the corpse. The Kunark-gated ones only spawn while Kunark content is flagged on, which is the case on TAKP.',
					'sub' => array(
						'{{npc:50058:rathemtn:Blinde the Cutpurse}} (short respawn) spawns {{npc:50306:rathemtn:a lesser spirit}}.',
						'{{npc:69130:oot:Capt. Surestout}} spawns {{npc:69149:oot:a lesser spirit}}.',
						'{{npc:68201:butcher:Peg Leg}} spawns {{npc:68251:butcher:a lesser spirit}}.',
						'Any {{npc:78066:fieldofbone:Iksar manslayer}} spawns {{npc:78211:fieldofbone:a lesser spirit}} every time it dies. Marauders do nothing — only manslayers.',
					),
				),
				array(
					'title' => 'Tiny Gem',
					'text' => 'Hail the spirit and answer with yes, then "threatened my life", then "what are you" (or "who are you"), then "know more". It summons {{item:1665}} onto your cursor.',
				),
				array(
					'title' => 'Bondl Felligan',
					'text' => 'Give {{item:1665}} to {{npc:8003:freportn:Bondl Felligan}}. He sets off toward the taverns planning to sell it for booze, and a persistent flag is stored on your character.',
					'sub' => array(
						'When he asks whether you will let him buy booze, say "you can buy booze". He sobers up, asks you to follow, and spawns {{npc:8117:freportn:a greater spirit}}.',
					),
				),
				array(
					'title' => 'Greater spirit',
					'text' => 'Hail the spirit and pick a path. Justice or Honor keeps you talking to this spirit. War, Fear, or Hate sends you to the next room, where {{npc:8118:freportn:a greater spirit}} spawns and repeats the same lecture.',
					'sub' => array(
						'Walk through its lines in order: trust, true spirits, very few, wisps of consciousness, then "heyokah". The word "heyokah" is what hands you {{item:1666}} and clears the flag on your character.',
						'Finish with "i will walk the path" to end the conversation. The line says "take this gem", but the item already comes from "heyokah".',
					),
				),
			),
		),
		array(
			'title' => 'Part 2 — Patience: Erud\'s Crossing',
			'steps' => array(
				array(
					'title' => 'Ooglyn',
					'text' => 'Travel to {{zone:erudsxing}} and find {{npc:98009:erudsxing:Ooglyn}} on the island. Hail her, then give {{item:1666}}. She walks you into the water and parks you at a waiting spot on the sea floor.',
					'sub' => array(
						'Bring water breathing or an equivalent; the wait is long and the water is deep.',
					),
				),
				array(
					'title' => 'The long wait',
					'text' => 'About a minute after you arrive, {{npc:98046:erudsxing:Srafen the Soaked}} spawns and Ooglyn leaves. A set of other waiting spirits cycle through on timers: Srafen, then {{npc:98047:erudsxing:Froham the Forgotten}}, then {{npc:98050:erudsxing:Abe the Abandoned}}. Plan on roughly half an hour before the finale.',
					'sub' => array(
						'Srafen brings in {{npc:98051:erudsxing:Dillon the Drowned}} after a few minutes. All of them have optional lore dialogue and none of it is required for progress.',
						'{{npc:98050:erudsxing:Abe the Abandoned}} will hand you {{item:1680}} if you say "give them the arrow". The arrow has no later use in the scripts — keep it as a souvenir or skip it.',
					),
				),
				array(
					'title' => 'Greater spirit at Erud',
					'text' => 'After Abe wanders off, {{npc:98052:erudsxing:a greater spirit}} appears. Hail it, say "next path", and it gives {{item:1667}}. It disappears after ten minutes, so do not step away.',
				),
			),
		),
		array(
			'title' => 'Part 3 — Wisdom: Shield of Falsehood',
			'intro' => 'Take {{item:1667}} to a roaming spirit in {{zone:qey2hh1}}. He tells you to head for Rathe, where two quest-givers each ask you to murder the other.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'Do not feed the pebbles.',
					'text' => '{{npc:50053:rathemtn:Glaron the Wicked}} and {{npc:50276:rathemtn:Tabien the Goodly}} each reward your hand-in with a pebble ({{item:1669}} or {{item:1670}}) that the wandering spirit treats as proof of failure. Kill both men, loot their relics, and give them only to the spirit.',
				),
			),
			'steps' => array(
				array(
					'title' => 'A Small Gem',
					'text' => 'Find {{npc:12083:qey2hh1:a wandering spirit}} and give {{item:1667}} (needs {{faction:404:True Spirit}} of at least 100). He tells you to go to the Rathe Mountains.',
				),
				array(
					'title' => 'Rathe — Glaron\'s relics',
					'text' => 'Kill {{npc:50053:rathemtn:Glaron the Wicked}}. Loot {{item:1676}} and {{item:1677}} (both always drop). Respawn is slow (about three hours, with variance).',
				),
				array(
					'title' => 'Rathe — Tabien\'s shield',
					'text' => 'Kill {{npc:50276:rathemtn:Tabien the Goodly}} and loot {{item:1675}} (always drops). Respawn is around two hours, with variance.',
				),
				array(
					'title' => 'Return the three relics',
					'text' => 'Give {{item:1675}}, {{item:1676}}, and {{item:1677}} together to {{npc:12083:qey2hh1:a wandering spirit}} (needs True Spirit 200+). A partial hand-in is declined with a hint.',
					'sub' => array(
						'Say "shield of falsehood" to receive {{item:1679}} (needs True Spirit 300+).',
						'Say "last path" to receive {{item:1668}}.',
					),
				),
			),
		),
		array(
			'title' => 'Part 4 — Might: the Spirit Sentinel',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Two sentinels.',
					'text' => 'The Emerald Jungle has two Spirit Sentinels with different jobs. {{npc:94052:emeraldjungle:Spirit Sentinel}} handles the gem, the Black Dire pelt, and the Torsis report. {{npc:94013:emeraldjungle:Spirit Sentinel}} — the one under the pond — takes the Torsis logs, the diary, the scale, and issues the epic.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Black Dire timer.',
					'text' => '{{npc:59129:mistmoore:Black Dire}} despawns about 35 minutes after he spawns. His dialogue is flavor only — kill him rather than talking.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Sparkling Gem',
					'text' => 'In {{zone:emeraldjungle}}, give {{item:1668}} to {{npc:94052:emeraldjungle:Spirit Sentinel}} (True Spirit 300+). He asks if you want to continue.',
				),
				array(
					'title' => 'The path of might',
					'text' => 'Say "continue what I have started" (True Spirit 400+), then "walk the path of might", "like the wasichu", "find him", and "dark point ear" in turn. He sends you after a corrupted shaman who followed a dark elf.',
				),
				array(
					'title' => 'Black Dire',
					'text' => 'In {{zone:mistmoore}}, kill {{npc:59055:mistmoore:an advisor}} to pop {{npc:59129:mistmoore:Black Dire}} (he is also on a four-hour timer of his own). Loot {{item:1672}} (always drops).',
				),
				array(
					'title' => 'Black Fur Boots',
					'text' => 'Give {{item:1672}} to {{npc:94052:emeraldjungle:Spirit Sentinel}} (True Spirit 400+). He returns {{item:1678}} and a large experience grant.',
				),
			),
		),
		array(
			'title' => 'Part 5 — Torsis and the final turn-in',
			'intro' => 'The last stretch is a chain across Torsis ({{zone:citymist}}), The Hole, and the Plane of Fear. Check True Spirit faction before each hand-in; it climbs a little each time, then jumps near the end.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Faction check.',
					'text' => 'Thresholds in the scripts: Report 500, Torsis logs 600, Kirn 700, Neh`Ashiir 800, diary 900, Rak`Ashiir 1800, final scale 1900. The diary turn-in is the big jump (+1000). The final turn-in subtracts 2000 from True Spirit.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Rak`Ashiir aggro.',
					'text' => '{{npc:90013:citymist:Lord Rak`Ashiir}} attacks anyone below 1800 who gets close. Do not wander near him until you have at least that.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Ancient Journal',
					'text' => 'Say "heyokah" to {{npc:94052:emeraldjungle:Spirit Sentinel}} (True Spirit 500+), then "rub it out", "city\'s demise", and "how it all began". He hands you {{item:17805}}.',
				),
				array(
					'title' => 'Journal pages',
					'text' => 'In {{zone:citymist}}, collect {{item:18450}}, {{item:18451}}, {{item:18452}}, {{item:18453}}, and {{item:18454}}. Each drops at a low rate from most of the undead and golems in the city (skeletons, spurbones, plaguebones, goos, fog/haze/vapor golems, army behemoths).',
					'sub' => array(
						'{{item:18455}} comes from {{npc:90165:citymist:a spectral courier}} (~10% roll in a shared spawn group on the west side; high drop rate once up).',
					),
				),
				array(
					'title' => 'Combine the report',
					'text' => 'Combine all six pages inside {{item:17805}} (no-fail) to create {{item:3599}}. Give the report to {{npc:94052:emeraldjungle:Spirit Sentinel}} (True Spirit 500+) for a faction bump and experience.',
				),
				array(
					'title' => 'Black reaver placeholders',
					'text' => 'Three named ghosts use black reavers as placeholders. When one of these reavers dies there is an 80% chance another reaver spawns instantly, and a 20% chance the named appears instead. Keep killing until you see the named.',
					'sub' => array(
						'{{npc:90007:citymist:a black reaver}} ? {{npc:90010:citymist:Lord Ghiosk}}.',
						'{{npc:90008:citymist:a black reaver}} (upper level) ? {{npc:90011:citymist:Neh`Ashiir}}.',
						'{{npc:90009:citymist:a black reaver}} ? {{npc:90013:citymist:Lord Rak`Ashiir}}.',
					),
				),
				array(
					'title' => 'Ghiosk\'s logs',
					'text' => 'Kill {{npc:90010:citymist:Lord Ghiosk}} and loot {{item:18456}}, {{item:18457}}, and {{item:18458}} (all always drop). Give all three together to {{npc:94013:emeraldjungle:Spirit Sentinel}} (True Spirit 600+).',
				),
				array(
					'title' => 'Icon of the High Scale',
					'text' => 'Pick up {{item:1671}} from the ground in {{zone:citymist}} at about (+132, -370) — a ground spawn with a roughly five-minute respawn.',
				),
				array(
					'title' => 'High Scale Kirn',
					'text' => 'In {{zone:hole}}, find {{npc:39161:hole:High Scale Kirn}} and give {{item:1671}} (True Spirit 700+). He turns hostile as {{npc:39083:hole:High Scale Kirn}}; kill him and loot {{item:1681}} (always drops).',
				),
				array(
					'title' => 'Neh`Ashiir',
					'text' => 'Give {{item:1681}} to {{npc:90011:citymist:Neh`Ashiir}} (True Spirit 800+). The ghost turns hostile as {{npc:90012:citymist:Neh`Ashiir}}; kill it and loot {{item:18459}} (always drops).',
				),
				array(
					'title' => 'The diary',
					'text' => 'Give {{item:18459}} to {{npc:94013:emeraldjungle:Spirit Sentinel}} (True Spirit 900+). This adds a large amount of True Spirit faction and experience.',
				),
				array(
					'title' => 'Child\'s Tear',
					'text' => 'In {{zone:fearplane}}, kill Dread, Fright, or Terror. Each has a 76% chance to leave behind {{npc:72105:fearplane:an Iksar broodling}}. Kill it and loot {{item:1673}} (always drops). The three named are on a multi-day respawn timer, so plan the trip.',
				),
				array(
					'title' => 'Rak`Ashiir\'s scale',
					'text' => 'Give {{item:1673}} to {{npc:90013:citymist:Lord Rak`Ashiir}} (True Spirit 1800+). He turns hostile as {{npc:90014:citymist:Lord Rak`Ashiir}}; kill him and loot {{item:1674}} (always drops).',
					'sub' => array(
						'Below 1800 he attacks and returns the Tear instead.',
					),
				),
				array(
					'title' => 'Spear of Fate',
					'text' => 'Give {{item:1674}} to {{npc:94013:emeraldjungle:Spirit Sentinel}} (True Spirit 1900+) to receive {{item:10651}}. True Spirit drops by 2000 afterward.',
				),
			),
		),
	),
	'verified' => array(
		'rathemtn/Blinde_the_Cutpurse.lua',
		'oot/Capt_Surestout.lua',
		'butcher/Peg_Leg.lua',
		'fieldofbone/an_Iksar_manslayer.lua',
		'rathemtn/a_lesser_spirit.lua',
		'oot/a_lesser_spirit.lua',
		'fieldofbone/a_lesser_spirit.lua',
		'butcher/a_lesser_spirit.lua',
		'freportn/Bondl_Felligan.lua',
		'freportn/a_greater_spirit.lua',
		'freportn/a_greater_spirit_.lua',
		'erudsxing/Ooglyn.lua',
		'erudsxing/Srafen_the_Soaked.lua',
		'erudsxing/Dillon_the_Drowned.lua',
		'erudsxing/Froham_the_Forgotten.lua',
		'erudsxing/Abe_the_Abandoned.lua',
		'erudsxing/a_greater_spirit.lua',
		'qey2hh1/a_wandering_spirit.lua',
		'rathemtn/Glaron_the_Wicked.lua',
		'rathemtn/Tabien_the_Goodly.lua',
		'emeraldjungle/Spirit_Sentinel.lua',
		'emeraldjungle/Spirit_Sentinel_.lua',
		'mistmoore/an_advisor.lua',
		'mistmoore/Black_Dire.lua',
		'citymist/90007.lua',
		'citymist/90008.lua',
		'citymist/90009.lua',
		'citymist/#Neh-Ashiir.lua',
		'citymist/#Lord_Rak-Ashiir.lua',
		'hole/#High_Scale_Kirn_.lua',
		'fearplane/Dread.lua',
		'fearplane/Fright.lua',
		'fearplane/Terror.lua',
	),
);

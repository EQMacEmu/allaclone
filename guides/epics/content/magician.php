<?php
/**
 * Magician Epic 1.0 — Orb of Mastery
 *
 * Structure referenced from EQ Progression; prose is original.
 * Verified against local Lua (item IDs / turn-ins):
 *   lakerathe/Rykas.lua                 "Magi'kot" -> 28035 (summoned to cursor);
 *                                       28003+28004+28031 -> 18958 Power of the Orb
 *   commons/Jahsohn_Aksot.lua           28035 -> flavor/xp only; 28000+28001+28002 -> 28003
 *   butcher/Walnan.lua                  28036+28037+28038+28039 -> 28031
 *   najena/Akksstaff.lua                28027+28028+28029+28030 -> 28004; depop_with_timer
 *   firiona/Tiblner_Milnik.lua          11567+28042+6360+6361 -> 28032 Element of Earth
 *   skyfire/Jennus_Lyklobar.lua         18958+28007+10376+28008 -> 28009 Element of Fire
 *   cauldron/Jinalis_Andir.lua          11569+28040+28041 -> 28006 Element of Water
 *   airplane/Kihun_Solstin.lua          20764+28043+2463 -> 28033 Element of Wind; spawns Master 71040
 *   airplane/#Master_of_Elements.lua    28032+28009+28006+28033 -> 19436 (NOT 28034); 90 min depop
 *   soltemple/Vira.lua                  16544+16543+16538+10032 -> 6360 Broom of Trilon;
 *                                       10035+10014+16545+16539 -> 6361 Shovel of Ponz
 *   citymist/90008.lua                  a black reaver death: 80% respawn, 20% unique #Neh`Ashiir 90011
 * DB (peq via allaclone-local): item 19436 is "Spell: Summon Orb" (scroll, Magician only);
 *   spell 1944 Summon Orb (effect create item 28034, level 46, 500 mana, 5s cast, 300s recast);
 *   item 28034 Orb of Mastery click = spell 1936 Manifest Elements (pet SumMageMultiElement, npc 598).
 * Shared Najena loot pool (lootdrop 115863): A Broom, A Shovel, A Torch, A Stein, and the four
 *   Torn Pages of Mastery (each 12.5% within the pool; pool rolls ~13% per kill). Alligators in
 *   Feerrott / Cazic-Thule also roll the same pool in the TAKP DB.
 * Epic reward item id: 28034
 */

return array(
	'toc' => array(
		'Words of Mastery path (lead-in)',
		'The four Elements',
		'Master of Elements and the Orb',
	),
	'prefarm' => array(
		array(
			'item' => 28000,
			'note' => 'Dread wolves, e.g. {{npc:20050:kithicor:an enraged dread wolf}}',
		),
		array(
			'item' => 28001,
			'note' => 'Terrors, e.g. {{npc:63043:unrest:a tentacle terror}} / {{npc:63113:unrest:a dark terror}}',
		),
		array(
			'item' => 28002,
			'note' => 'Ghouls, e.g. {{npc:66146:gukbottom:a frenzied ghoul}} / {{npc:66147:gukbottom:a bloodthirsty ghoul}}',
		),
		array(
			'html' => 'Four {{item:28027:Torn Pages of Mastery}} ({{item:28027:Fire}}, {{item:28028:Wind}}, {{item:28029:Earth}}, {{item:28030:Water}}) — rare drops shared with the Najena tool pool; see Part 1',
		),
		array(
			'html' => 'Four Power pieces for Walnan: {{item:28036}}, {{item:28037}}, {{item:28038}}, {{item:28039}}',
		),
		array(
			'item' => 11567,
			'note' => '{{npc:76326:hateplane:#Magi P`tasa}} or {{npc:76003:hateplane:#Innoruuk}}',
		),
		array(
			'item' => 11569,
			'note' => '{{npc:64001:kedge:Phinigel Autropos}} (long respawn)',
		),
		array(
			'item' => 28040,
			'note' => '{{npc:93085:overthere:Captain Rottgrime}}',
		),
		array(
			'item' => 28041,
			'note' => '{{npc:15171:eastkarana:Tarbul Earthstrider}}',
		),
		array(
			'item' => 10376,
			'note' => '{{npc:64087:kedge:Undertow}} ',
		),
		array(
			'item' => 2463,
			'note' => '{{npc:14139:southkarana:#Quillmane}}',
		),
	),
	'sections' => array(
		array(
			'title' => 'Part 1 — Words of Mastery path (lead-in)',
			'intro' => 'Four short quests that end with you holding {{item:18958}}. You will need that tome for the Fire element later, so finish this part before turning in to Jennus.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Order.',
					'text' => 'Rykas starts you off and finishes the loop. Jahsohn, Walnan, and Akksstaff can be done in any order once you have the token. You need all three of {{item:28003}}, {{item:28004}}, and {{item:28031}} in hand for Rykas.',
				),
				array(
					'kind' => 'note',
					'title' => 'Token of Mastery.',
					'text' => '{{item:28035}} is a conversation item. Jahsohn reacts to it and grants a small amount of experience, but his page turn-in does not check for it. Keep it for the story if you like.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Start — Rykas',
					'text' => 'Hail {{npc:51045:lakerathe:Rykas}}, say you are looking for knowledge, then ask about Magi\'kot. He conjures {{item:28035}} onto your cursor and points you to the Commonlands.',
				),
				array(
					'title' => 'Jahsohn Aksot',
					'text' => 'Find {{npc:21043:commons:Jahsohn Aksot}}. Mention Rykas, then ask about the Words and what task to do. He wants the three torn pages of Magi\'kot.',
					'sub' => array(
						'{{item:28000}} — dread wolves ({{npc:20050:kithicor:an enraged dread wolf}}, {{npc:20226:kithicor:a dreaded wolf}}).',
						'{{item:28001}} — {{npc:63043:unrest:a tentacle terror}} or {{npc:63113:unrest:a dark terror}}.',
						'{{item:28002}} — ghouls, e.g. {{npc:66146:gukbottom:a frenzied ghoul}}.',
						'Give Jahsohn {{item:28000}}, {{item:28001}}, and {{item:28002}} together for {{item:28003}}. He then sends you to Walnan.',
					),
				),
				array(
					'title' => 'Walnan — Power of the Elements',
					'text' => 'Find {{npc:68087:butcher:Walnan}}. Hail her, answer that you are in search of the truth, say you have come to learn, then answer "no" when she asks if you know Magi\'kot\'s fate. She asks for four Power pieces.',
					'sub' => array(
						'{{item:28038}} — fae guards, e.g. {{npc:57101:lfaydark:a faerie guard}}.',
						'{{item:28036}} — lava elementals, e.g. {{npc:31143:soldunga:a lava elemental}}.',
						'{{item:28037}} — {{npc:59144:mistmoore:a gypsy dancer}}.',
						'{{item:28039}} — ravener-type monsters, e.g. {{npc:88080:kaesora:an enraged ravener}}.',
						'Give all four to Walnan for {{item:28031}}. She tells you to travel to Najena.',
					),
				),
				array(
					'title' => 'Akksstaff — Words of Mastery',
					'text' => 'Find {{npc:44063:najena:Akksstaff}}. Hail him, then say "tell me about Magi\'kot" and "wish to hear more". He wants four torn pages of Mastery.',
					'sub' => array(
						'{{item:28027}}, {{item:28028}}, {{item:28029}}, {{item:28030}} come from the same rare-drop pool as the keep\'s magician tools, carried by many of the humanoids inside Najena\'s keep (magicians, {{npc:44057:najena:Linara Parlone}}, {{npc:44102:najena:Ekeros}}, and others).',
						'Give all four pages to Akksstaff for {{item:28004}}. He leaves the room after the turn-in and returns on a timer.',
					),
				),
				array(
					'title' => 'Finish — Rykas',
					'text' => 'Return to {{npc:51045:lakerathe:Rykas}} and hand over {{item:28003}}, {{item:28004}}, and {{item:28031}}. He gives you {{item:18958}}. Do not turn it in anywhere except to Jennus.',
				),
			),
		),
		array(
			'title' => 'Part 2 — The four Elements',
			'intro' => 'Each master wants a different set of items and hands back an Element. The Elements can be gathered in any order, except that the Fire turn-in also consumes {{item:18958}}.',
			'callouts' => array(
				array(
					'kind' => 'note',
					'title' => 'Prefarm what you can.',
					'text' => 'Earth and Fire have the longest shopping lists. Start the Earth and Fire farms early and work the easier Water and Wind lists around raid-flagged or long-respawn targets.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Element of Earth — Tiblner Milnik',
					'text' => 'Find {{npc:84160:firiona:Tiblner Milnik}}. Hail him and ask for the Element of Earth. Give {{item:11567}}, {{item:28042}}, {{item:6360}}, and {{item:6361}} to receive {{item:28032}}.',
					'sub' => array(
						'{{item:11567}} drops from {{npc:76326:hateplane:#Magi P`tasa}} or {{npc:76003:hateplane:#Innoruuk}}.',
						'{{item:28042}} drops from {{npc:87047:burningwood:Slixin Klex}}.',
						'{{item:6360}} and {{item:6361}} are crafted for you by {{npc:80015:soltemple:Vira}} (see the next two steps). She only deals with people she is at least indifferent toward; her turn-ins also shift {{faction:415:Temple of Sol Ro}} standing.',
					),
				),
				array(
					'title' => 'Earth tool — Broom of Trilon',
					'text' => 'Give {{npc:80015:soltemple:Vira}} {{item:16544}}, {{item:16543}}, {{item:16538}}, and {{item:10032}} to receive {{item:6360}}.',
					'sub' => array(
						'{{item:16544}} is a rare drop from the Najena magician pool (see Akksstaff above).',
						'{{item:16543}} — cyclopes, e.g. {{npc:12050:qey2hh1:a cyclops}} or {{npc:50305:rathemtn:a cyclops}}.',
						'{{item:16538}} — griffons, e.g. {{npc:13005:northkarana:a griffon}} or {{npc:21004:commons:a griffin}}.',
						'{{item:10032}} — buy from a gem merchant or loot.',
					),
				),
				array(
					'title' => 'Earth tool — Shovel of Ponz',
					'text' => 'Give {{npc:80015:soltemple:Vira}} {{item:10035}}, {{item:10014}}, {{item:16545}}, and {{item:16539}} to receive {{item:6361}}.',
					'sub' => array(
						'{{item:10035}} — buy from a gem merchant or loot.',
						'{{item:10014}} — gargoyles, e.g. {{npc:59057:mistmoore:a leering gargoyle}}.',
						'{{item:16545}} is another rare drop from the Najena magician pool.',
						'{{item:16539}} — hill giants, e.g. {{npc:13267:northkarana:a hill giant}} or {{npc:21106:commons:a hill giant}}.',
					),
				),
				array(
					'title' => 'Element of Fire — Jennus Lyklobar',
					'text' => 'Find {{npc:91007:skyfire:Jennus Lyklobar}}. Hail him and say you have come for the Element of Fire. Give {{item:18958}}, {{item:28007}}, {{item:10376}}, and {{item:28008}} to receive {{item:28009}}.',
					'sub' => array(
						'{{item:28007}} drops from {{npc:90011:citymist:#Neh`Ashiir}}. He is a rare spawn that has a chance of replacing a killed {{npc:90008:citymist:black reaver}}.',
						'{{item:10376}} drops from {{npc:64087:kedge:Undertow}}.',
						'{{item:28008}} drops from burning-wurm type monsters: {{npc:87007:burningwood:Nezekezena}}, {{npc:87013:burningwood:Phurzikon}}, or {{npc:87070:burningwood:Gylton}}.',
					),
				),
				array(
					'title' => 'Element of Water — Jinalis Andir',
					'text' => 'Find {{npc:70028:cauldron:Jinalis Andir}}. Give {{item:11569}}, {{item:28040}}, and {{item:28041}} to receive {{item:28006}}.',
					'sub' => array(
						'{{item:11569}} drops from {{npc:64001:kedge:Phinigel Autropos}}.',
						'{{item:28040}} drops from {{npc:93085:overthere:Captain Rottgrime}}.',
						'{{item:28041}} drops from {{npc:15171:eastkarana:Tarbul Earthstrider}}.',
					),
				),
				array(
					'title' => 'Element of Wind — Kihun Solstin',
					'text' => 'Find {{npc:71055:airplane:Kihun Solstin}}. Hail him and say you seek the Element of Wind. Give {{item:20764}}, {{item:28043}}, and {{item:2463}} to receive {{item:28033}}.',
					'sub' => array(
						'{{item:20764}} drops from Plane of Sky inhabitants such as {{npc:71000:airplane:an undine spirit}} and {{npc:71002:airplane:a windrider drake}}.',
						'{{item:28043}} drops from the elemental casters, e.g. {{npc:39000:hole:an elemental channeler}}.',
						'{{item:2463}} drops from {{npc:14139:southkarana:#Quillmane}}.',
						'On a successful turn-in Kihun also spawns the Master of Elements.',
					),
				),
			),
		),
		array(
			'title' => 'Part 3 — Master of Elements and the Orb',
			'intro' => 'With all four Elements in your bags, the last turn-in is a short fight-free conversation and one more item swap.',
			'callouts' => array(
				array(
					'kind' => 'warning',
					'title' => 'The Master gives a spell scroll.',
					'text' => 'The Master of Elements does not hand you the Orb directly. His reward is {{item:19436}}, a Magician-only spell scroll. Scribing it teaches Summon Orb, and casting that spell creates {{item:28034}}.',
				),
				array(
					'kind' => 'warning',
					'title' => 'Ninety-minute timer.',
					'text' => 'The Master despawns about 90 minutes after he spawns. If he is gone, hail Kihun and say "see the master" to spawn him again.',
				),
			),
			'steps' => array(
				array(
					'title' => 'Master of Elements',
					'text' => 'Find {{npc:71040:airplane:the Master of Elements}} near Kihun. Hail him and say "I am the one". Give {{item:28032}}, {{item:28009}}, {{item:28006}}, and {{item:28033}} together to receive {{item:19436}}.',
				),
				array(
					'title' => 'Scribe Summon Orb',
					'text' => 'Scribe {{item:19436}} (Magician only; the spell is level 46). Memorize Summon Orb.',
				),
				array(
					'title' => 'Orb of Mastery',
					'text' => 'Cast Summon Orb (500 mana, about 5 seconds to cast, 5 minute recast) to create {{item:28034}}. Equip it in your primary slot; the click effect, Manifest Elements, summons an elemental pet ({{npc:598:Elemental pet}}).',
				),
			),
		),
	),
	'verified' => array(
		'lakerathe/Rykas.lua',
		'commons/Jahsohn_Aksot.lua',
		'butcher/Walnan.lua',
		'najena/Akksstaff.lua',
		'firiona/Tiblner_Milnik.lua',
		'skyfire/Jennus_Lyklobar.lua',
		'cauldron/Jinalis_Andir.lua',
		'airplane/Kihun_Solstin.lua',
		'airplane/#Master_of_Elements.lua',
		'soltemple/Vira.lua',
		'citymist/90008.lua',
	),
);

<?php
function SpellDescription($spell, $n, $csv = false) {
	global $dbspelleffects, $tbitems, $dbiracenames, $tbspells, $ServerMaxLevel, $dbspelltargets;
	$minlvl = 70;
	$duration = CalcBuffDuration($minlvl, $spell["buffdurationformula"], $spell["buffduration"]);
	// "spacer" effects use the same effectid as CHA (10), have a base
	// value of 0 and a formula of 100
	$is_spacer = ($spell["effectid$n"] == 10 && $spell["effect_base_value$n"] == 0 && $spell["formula$n"] == 100);
	if (($spell["effectid$n"] != 254) and !$is_spacer) {
		$maxlvl = $spell["effect_base_value$n"];
		$minlvl = $ServerMaxLevel;
		for ($i = 1; $i < 16; $i++) {
			if ($spell["classes" . $i] < $minlvl) {
				$minlvl = $spell["classes" . $i];
			}
		}
		$min = CalcSpellEffectValue($spell["formula" . $n], $spell["effect_base_value$n"], $spell["max$n"], $minlvl);
		$max = CalcSpellEffectValue($spell["formula" . $n], $spell["effect_base_value$n"], $spell["max$n"], $ServerMaxLevel);
		$base_limit = $spell["effect_limit_value$n"];
		//debug($spell['name']." ".$min." ".$max);
		//debug($spell["formula".$n]." ".$spell["effect_base_value$n"]." ".$spell["max$n"]." ".$minlvl."x");
		if (($min < $max) and ($max < 0)) {
			$tn = $min;
			$min = $max;
			$max = $tn;
		}
		if ($spell["effectid$n"] != 254 and $spell["effectid$n"] != 10) {
			//print 'Debug effectid: '. $spell["effectid$n"] .'<br>';
		}
		if ($csv == true) {
			print ",,";
		} else {
			print "<li>$n: ";
		}
		switch ($spell["effectid$n"]) {
			case 3: // Increase Movement (% / 0)
				if ($max < 0) { // Decrease
					print "Decrease Movement";
					if ($min != $max) {
						print " by " . abs($min) . "% (L$minlvl) to " . abs($max) . "% (L$maxlvl)";
					} else {
						print " by " . abs(100) . "%";
					}
				} else {
					print "Increase Movement";
					if ($min != $max) {
						print " by " . $min . "% (L$minlvl) to " . ($max) . "% (L$maxlvl)";
					} else {
						print " by " . ($max) . "%";
					}
				}
				break;
			case 11: // Decrease OR Increase AttackSpeed (max/min = percentage of speed / normal speed, IE, 70=>-30% 130=>+30%
				if ($max < 100) { // Decrease
					print "Decrease Attack Speed";
					if ($min != $max) {
						print " by " . (100 - $min) . "% (L$minlvl) to " . (100 - $max) . "% (L$maxlvl)";
					} else {
						print " by " . (100 - $max) . "%";
					}
				} else {
					print "Increase Attack Speed";
					if ($min != $max) {
						print " by " . ($min - 100) . "% (L$minlvl) to " . ($max - 100) . "% (L$maxlvl)";
					} else {
						print " by " . ($max - 100) . "%";
					}
				}
				break;
			case 64: // SpinStun
			case 21: // stun 
				print $dbspelleffects[$spell["effectid$n"]];
				if ($min != $max) {
					print " (" . ($min / 1000) . " sec (L$minlvl) to " . ($max / 1000) . " sec (L$maxlvl))";
				} else {
					print " (" . ($max / 1000) . " sec)";
				}
				break;
			case 20: // Blindness
				print $dbspelleffects[$spell["effectid$n"]] . " (" . $max . ")";
				break;
			case 32: // summonitem
			case 109: // summonitem v2
				print $dbspelleffects[$spell["effectid$n"]];
				$name = GetFieldByQuery("name", "SELECT name FROM $tbitems WHERE id=" . $spell["effect_base_value$n"]);
				if (($name != "") and ($csv == false)) {
					$id = $spell["effect_base_value$n"];
					print " : <a data-item-id=\"$id\" class=\"item-link\" href=item.php?id=$id>$name</a>";
				} else {
					print " : $name";
				}
				break;
			case 87: // Increase Magnification
			case 114: // Increase Agro Multiplier
			case 119: // Increase Haste v3
			case 123: // Increase Spell Damage
			case 124: // Increase Spell Damage
			case 125: // Increase Spell Healing
			case 127: // Increase Spell Haste
			case 128: // Increase Spell Duration
			case 129: // Increase Spell Range
			case 130: // Increase Spell/Bash Hate
			case 131: // Decrease Chance of Using Reagent
			case 132: // Decrease Spell Mana Cost
			case 158: // Increase Chance to Reflect Spell
			case 168: // Increase Melee Mitigation
			case 169: // Increase Chance to Critical Hit
			case 172: // Increase Chance to Avoid Melee
			case 173: // Increase Chance to Riposte
			case 174: // Increase Chance to Dodge
			case 175: // Increase Chance to Parry
			case 176: // Increase Chance to Dual Wield
			case 177: // Increase Chance to Double Attack
			case 180: // Increase Chance to Resist Spell
			case 181: // Increase Chance to Resist Fear Spell
			case 183: // Increase All Skills Skill Check
			case 184: // Increase Chance to Hit With all Skills
			case 185: // Increase All Skills Damage Modifier
			case 186: // Increase All Skills Minimum Damage Modifier
			case 188: // Increase Chance to Block
			case 200: // Increase Proc Modifier
			case 201: // Increase Range Proc Modifier
			case 216: // Increase Accuracy
			case 227: // Reduce Skill Timer
			case 266: // Add Attack Chance
			case 273: // Increase Critical Dot Chance
			case 294: // Increase Critical Spell Chance
				print $dbspelleffects[$spell["effectid$n"]];
				if ($min != $max) {
					print " by $min% (L$minlvl) to $max% (L$maxlvl)";
				} else {
					$max = abs($max);
					print " by $max%";
				}
				break;
			case 98: // Increase Haste v2
				$max -= 100;
				print $dbspelleffects[$spell["effectid$n"]] . " by $max%";
				break;
			case 15: // Increase/Decrease Mana per tick
				$effect = $dbspelleffects[$spell["effectid$n"]];
				if ($max < 0) {
					$effect = str_replace("Increase", "Decrease", $effect);
				}
				print $effect;
				if ($duration) {
					if ($min != $max) {
						print " by " . abs($min) . " (L$minlvl) to " . abs($max) . " (L$maxlvl) per tick (total " . abs($min * $duration) . " to " . abs($max * $duration) . ")";
					} else {
						print " by $max per tick (total " . abs($max * $duration) . ")";
					}
				} else {
					print " by " . abs($max);
				}
				break;
			case 100: // Increase Hitpoints v2 per tick
				print $dbspelleffects[$spell["effectid$n"]];
				if ($min != $max) {
					print " by " . abs($min) . " (L$minlvl) to " . abs($max) . " (L$maxlvl) per tick (total " . abs($min * $duration) . " to " . abs($max * $duration) . ")";
				} else {
					print " by $max per tick (total " . abs($max * $duration) . ")";
				}
				break;
			case 30: // Frenzy Radius
			case 86: // Reaction Radius
				print $dbspelleffects[$spell["effectid$n"]];
				print " (" . $spell["effect_base_value$n"] . "/" . $spell["effect_limit_value$n"] . ")";
				break;
			case 22: // Charm
			case 23: // Fear
			case 31: // Mesmerize
				print $dbspelleffects[$spell["effectid$n"]];
				print " up to level " . $spell["effect_limit_value$n"];
				break;
			case 33: // Summon Pet:
			case 68: // Summon Skeleton Pet:
			case 106: // Summon Warder:
			case 108: // Summon Familiar:
			case 113: // Summon Horse:
			case 152: // Summon Pets: 
				print $dbspelleffects[$spell["effectid$n"]];
				if ($csv == false) {
					print " <a href=pet.php?name=" . $spell["teleport_zone"] . ">" . $spell["teleport_zone"] . "</a>";
				} else {
					print " : " . $spell["teleport_zone"];
				}
				break;
			case 13: // See Invisible
			case 18: // Pacify
			case 25: // Bind Affinity
			case 26: // Gate
			case 28: // Invisibility versus Undead
			case 29: // Invisibility versus Animals
			case 40: // Invunerability
			case 41: // Destroy Target
			case 42: // Shadowstep
			case 44: // Lycanthropy
			case 52: // Sense Undead
			case 53: // Sense Summoned
			case 54: // Sense Animals
			case 56: // True North
			case 57: // Levitate
			case 61: // Identify
			case 65: // Infravision
			case 66: // UltraVision
			case 67: // Eye of Zomm
			case 68: // Reclaim Energy
			case 73: // Bind Sight
			case 74: // Feign Death
			case 75: // Voice Graft
			case 76: // Sentinel
			case 77: // Locate Corpse
			case 82: // Summon PC
			case 90: // Cloak
			case 93: // Stop Rain
			case 94: // Make Fragile (Delete if combat)
			case 95: // Sacrifice
			case 96: // Silence
			case 99: // Root
			case 101: // Complete Heal (with duration)
			case 103: // Call Pet
			case 104: // Translocate target to their bind point
			case 105: // Anti-Gate
			case 115: // Food/Water
			case 117: // Make Weapons Magical
			case 135: // Limit: Resist(XXX allowed)
				switch($spell["effect_base_value$n"])
				{
				    case 1:
					$type = "Magic";
					break;
				    case 2:
					$type = "Fire";
					break;
				    case 3:
					$type = "Cold";
					break;
				}
				print $dbspelleffects[$spell["effectid$n"]] . "($type allowed)";
				break;
			case 137: // Limit: Effect
				print $dbspelleffects[$spell["effectid$n"]];
				$id = abs($spell["effect_base_value$n"]);
				if (!$id) {
					print " (Hitpoints allowed)";
				} else {
					$name = $dbspelleffects[$id];
					print " ({$name} excluded)";
				}
				break;
			case 138: // Limit: Spell Type
				print $dbspelleffects[$spell["effectid$n"]];
				$type = $spell["effect_base_value$n"] ? "Beneficial" : "Detrimental";
				print " ($type only)";
				break;
			case 139: // Limit: Spell
				print $dbspelleffects[$spell["effectid$n"]];
				$id = $spell["effect_base_value$n"];
				$name = GetFieldByQuery("Name", "SELECT Name FROM $tbspells WHERE id=abs($id)");
				if ($id > 0) {
					print "({$name} included)";
				} else {
					print " ({$name} excluded)";
				}
				break;
			case 141: // Limit: Instant spells only
			case 150: // Death Save - Restore Partial Health
			case 151: // Suspend Pet - Lose Buffs and Equipment
			case 154: // Remove Detrimental
			case 156: // Illusion: Target
			case 178: // Lifetap from Weapon Damage
			case 179: // Instrument Modifier
			case 182: // Hundred Hands Effect
			case 194: // Fade
			case 195: // Stun Resist
			case 205: // Rampage
			case 206: // Area of Effect Taunt
			case 311: // Limit: Combat Skills Not Allowed
			case 314: // Fixed Duration Invisbility
			case 299: // Wake the Dead
				print $dbspelleffects[$spell["effectid$n"]];
				break;
			case 58: // Illusion:
				print $dbspelleffects[$spell["effectid$n"]];
				print $dbiracenames[$spell["effect_base_value$n"]];
				break;
			case 63: // Memblur
			case 120: // Set Healing Effectiveness
			case 330: // Critical Damage Mob
				print $dbspelleffects[$spell["effectid$n"]];
				print " ($max%)";
				break;
			case 81: // Resurrect
				print $dbspelleffects[$spell["effectid$n"]];
				print " and restore " . $spell["effect_base_value$n"] . "% experience";
				break;
			case 84:
				$value = -$spell["effect_base_value$n"];
				print $dbspelleffects[$spell["effectid$n"]] . "($value)";
				break;
			case 83: // Teleport
			case 88: // Evacuate
			case 145: // Teleport v2
				//print " (Need to add zone to spells table)";
				print $dbspelleffects[$spell["effectid$n"]];
				if ($csv == false) {
					print " <a href=zone.php?name=" . $spell["teleport_zone"] . ">" . $spell["teleport_zone"] . "</a>";
				} else {
					print " : " . $spell["teleport_zone"];
				}
				break;
			case 85: // Add Proc:
			case 289: // Improved Spell Effect:
			case 323: // Add Defensive Proc:
				print $dbspelleffects[$spell["effectid$n"]];
				$name = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=" . $spell["effect_base_value$n"]);
				if ($csv == false) {
					print "<a href=spell.php?id=" . $spell["effect_base_value$n"] . ">$name</a>";
				} else {
					print " : $name";
				}
				break;
			case 89: // Increase Player Size
				$name = $dbspelleffects[$spell["effectid$n"]];
				$min -= 100;
				$max -= 100;
				if ($max < 0) {
					$name = str_replace("Increase", "Decrease", $name);
				}
				print $name;
				if ($min != $max) {
					print " by $min% (L$minlvl) to $max% (L$maxlvl)";
				} else {
					print " by $max%";
				}
				break;
			case 27: // Cancel Magic
			case 134: // Limit: Max Level
			case 157: // Spell-Damage Shield
				print $dbspelleffects[$spell["effectid$n"]];
				print " ($max)";
				break;
			case 121: // Reverse Damage Shield
				print $dbspelleffects[$spell["effectid$n"]];
				print " (-$max)";
				break;
			case 91: // Summon Corpse
				print $dbspelleffects[$spell["effectid$n"]];
				print " (max level $max)";
				break;
			case 136: // Limit: Target
				print $dbspelleffects[$spell["effectid$n"]];
				if ($max < 0) {
					$max = -$max;
					$v = " excluded";
				} else {
					$v = "";
				}
				print " (" . $dbspelltargets[$max] . "$v)";
				break;
			case 139: // Limit: Spell
				print $dbspelleffects[$spell["effectid$n"]];
				$max = $spell["effect_base_value$n"];
				if ($max < 0) {
					$max = -$max;
					$v = " excluded";
				}
				$name = GetFieldByQuery("name", "SELECT name FROM $tbspells WHERE id=$max");
				if ($csv == false) {
					print "($name)";
				} else {
					print " (<a href=spell.php?id=" . $spell["effect_base_value$n"] . ">$name</a>$v)";
				}
				break;
			case 140: // Limit: Min Duration
				print $dbspelleffects[$spell["effectid$n"]];
				$min *= 6;
				$max *= 6;
				if ($min != $max) {
					print " ($min sec (L$minlvl) to $max sec (L$maxlvl))";
				} else {
					print " ($max sec)";
				}
				break;
			case 143: // Limit: Min Casting Time
				print $dbspelleffects[$spell["effectid$n"]];
				$min *= 6;
				$max *= 6;
				if ($min != $max) {
					print " (" . ($min / 6000) . " sec (L$minlvl) to " . ($max / 6000) . " sec (L$maxlvl))";
				} else {
					print " (" . ($max / 6000) . " sec)";
				}
				break;
			case 148: // Stacking: Overwrite existing spell
				print $dbspelleffects[$spell["effectid$n"]];
				$blocked_effect_slot = $spell["formula$n"];
				// TAKP code is 0 based, we are 1 based
				if ($blocked_effect_slot > 199)
					$blocked_effect_slot -= 200;
				print " if slot $blocked_effect_slot is effect '" . $dbspelleffects[$spell["effect_base_value$n"]] . "' and <" . $spell["max$n"];
				break;
			case 149: // Stacking: Overwrite existing spell
				print $dbspelleffects[$spell["effectid$n"]];
				$overwrite_effect_slot = $spell["formula$n"];
				// TAKP code is 0 based, we are 1 based
				if ($overwrite_effect_slot > 199)
					$overwrite_effect_slot -= 200;
				print " if slot $overwrite_effect_slot is effect '" . $dbspelleffects[$spell["effect_base_value$n"]] . "' and <" . $spell["max$n"];
				break;
			case 147: // Increase Hitpoints (%)  
				$name = $dbspelleffects[$spell["effectid$n"]];
				if ($max < 0) {
					$name = str_replace("Increase", "Decrease", $name);
				}
				print $name . " by " . $spell["effect_limit_value$n"] . " ($max% max)";
				break;
			case 153: // Balance Party Health
				print $dbspelleffects[$spell["effectid$n"]];
				print " ($max% penalty)";
				break;
			case 110: // Increase Chance to Hit by XX% with Archery
				print "Increase Chance to Hit by " . $spell["effect_base_value$n"] . "% with Archery.";
				break;
			case 0: // In/Decrease hitpoints
			case 1: // Increase AC
			case 2: // Increase ATK
			case 4: // Increase STR
			case 5: // Increase DEX
			case 6: // Increase AGI
			case 7: // Increase STA
			case 8: // Increase INT
			case 9: // Increase WIS
			case 10: // Increase CHA
			case 19: // Increase Faction
			case 35: // Increase Disease Counter
			case 36: // Increase Poison Counter
			case 46: // Increase Magic Fire 
			case 47: // Increase Magic Cold 
			case 48: // Increase Magic Poison 
			case 49: // Increase Magic Disease 
			case 50: // Increase Magic Resist
			case 55: // Increase Absorb Damage
			case 59: // Increase Damage Shield
			case 69: // Increase Max Hitpoints
			case 78: // Increase Absorb Magic Damage
			case 79: // Increase HP when cast 
			case 92: // Increase hate
			case 97: // Increase Mana Pool
			case 111: // Increase All Resists
			case 112: // Increase Effective Casting
			case 116: // Decrease Curse Counter
			case 118: // Increase Singing Skill 
			case 159: // Decrease Stats
			case 167: // Pet Power Increase
			case 192: // Increase hate
			default:
				$name = $dbspelleffects[$spell["effectid$n"]];
				if ($max < 0) {
					$name = str_replace("Increase", "Decrease", $name);
				}
				print $name;
				if ($min != $max) {
					print " by $min (L$minlvl) to $max (L$maxlvl)";
				} else {
					if ($max < 0) {
						$max = -$max;
					}
					print " by $max";
				}
				break;
		}
		echo '</li>';
	}
}

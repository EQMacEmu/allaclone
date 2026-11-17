function AlphaSay(e)
	if(e.message:findi("hail")) then
		e.self:Say("Alpha hail reply");
	end
end

function AlphaTrade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 6001})) then
		e.other:QuestReward(e.self,{itemid = 6002});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

function BetaSay(e)
	if(e.message:findi("hail")) then
		e.self:Say("Beta hail reply");
	end
end

function BetaTrade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 7001})) then
		e.other:QuestReward(e.self,{itemid = 7002});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

function SharedDeath(e)
	eq.depop();
end

function event_encounter_load(e)
	eq.register_npc_event("Enc", Event.say, 10101, AlphaSay);
	eq.register_npc_event("Enc", Event.trade, 10101, AlphaTrade);
	eq.register_npc_event("Enc", Event.say, 10102, BetaSay);
	eq.register_npc_event("Enc", Event.trade, 10102, BetaTrade);
	eq.register_npc_event("Enc", Event.death_complete, 10999, SharedDeath);
end

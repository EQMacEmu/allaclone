function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 4001})) then
		e.other:Faction(e.self, 401, 25);
		e.other:Faction(e.self, 402, -10);
		e.other:QuestReward(e.self, 0, 0, 0, 0, 4002, 10);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 4003})) then
		e.other:Faction(e.self, 403, 5);
		e.other:QuestReward(e.self, 0, 0, 0, 0, 4004, 10);
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

function event_say(e)
	if(e.message:findi("hail")) then
		e.self:Say("Hello adventurer.");
	elseif(e.message:findi("challenge")) then
		eq.unique_spawn(27119, 0, 0, 100.5, -200, 10, 128);
	end
end

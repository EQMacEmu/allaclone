function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 1001, item2 = 1002})) then
		e.other:Faction(e.self, 301, 10);
		e.other:QuestReward(e.self, 0, 0, 0, 1, 2001, 500);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 1003})) then
		e.other:Faction(e.self, 302, -5);
		e.other:QuestReward(e.self,{items = {2002}, exp = 100, copper = 5});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

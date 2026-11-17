function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 1001})) then
		e.other:QuestReward(e.self,{itemid = 2001,exp = 50});
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 1002})) then
		e.other:QuestReward(e.self,{items = {2002,2003}, exp = 100});
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 1003})) then
		e.other:QuestReward(e.self, 0, 0, 0, 1, 2004, 25);
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

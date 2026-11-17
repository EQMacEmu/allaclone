function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 3001})) then
		e.other:QuestReward(e.self,{
			itemid = 3002,
			exp = 75,
			platinum = 1
		});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

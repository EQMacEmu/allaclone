function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {platinum = 50})) then
		e.other:QuestReward(e.self, 0, 0, 0, 0, 3001, 50);
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 13703, platinum = 1})) then
		e.other:QuestReward(e.self, 0, 0, 0, 0, 3002, 25);
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

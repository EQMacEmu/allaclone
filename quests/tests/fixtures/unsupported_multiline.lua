function event_trade(e)
	local item_lib = require("items");
	-- Multiline table intentionally unsupported
	if(item_lib.check_turn_in(e.self, e.trade, {
		item1 = 5001,
		item2 = 5002
	})) then
		e.other:QuestReward(e.self, 0, 0, 0, 0, 5003, 1);
	end
	local item_id = 5004;
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = item_id})) then
		e.other:QuestReward(e.self,{gold = math.random(5,8), exp = 500});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

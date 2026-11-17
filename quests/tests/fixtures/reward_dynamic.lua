function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 4001})) then
		e.other:QuestReward(e.self,{itemid = eq.ChooseRandom(5001,5002),exp = 100});
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 4002})) then
		e.other:QuestReward(e.self,{copper = math.random(0,10),itemid = 5003,exp = 50});
	elseif(item_lib.check_turn_in(e.self, e.trade, {item1 = 4003})) then
		e.other:QuestReward(e.self,0,0,0,0,eq.ChooseRandom(5004,5005),10);
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

function event_trade(e)
	local item_lib = require("items");

	-- Deputy Budo-style: single item, rewards in if(count > 0) / repeat
	local count = item_lib.count_handed_item(e.self, e.trade, {13916});
	if(count > 0) then
		repeat
			e.other:Faction(e.self,263,5,0);
			e.other:Faction(e.self,286,1,0);
			e.other:QuestReward(e.self,0,0,9,0,13024,100);
			count = count - 1;
		until count == 0;
	end

	-- min_count > 1 with a single item id (qty shown as Nx)
	local shoulder = item_lib.count_handed_item(e.self, e.trade, {13319}, 2, text);
	if(shoulder > 0) then
		e.other:QuestReward(e.self, 0, 0, 0, 0, 2001, 50);
	end

	item_lib.return_items(e.self, e.other, e.trade)
end

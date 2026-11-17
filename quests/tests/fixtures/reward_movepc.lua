function event_say(e)
	if(e.message:findi("port me")) then
		e.self:Say("Away with you!");
		e.other:MovePC(27,534,913,55,0); -- Zone: lavastorm
	elseif(e.message:findi("dynamic")) then
		e.other:MovePC(zone_id, x, y, z, 0);
	end
end

function event_enter(e)
	if(e.other:GetLevel() > 52) then
		e.other:Message(4,"I will not fight you, but I shall banish you!");
		e.other:MovePC(27,534,913,55,0);
	end
end

function event_trade(e)
	local item_lib = require("items");
	if(item_lib.check_turn_in(e.self, e.trade, {item1 = 12990, item2 = 12755})) then
		e.self:Say("Ha ha ha!!");
		e.other:QuestReward(e.self,0,0,0,0,0,5000);
		e.other:MovePC(87,-4039,6280,-53);
	end
	item_lib.return_items(e.self, e.other, e.trade)
end

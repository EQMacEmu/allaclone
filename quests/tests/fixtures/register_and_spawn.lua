local TARGET_NPC = 12001

function event_spawn(e)
	eq.register_npc_event("Encounters", Event.say, TARGET_NPC);
	eq.register_npc_event("Encounters", Event.trade, 12002);
end

function event_say(e)
	if(e.message:findi("spawn")) then
		eq.spawn2(12003, 0, 0, 11, 22, 33, 0);
	end
end

-- local TARGET = 99999;
local TARGET = 12003;
-- eq.register_npc_event("comments", Event.death, 99999, discarded_death);
--[[
function event_trade(e)
  if item_lib.check_turn_in(e.self, e.trade, {item1=9001}) then
    e.other:QuestReward(e.self, 0, 0, 0, 0, 9002, 1);
  end
end
eq.spawn2(99991, 0, 0, 1, 2, 3, 0);
]]
--[=[
eq.spawn2(99992, 0, 0, 1, 2, 3, 0);
-- A mismatched ]] does not close this long comment.
function discarded_death(e)
  eq.spawn2(99993, 0, 0, 1, 2, 3, 0);
end
]=]

function event_trade(e)
  -- if item_lib.check_turn_in(e.self, e.trade, {item1=9003}) then
  --   e.other:QuestReward(e.self, 0, 0, 0, 0, 9004, 1);
  -- end
  if item_lib.check_turn_in(e.self, e.trade, {item1=1001, platinum=50}) then
    -- e.other:QuestReward(e.self, 0, 0, 0, 0, 99994, 1);
    -- e.other:Faction(e.self, 999, 10);
    e.other:QuestReward(e.self, 0, 0, 0, 0, 2001, 100); -- live reward
  end
end

function event_say(e)
  if e.message:findi("hail") then
    -- e.self:Say("Disabled reply");
    e.self:Say("Hello -- this is dialogue, not a comment.");
  end
end

function event_death(e)
  -- eq.spawn2(99995, 0, 0, 1, 2, 3, 0);
  --[==[ eq.spawn2(99996, 0, 0, 1, 2, 3, 0); ]==]
  eq.spawn2(TARGET, 0, 0, 10, 20, 30, 0); -- live spawn
end

--[=[ eq.register_npc_event("comments", Event.death, 99997, event_death); ]=]
eq.register_npc_event("comments", Event.death, 12001, event_death);

local BOSS_TYPE = 71032;
local SIREN_TYPE = 71058;

function BossDeath(e)
	eq.unique_spawn(SIREN_TYPE,0,0, e.self:GetX(), e.self:GetY(), e.self:GetZ(), e.self:GetHeading());
end

function OtherDeath(e)
	eq.spawn2(71060,48,0,-1462.2,-270.1,1250.9,62.9);
end

function event_encounter_load(e)
	eq.register_npc_event("DeathEnc", Event.death_complete, BOSS_TYPE, BossDeath);
	eq.register_npc_event("DeathEnc", Event.death_complete, 71000, OtherDeath);
end

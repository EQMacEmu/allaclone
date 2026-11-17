-- Classic death handler: literal coords + corpse-relative spawn.
function event_death_complete(e)
	eq.unique_spawn(90016,0,0,28.1,1.4,7,192.5);
	eq.spawn2(36091,0,0,e.self:GetX(),e.self:GetY(),e.self:GetZ(),e.self:GetHeading());
end

# Quest data (Lua → MySQL)

AllaClone serves quests from **MySQL tool tables**, like items and factions. Page loads never parse Lua or `.inc` files.

## Refresh

1. Apply schema / import from the EQEmu quest tree (Windows PowerShell preferred):

```powershell
cd quests
.\import_lua_quests.ps1 -QuestRoot 'C:\TAKP\quests' -DbHost localhost -DbUser root -DbPass root -DbName peq
```

Optional: `-Zones qeynos,freporte`; `-DryRun` to scan without writing.

PHP CLI alternative (Linux/CI):

```bash
php quests/import_lua_quests.php --quests /path/to/quests --host localhost --user root --pass root --db peq
```

2. Enable in `includes/config.php` (see `config.template.php`):

- `$AllowQuestsNPC = TRUE;`
- `$ItemQuestSource = TRUE;`

## What is extracted (heuristic)

From each zone’s `*.lua` scripts:

- `item_lib.check_turn_in` give-items and nearby `QuestReward` (items, exp, money, faction flag)
- `e.other:Faction(e.self, id, delta)` hits
- `GetFactionValue(e.self) >= N` standing gates (mapped to the NPC’s primary faction)
- `event_say` / `:findi("keyword")` dialogue branches

Scripts are bound to `npc_types.id` via basename ↔ NPC name and literal spawn/signal ids.

Limits: regex-based; may miss concatenated strings, variable item ids, and deep nesting. Treat summaries as approximate.

## Tables

Defined in `includes/sql/quests.sql`. Runtime pages JOIN these to `items`, `npc_types`, `faction_list`, and `zone`.

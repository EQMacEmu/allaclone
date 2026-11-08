# Quest data (Lua → MySQL)

AllaClone serves quests from **MySQL tool tables**, like items and factions. Page loads never parse Lua or `.inc` files.

## Refresh

1. Apply the schema and import from the EQEmu quest tree with the PHP CLI (needs the `mysqli` extension):

```bash
php quests/import_lua_quests.php --quests /path/to/quests --host localhost --user root --pass root --db peq
```

Optional: `--zones qeynos,freporte` to import a subset; `--dry-run` to scan without writing. Use `--pass=` for an empty password. DB settings can also come from `ALLACLONE_DB_HOST`, `ALLACLONE_DB_USER`, `ALLACLONE_DB_PASS` and `ALLACLONE_DB_NAME`.

Each run drops and recreates the `quest_*` tables, so import every zone you want in one run.

On Windows, `quests/import_lua_quests.ps1` wraps the same importer. It uses `php` from `PATH`, or runs inside a PHP container that mounts this checkout at `/var/www/html`:

```powershell
.\quests\import_lua_quests.ps1 -QuestRoot 'D:\eqemu\quests' -Container allaclone-local -DbHost host.docker.internal
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

Scripts are bound to `npc_types.id` the way the server loads them: `<npc_id>.lua`, or `<npc_name>.lua` matched exactly (then case/prefix-insensitively) against NPCs belonging to that zone (id range or a spawn point there), plus ids passed to `eq.register_npc_event`. NPCs a script only spawns or signals are not bound to it.

Quest pages honor `$hide_npc_id` / `$hide_item_id` and the expansion selector (by zone expansion).

Limits: regex-based; may miss concatenated strings, variable item ids, and deep nesting. Treat summaries as approximate.

## Tables

Defined in `includes/sql/quests.sql`. Runtime pages JOIN these to `items`, `npc_types`, `faction_list`, and `zone`.

# Quest data (Lua → MySQL)

AllaClone serves quests from **MySQL tool tables**, like items and factions. Page loads never parse Lua and never execute Perl. If imported DB rows are missing, quest pages may fall back to a pre-generated static `.inc` under `quests/datas/` (include-only; never regenerated at request time).

## Refresh

1. Ensure the schema exists (`includes/sql/quests.sql` is idempotent `CREATE TABLE IF NOT EXISTS`). The importer also applies it safely before loading.

2. Import from the EQEmu quest tree with the PHP CLI (needs the `mysqli` extension):

```bash
php quests/import_lua_quests.php --quests /path/to/quests --host localhost --user root --pass root --db peq
```

Optional:

- `--zones qeynos,freporte` — refresh only those zones; **other zones already in the DB are preserved** (staging merge + RENAME swap).
- `--dry-run` — scan/resolve and report counts; **no DDL and no writes**.
- `--force-incomplete` — allow replacing live data when staging looks empty or far smaller than the previous import (default: refuse).
- `--pass=` for an empty password.

DB settings can also come from `ALLACLONE_DB_HOST`, `ALLACLONE_DB_USER`, `ALLACLONE_DB_PASS` and `ALLACLONE_DB_NAME`.

### Import safety

- Live `quest_*` tables are **not** dropped before load.
- Data is written to `*_stg` staging tables, validated, then cut over with a single `RENAME TABLE` batch.
- MySQL DDL auto-commits; safety is “leave live untouched until swap,” not a multi-statement transaction.
- SQL errors abort the run and drop staging only.
- A `quest_import_meta` row records source path, zone scope, counts, and a content signature.

On Windows, `quests/import_lua_quests.ps1` wraps the same importer:

```powershell
.\quests\import_lua_quests.ps1 -QuestRoot 'D:\eqemu\quests' -Container allaclone-local -DbHost host.docker.internal
```

3. Enable in `includes/config.php` (see `config.template.php`):

- `$AllowQuestsNPC = TRUE;`
- `$ItemQuestSource = TRUE;`

## What is extracted (heuristic)

From each zone’s `*.lua` scripts:

- `item_lib.check_turn_in` give-items, give-currency, and nearby `QuestReward` (items, exp, money, faction flag)
- `e.other:Faction(e.self, id, delta)` hits (attributed to the turn-in branch when inside that branch)
- `GetFactionValue(e.self) >= N` standing gates (mapped to the NPC’s primary faction — **heuristic**)
- `event_say` / `:findi("keyword")` dialogue branches (including spawn/depop-only branches)
- Literal `eq.spawn2` / `eq.unique_spawn` (and `quest::` equivalents) with numeric x/y/z into `quest_spawn`

Scripts bind to `npc_types.id` the way the server loads them: `<npc_id>.lua`, or `<npc_name>.lua` matched against NPCs belonging to that zone (id range or a spawn point there), plus ids passed to `eq.register_npc_event` **that also belong to the zone**.

### Known unsupported patterns (not invented)

- Multiline `{...}` turn-in tables
- Variable / concatenated item ids
- `math.random` / non-literal rewards
- Dynamic spawn coords (`GetX()` etc.)
- Deep nesting beyond the indent heuristic

Treat summaries as approximate. Fixture tests live under `quests/tests/`.

## Tables

Defined in `includes/sql/quests.sql` (`quest_npc`, `quest_trade*`, `quest_faction_*`, `quest_say_branch`, `quest_spawn`, `quest_items`, `quest_import_meta`).

# Quest data (Lua → MySQL)

AllaClone serves quests from **MySQL tool tables**, like items and factions. Page loads never parse Lua and never execute Perl.

### Feature flag

- `$AllowQuestsNPC = FALSE` — **all** quest browsing is disabled (`quests/index.php` redirects). No DB pages and no archived `.inc` fallback.
- `$AllowQuestsNPC = TRUE` — serve Lua-derived `quest_*` DB pages. Name bookmarks (`?npc=…&zone=…`) resolve via imported rows first; if missing, may include a pre-generated static `.inc` from `$quests_datas` (repo default: `quests/datas/`, include-only; never regenerated at request time; never HTTP Perl). Otherwise redirect to the zones list or NPC page.

`$quests_datas` must point at the archived `.inc` tree, not the live Lua `$quests_dir`.

## Refresh

1. Ensure the schema exists (`includes/sql/quests.sql` is idempotent `CREATE TABLE IF NOT EXISTS`). The importer also applies it safely before loading. Legacy live `quest_items` tables that lack `npc_id` (old `PRIMARY KEY (item_id, npc, zone)`) are **not** altered in place: staging is created with the new schema, resolvable rows are copied with an explicit column list, and cutover uses `RENAME TABLE`. Rows whose NPC name cannot be resolved to an `npc_id` are omitted from staging and are discarded only after a successful swap.

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

### Import safety tests

`php quests/tests/run_import_safety_tests.php` **destroys tables** inside a disposable database (DROPs reference + `quest_*` / staging / old tables).

- Set `ALLACLONE_QUEST_TEST_DB` only to an explicitly disposable name that starts with `allaclone_test_` (default: `allaclone_test_quest`).
- Names equal to `ALLACLONE_PEQ_DB` / `ALLACLONE_DB_NAME`, or production-like names (`peq`, `allaclone`, `information_schema`, …), are refused **before** any destructive SQL.

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

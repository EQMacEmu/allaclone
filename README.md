Fork of EQEmu AllaClone, customized for The Al'kabor Project.

For installation instructions, see wiki section.

## PHP 8.3 / mysqli upgrade notes

Existing installs that still use a `config.php` copied from an older `mysql_*` template must update manually (this repo gitignores `includes/config.php`):

1. Merge changes from `includes/config.template.php` into your local `includes/config.php` (or re-copy the template and restore your `$db*`, `$root_url`, and `$quests_*` values).
2. Replace `mysql_connect` / `mysql_select_db` with `mysqli_connect(...)` plus `mysqli_set_charset($db, 'latin1')`, and derive `$PHP_SELF` from validated `$_SERVER['SCRIPT_NAME']` (see `config.template.php`; rejects `..` / attribute-breaking characters; falls back to script basename for nested installs).
3. Prefer `__DIR__` for `$includes_dir` / maps / icons paths so nested pages resolve correctly.
4. Deploy under PHP 8.x with the mysqli extension enabled.
5. Legacy Perl quest generation is offline-only: HTTP quest pages no longer call `system(perl)`. If you still run `quests/parse_quest.pl` offline, set `ALLACLONE_DB_PASS` (and related env vars) instead of embedding credentials.
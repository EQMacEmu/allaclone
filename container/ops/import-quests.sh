#!/bin/bash
# Container-side maintenance; the existing application importer owns its schema.
set -euo pipefail
umask 077
[[ $# == 2 ]] || { echo 'Run ./maintenance update-quests [--local] on the host.' >&2; exit 1; }
exec 8>/app/run/database-update.lock
flock -n 8 || { echo 'Database or quest maintenance is already running.' >&2; exit 1; }

state=/app/data/quests
mkdir -p "$state"
work=$(mktemp -d "$state/.update.XXXXXX")
supervisor=(supervisorctl -c /app/config/supervisord.conf)
php_paused=0
import_started=0
cleanup() {
    result=$?
    trap - EXIT
    if (( result == 0 )); then
        rm -rf -- "$work"
    else
        echo "Quest update failed. Logs and any backup retained in $work" >&2
        if (( import_started )); then
            "${supervisor[@]}" stop php-fpm >/dev/null 2>&1 || true
            echo 'PHP-FPM remains stopped. Correct the failure and rerun update-quests.' >&2
        elif (( php_paused )); then
            "${supervisor[@]}" start php-fpm || true
        fi
    fi
    exit "$result"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
printf '%s\n' "$1" > "$work/quest-revision.txt"
printf '%s\n' "$2" > "$work/importer-revision.txt"

# This scans the Lua files and checks reference-table access without SQL writes.
database=$(php /app/ops/quests.php --database)
echo 'Checking quest source and reference data (dry run)...'
php /app/ops/quests.php --quests /app/source/quests --dry-run > "$work/dry-run.log" 2>&1
php_status=$("${supervisor[@]}" status php-fpm || true)
case "$php_status" in
    *' RUNNING '*) php_paused=1; "${supervisor[@]}" stop php-fpm ;;
    *' STOPPED '*) ;; # Recovery after a failed import.
    *) echo 'PHP-FPM must be RUNNING or STOPPED before importing.' >&2; exit 1 ;;
esac
echo 'Saving the current application database...'
mariadb-dump --defaults-file=/app/config/mariadb.cnf --user=root \
    --routines --events --triggers "$database" > "$work/before.sql" 2> "$work/backup.log"
echo 'Importing quest data...'
import_started=1
php /app/ops/quests.php --quests /app/source/quests > "$work/import.log" 2>&1
php /app/ops/quests.php --verify > "$work/result.json"
"${supervisor[@]}" start php-fpm

# Keep one completed backup/report/log set; failed attempts retain their own files.
mv -f "$work/before.sql" "$state/previous.sql"
mv -f "$work/result.json" "$state/last-import.json"
mv -f "$work/import.log" "$state/latest-import.log"
mv -f "$work/dry-run.log" "$state/latest-dry-run.log"
mv -f "$work/quest-revision.txt" "$state/quest-revision.txt"
mv -f "$work/importer-revision.txt" "$state/importer-revision.txt"
echo "Quest update complete. PHP-FPM is running. Report: $state/last-import.json"
echo 'Enable AllowQuestsNPC, ItemQuestSource and AllowEpics in config/application.php after the first successful import.'

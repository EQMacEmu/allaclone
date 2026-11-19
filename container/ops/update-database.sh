#!/bin/bash
# Called by host-side maintenance with its downloaded ZIP outside the web root.
set -euo pipefail
umask 077

if [[ $# != 2 || ! -f /app/config/application.php || ! -f $2 ]]; then
    echo 'Run maintenance setup first, then maintenance update-database [ZIP_URL].' >&2
    exit 1
fi

# Keep the import serialized even if its host docker-exec client disconnects.
exec 8>/app/run/database-update.lock
flock -n 8 || { echo 'A database update is already running.' >&2; exit 1; }

state=/app/data/database
mkdir -p "$state"
work=$(mktemp -d "$state/.update.XXXXXX")
php_paused=0
import_started=0
supervisor=(supervisorctl -c /app/config/supervisord.conf)
cleanup() {
    result=$?
    trap - EXIT
    rm -f "$work/client.cnf"
    if (( result == 0 )); then
        rm -rf -- "$work"
    else
        echo "Database update failed. Files retained in $work" >&2
        if (( php_paused && ! import_started )); then
            "${supervisor[@]}" start php-fpm || true
        elif (( import_started )); then
            echo 'PHP-FPM remains stopped. Correct the failure and rerun update-database.' >&2
        fi
    fi
    exit "$result"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

printf '%s\n' "$1" > "$work/source-url.txt"
mv -- "$2" "$work/archive.zip"
unzip -tqq "$work/archive.zip"
members=$(unzip -Z1 "$work/archive.zip")
mapfile -t drops < <(printf '%s\n' "$members" | grep -E '(^|/)drop_system\.sql$')
mapfile -t dumps < <(printf '%s\n' "$members" | grep -E '(^|/)alkabor[^/]*\.sql$')
if (( ${#drops[@]} != 1 || ${#dumps[@]} != 1 )); then
    echo 'Expected one drop_system.sql and one alkabor*.sql file in the ZIP.' >&2
    exit 1
fi
for member in "${drops[0]}" "${dumps[0]}"; do
    if [[ ! "$member" =~ ^[A-Za-z0-9_./-]+$ ]]; then
        echo 'Unexpected SQL member name in the ZIP.' >&2
        exit 1
    fi
done
if [[ "$(dirname -- "${drops[0]}")" != "$(dirname -- "${dumps[0]}")" ]]; then
    echo 'The drop script and reference dump must be in the same ZIP directory.' >&2
    exit 1
fi
# Extract only selected members to fixed paths, never archive-supplied paths.
unzip -p "$work/archive.zip" "${drops[0]}" > "$work/drop.sql"
unzip -p "$work/archive.zip" "${dumps[0]}" > "$work/alkabor.sql"
sha256sum "$work/archive.zip" | cut -d ' ' -f 1 > "$work/archive.sha256"
printf '%s\n' "${dumps[0]}" > "$work/source-member.txt"
echo "Checking ${dumps[0]}..."
php /app/ops/database.php prepare "$work"

# Finish downloads, validation and login checks before interrupting PHP requests.
php_status=$("${supervisor[@]}" status php-fpm || true)
case "$php_status" in
    *' RUNNING '*) php_paused=1; "${supervisor[@]}" stop php-fpm ;;
    *' STOPPED '*) ;; # A failed prior import can be retried directly.
    *) echo 'PHP-FPM must be RUNNING or STOPPED before importing.' >&2; exit 1 ;;
esac

database=$(cat "$work/database.txt")
if (( $(cat "$work/existing-table-count.txt") > 0 )); then
    echo 'Saving the current application database before replacement...'
    mariadb-dump --defaults-file=/app/config/mariadb.cnf --user=root \
        --single-transaction --routines --events --triggers "$database" \
        > "$work/before.sql" 2> "$work/backup.log"
fi

echo 'Dropping and importing the checked reference tables...'
import_started=1
mariadb --defaults-file="$work/client.cnf" --binary-mode \
    < "$work/drop.sql" > "$work/import.log" 2>&1
mariadb --defaults-file="$work/client.cnf" --binary-mode \
    < "$work/alkabor.sql" >> "$work/import.log" 2>&1
php /app/ops/database.php verify "$work"

# Keep one successful download/report and one pre-update backup; failed work stays.
mv -f "$work/archive.zip" "$state/latest.zip"
mv -f "$work/archive.sha256" "$state/latest.sha256"
mv -f "$work/source-url.txt" "$state/latest-url.txt"
mv -f "$work/source-member.txt" "$state/latest-member.txt"
mv -f "$work/result.json" "$state/last-import.json"
if [[ -f "$work/before.sql" ]]; then
    mv -f "$work/before.sql" "$state/previous.sql"
fi
"${supervisor[@]}" start php-fpm
echo 'Database update complete. PHP-FPM is running; imported quests should be refreshed next.'

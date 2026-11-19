#!/bin/bash
# Host-side helper, called with the maintenance lock already held.
set -euo pipefail
umask 077
source_dir=$(cd .. && pwd)
compose=(docker compose --project-directory "$PWD" -f compose.yaml)
supervisor=("${compose[@]}" exec -T --user root allaclone supervisorctl -c /app/config/supervisord.conf)

[[ -f config/application.php ]] || { echo 'Run ./maintenance setup first.' >&2; exit 1; }
for command in git rsync tar; do command -v "$command" >/dev/null; done
# A disconnected database import may still be running inside the container.
"${compose[@]}" exec -T --user root allaclone flock -n /app/run/database-update.lock true

[[ $(git -C "$source_dir" rev-parse --show-toplevel) == "$source_dir" ]] || {
    echo 'Place container/ in the root of an Allaclone Git checkout.' >&2; exit 1;
}
[[ -z $(git -C "$source_dir" ls-files -u) ]] || { echo 'Resolve Git conflicts first.' >&2; exit 1; }
# Ignore container state when checking application paths.
if [[ -n $(find "$source_dir" \( -name .git -o -path "$source_dir/container" \) -prune -o -type l -print -quit) ]] ||
    git -C "$source_dir" ls-files --stage -- . ':!container' | grep '^160000 ' >/dev/null; then
    echo 'Source symlinks and submodules are not supported by this exporter.' >&2; exit 1
fi

mkdir -p data/application
work=$(mktemp -d data/application/.update.XXXXXX)
php_paused=0
copy_started=0
cleanup() {
    result=$?
    trap - EXIT
    if (( result == 0 )); then
        rm -rf -- "$work"
    else
        echo "Deployment failed. Export, logs and any backup retained in $work" >&2
        if (( copy_started )); then
            "${supervisor[@]}" stop php-fpm >/dev/null 2>&1 || true
            echo 'PHP-FPM remains stopped. Correct the failure and rerun update-allaclone.' >&2
        elif (( php_paused )); then
            "${supervisor[@]}" start php-fpm || true
        fi
    fi
    exit "$result"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# Export tracked working files: git add new files first; tracked deletions are kept.
# Keep runtime includes/guide helpers and archived quests, but no CLI/import tools.
git -C "$source_dir" ls-files -z > "$work/tracked-files"
while IFS= read -r -d '' file; do
    [[ -f $source_dir/$file ]] || continue
    case "$file" in
        .*|*/.*|*~|*.bak|*.old|*.orig|*.save|*.swp|container/*|tests/*|includes/config*|includes/sql/*|includes/scss/*) continue ;;
        quests/index.php|quests/zones.php|quests/datas/*.inc) ;;
        quests/*) continue ;;
        fonts/*.woff2|fonts/OFL.txt) ;;
        *.php|*.js|*.css|*.gif|*.png|*.jpg|*.JPG|*.jpeg|*.svg|*.ico|map_txt/*.txt|maps/*) ;;
        *) continue ;;
    esac
    printf '%s\0' "$file"
done < "$work/tracked-files" > "$work/runtime-files"
mkdir "$work/www"
rsync -r --from0 --files-from="$work/runtime-files" "$source_dir/" "$work/www/"
for file in index.php item.php spell.php zone.php includes/mysql.php includes/headers.php; do
    [[ -s $work/www/$file ]] || { echo "Missing required application file: $file" >&2; exit 1; }
done
install -m 0644 init/application-config.php "$work/www/includes/config.php"
"${compose[@]}" exec -T --user root allaclone bash -c \
    'set -o pipefail; find "$1" -type f -name "*.php" -print0 | xargs -0 -r -n 1 php -l' \
    bash "/app/$work/www" > "$work/php-lint.log" 2>&1
git -C "$source_dir" rev-parse HEAD > "$work/revision.txt"
git -C "$source_dir" diff --stat HEAD > "$work/local-changes.txt"

# Prepare everything before the brief PHP maintenance window. nginx stays running.
php_status=$("${supervisor[@]}" status php-fpm || true)
case "$php_status" in
    *' RUNNING '*) php_paused=1; "${supervisor[@]}" stop php-fpm ;;
    *' STOPPED '*) ;; # Retry after a failed deployment.
    *) echo 'PHP-FPM must be RUNNING or STOPPED before deployment.' >&2; exit 1 ;;
esac
tar -cf "$work/before.tar" -C www .
copy_started=1
rsync -r --checksum --delete --perms --chmod=D755,F644 \
    --owner --group --chown=root:root "$work/www/" www/
"${supervisor[@]}" start php-fpm
status=$("${compose[@]}" exec -T --user root allaclone curl --silent --show-error \
    --max-time 15 --output "/app/$work/home.html" --write-out '%{http_code}' http://127.0.0.1:8081/)
if [[ $status != 200 ]] || ! grep -q 'id="box-art"' "$work/home.html"; then
    echo 'The deployed application did not return its homepage with HTTP 200.' >&2; exit 1
fi
# Promote the backup only after success; a failed attempt keeps its own before.tar.
mv -f "$work/before.tar" data/application/previous.tar
mv -f "$work/revision.txt" data/application/revision.txt
mv -f "$work/local-changes.txt" data/application/local-changes.txt
echo 'Application deployment complete. PHP-FPM is running; database and quests are unchanged.'

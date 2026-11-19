#!/bin/bash
# Host-side checkout update; maintenance already holds the host lock.
set -euo pipefail
umask 077
source_dir=source/quests
app_dir=$(cd .. && pwd)
compose=(docker compose --project-directory "$PWD" -f compose.yaml)

[[ -f config/application.php && -f $app_dir/quests/import_lua_quests.php ]] || {
    echo 'Run setup, update-database and update-allaclone before importing quests.' >&2; exit 1;
}
command -v git >/dev/null
# Also detect a previous import whose host Docker client has disconnected.
"${compose[@]}" exec -T --user root allaclone flock -n /app/run/database-update.lock true
if [[ ! -d $source_dir ]]; then
    [[ ${1:-} != --local ]] || { echo 'No quest checkout; run without --local first.' >&2; exit 1; }
    mkdir -p source
    git clone --branch main https://github.com/EQMacEmu/quests.git "$source_dir"
fi
for repo in "$PWD/$source_dir" "$app_dir"; do
    [[ $(git -C "$repo" rev-parse --show-toplevel) == "$repo" ]] || {
        echo "$repo must be its own Git checkout." >&2; exit 1;
    }
    [[ -z $(git -C "$repo" ls-files -u) ]] || { echo "Resolve conflicts in $repo first." >&2; exit 1; }
done
if [[ ${1:-} != --local ]]; then
    [[ -z $(git -C "$source_dir" status --porcelain) ]] || {
        echo 'Quest source has local changes. Commit them or use --local.' >&2; exit 1;
    }
    [[ $(git -C "$source_dir" branch --show-current) == main ]] || {
        echo 'Automatic quest updates require main; use --local for another branch.' >&2; exit 1;
    }
    git -C "$source_dir" fetch origin main
    git -C "$source_dir" merge-base --is-ancestor HEAD origin/main || {
        echo 'Quest commits differ from origin/main. Review them and use --local.' >&2; exit 1;
    }
    git -C "$source_dir" merge --ff-only origin/main
fi
if [[ -n $(find "$source_dir" -name .git -prune -o -type l -print -quit) ]] ||
    git -C "$source_dir" ls-files --stage | grep '^160000 ' >/dev/null; then
    echo 'Quest source symlinks and submodules are not supported.' >&2; exit 1
fi
quest_revision=$(git -C "$source_dir" rev-parse HEAD)
importer_revision=$(git -C "$app_dir" rev-parse HEAD)
[[ -z $(git -C "$source_dir" status --porcelain) ]] || quest_revision+='+local'
[[ -z $(git -C "$app_dir" status --porcelain) ]] || importer_revision+='+local'
"${compose[@]}" exec -T --user root allaclone bash /app/ops/import-quests.sh \
    "$quest_revision" "$importer_revision"

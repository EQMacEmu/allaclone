#!/bin/bash
# Bootstrap signed Debian packages and build one Allaclone image.
set -euo pipefail
umask 022

here=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
if (( $# )); then
    echo 'Usage: ./build.sh (edit snapshot to change the package date)' >&2
    exit 2
fi
if (( EUID != 0 )); then
    echo 'Run as root; mmdebstrap creates a temporary Debian filesystem.' >&2
    exit 1
fi
for tool in mmdebstrap docker dpkg date flock; do
    command -v "$tool" >/dev/null || { echo "Missing build tool: $tool" >&2; exit 1; }
done
if [[ $(dpkg --print-architecture) != amd64 ]]; then
    echo 'This build requires Debian amd64.' >&2
    exit 1
fi
keyring=/usr/share/keyrings/debian-archive-keyring.gpg
[[ -r $keyring ]] || { echo "Missing $keyring" >&2; exit 1; }
snapshot=$(cat "$here/snapshot")
if [[ ! $snapshot =~ ^[0-9]{8}T[0-9]{6}Z$ ]]; then
    echo 'snapshot must contain one UTC timestamp, e.g. 20261117T000000Z.' >&2
    exit 1
fi
export SOURCE_DATE_EPOCH
SOURCE_DATE_EPOCH=$(date -u -d "${snapshot:0:4}-${snapshot:4:2}-${snapshot:6:2} ${snapshot:9:2}:${snapshot:11:2}:${snapshot:13:2}" +%s)
export TZ=Etc/UTC
image="takp/allaclone:trixie-$snapshot"
docker info >/dev/null
docker buildx version >/dev/null

# Serialize builds of this local image; scratch files never enter the repository.
exec 9>/run/lock/allaclone-build.lock
flock 9
mkdir -p "$here/build"
work=$(mktemp -d "$here/build/image.XXXXXXXX")
trap 'rm -rf -- "$work"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
chmod 0755 "$work"
mkdir "$work/tmp"
export TMPDIR="$work/tmp"

# Only expiry checks are disabled for historical snapshots; signatures stay checked.
printf '%s\n' \
    "deb [check-valid-until=no] http://snapshot.debian.org/archive/debian/${snapshot}/ trixie main" \
    "deb [check-valid-until=no] http://snapshot.debian.org/archive/debian/${snapshot}/ trixie-updates main" \
    "deb [check-valid-until=no] http://snapshot.debian.org/archive/debian-security/${snapshot}/ trixie-security main" \
    > "$work/sources.list"

mmdebstrap \
    --mode=root --architectures=amd64 --variant=apt \
    --include=ca-certificates,debian-archive-keyring,passwd,tzdata \
    --keyring="$keyring" --skip=output/dev \
    --customize-hook='rm -f "$1/etc/hostname" "$1/etc/hosts" "$1/etc/resolv.conf"' \
    trixie "$work/rootfs.tar" "$work/sources.list"

# Only the bootstrapped filesystem enters the Docker build context.
printf '*\n!rootfs.tar\n' > "$work/.dockerignore"
docker build --builder default --network host \
    --build-arg "SNAPSHOT=$snapshot" \
    --file "$here/Dockerfile" --tag "$image" "$work"
printf 'Built %s\n' "$image"

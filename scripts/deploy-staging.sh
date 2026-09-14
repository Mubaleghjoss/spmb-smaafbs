#!/usr/bin/env bash
set -euo pipefail

STAGING_ROOT="${STAGING_ROOT:-/var/www/spmb-staging}"
RELEASES_DIR="${RELEASES_DIR:-$STAGING_ROOT/releases}"
SHARED_DIR="${SHARED_DIR:-$STAGING_ROOT/shared}"
SHARED_ENV="${SHARED_ENV:-$SHARED_DIR/.env}"
SHARED_STORAGE="${SHARED_STORAGE:-$SHARED_DIR/storage}"
CURRENT_LINK="${CURRENT_LINK:-$STAGING_ROOT/current}"
STAGING_URL="${STAGING_URL:-https://staging-seleksi.smaafbs.sch.id}"
LOCK_DIR="$STAGING_ROOT/.deploy.lock"
SWITCHED=false
PREVIOUS_RELEASE=""

fail() { echo "Error: $*" >&2; exit 1; }

# Staging variables are overridable for controlled test environments, never for production targets.
for value in "$STAGING_ROOT" "$RELEASES_DIR" "$SHARED_DIR" "$SHARED_ENV" "$SHARED_STORAGE" "$CURRENT_LINK" "$STAGING_URL"; do
    [[ "$value" != *"seleksi.smaafbs.sch.id" || "$value" == "$STAGING_URL" ]] || fail "Refusing production domain in staging configuration."
    [[ "$value" != *production* && "$value" != /home/sman5479/* && "$value" != /var/www/spmb && "$value" != /var/www/spmb/* ]] || fail "Refusing production path or domain: $value"
done
[[ "$STAGING_URL" == "https://staging-seleksi.smaafbs.sch.id" ]] || fail "STAGING_URL must be the staging URL."
[[ "$RELEASES_DIR" == "$STAGING_ROOT/releases" && "$SHARED_DIR" == "$STAGING_ROOT/shared" ]] || fail "Staging layout is inconsistent."

rollback_after_failure() {
    local status=$?
    if [[ "$status" -ne 0 ]] && "$SWITCHED" && [[ -n "$PREVIOUS_RELEASE" && -d "$PREVIOUS_RELEASE" ]]; then
        echo "Deployment failed; restoring previous release." >&2
        ln -sfn "$PREVIOUS_RELEASE" "$STAGING_ROOT/current.rollback.tmp"
        mv -Tf "$STAGING_ROOT/current.rollback.tmp" "$CURRENT_LINK" || true
    fi
    [[ -d "$LOCK_DIR" ]] && rmdir "$LOCK_DIR" || true
    exit "$status"
}
trap rollback_after_failure EXIT
trap 'exit 1' INT TERM

[[ $# -ge 1 ]] || fail "Usage: $0 <COMMIT_SHA> | $0 --rollback <PREVIOUS_SHA>"
if [[ "$1" == "--rollback" ]]; then
    [[ $# -eq 2 && "$2" =~ ^[0-9a-fA-F]{40}$ ]] || fail "Usage: $0 --rollback <PREVIOUS_SHA>"
else
    [[ $# -eq 1 && "$1" =~ ^[0-9a-fA-F]{40}$ ]] || fail "COMMIT_SHA must be a 40-character SHA."
fi
mkdir -p "$RELEASES_DIR" "$SHARED_DIR"
mkdir "$LOCK_DIR" 2>/dev/null || fail "Another staging deployment is already running."

if [[ "$1" == "--rollback" ]]; then
    [[ $# -eq 2 ]] || fail "Usage: $0 --rollback <PREVIOUS_SHA>"
    [[ "$2" =~ ^[0-9a-fA-F]{40}$ ]] || fail "Rollback SHA must be a 40-character SHA."
    rollback_dir="$RELEASES_DIR/${2,,}"
    [[ -d "$rollback_dir" ]] || fail "Rollback release does not exist: ${2,,}"
    ln -sfn "$rollback_dir" "$STAGING_ROOT/current.tmp"
    mv -Tf "$STAGING_ROOT/current.tmp" "$CURRENT_LINK"
    echo "Rolled back staging to ${2,,}."
    exit 0
fi

[[ $# -eq 1 ]] || fail "Usage: $0 <COMMIT_SHA>"
[[ "$1" =~ ^[0-9a-fA-F]{40}$ ]] || fail "COMMIT_SHA must be a 40-character SHA."
repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || fail "Must run from a git repository."
release_sha="$(git -C "$repo_root" rev-parse --verify "${1}^{commit}" 2>/dev/null)" || fail "SHA is not a commit in this repository."
release_dir="$RELEASES_DIR/$release_sha"

[[ -f "$SHARED_ENV" ]] || fail "Required shared staging .env is missing: $SHARED_ENV"
env_value() {
    awk -v key="$1" '$0 ~ "^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=" { sub("^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=", ""); gsub(/^[[:space:]]+|[[:space:]]+$/, ""); if ((substr($0,1,1)=="\"" && substr($0,length($0),1)=="\"") || (substr($0,1,1)=="\047" && substr($0,length($0),1)=="\047")) print substr($0,2,length($0)-2); else print; exit }' "$SHARED_ENV"
}
[[ "$(env_value APP_ENV)" == "staging" ]] || fail "Shared .env APP_ENV must be staging."
[[ "$(env_value APP_URL)" == "$STAGING_URL" ]] || fail "Shared .env APP_URL must be $STAGING_URL."

if [[ -e "$CURRENT_LINK" ]]; then
    PREVIOUS_RELEASE="$(readlink -f "$CURRENT_LINK" || true)"
fi
if [[ -e "$release_dir" ]]; then
    fail "Release already exists: $release_sha"
fi
mkdir -p "$release_dir"
git -C "$repo_root" archive "$release_sha" | tar -x -C "$release_dir"
ln -s "$SHARED_ENV" "$release_dir/.env"
mkdir -p "$SHARED_STORAGE/app/public" "$SHARED_STORAGE/framework/cache/data" "$SHARED_STORAGE/framework/sessions" "$SHARED_STORAGE/framework/views" "$SHARED_STORAGE/logs"
rm -rf "$release_dir/storage" "$release_dir/public/storage"
ln -s "$SHARED_STORAGE" "$release_dir/storage"
ln -s "$SHARED_STORAGE/app/public" "$release_dir/public/storage"

cd "$release_dir"
command -v composer >/dev/null 2>&1 || fail "composer is required."
composer install --no-dev --optimize-autoloader --no-interaction
if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
    npm ci
    npm run build
fi
php artisan optimize:clear --ansi
php artisan migrate --force --ansi
php artisan optimize --ansi

ln -sfn "$release_dir" "$STAGING_ROOT/current.tmp"
mv -Tf "$STAGING_ROOT/current.tmp" "$CURRENT_LINK"
SWITCHED=true

# Keep the current release and four prior releases.
mapfile -t old_releases < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | tail -n +6 | cut -d' ' -f2-)
for old_release in "${old_releases[@]}"; do
    rm -rf -- "$old_release"
done

echo "Staging deployed: $release_sha"

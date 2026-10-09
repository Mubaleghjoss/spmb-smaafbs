#!/usr/bin/env bash
set -euo pipefail

STAGING_ROOT="${STAGING_ROOT:-/var/www/spmb-staging}"
RELEASES_DIR="${RELEASES_DIR:-$STAGING_ROOT/releases}"
SHARED_DIR="${SHARED_DIR:-$STAGING_ROOT/shared}"
SHARED_ENV="${SHARED_ENV:-$SHARED_DIR/.env}"
SHARED_STORAGE="${SHARED_STORAGE:-$SHARED_DIR/storage}"
CURRENT_LINK="${CURRENT_LINK:-$STAGING_ROOT/current}"
STAGING_URL="${STAGING_URL:-https://staging-seleksi.smaafbs.sch.id}"
DEPLOY_LOG_DIR="${DEPLOY_LOG_DIR:-/home/hermesadmin/logs/spmb/deploy}"
HEALTH_CHECK_LOCAL_URL="${HEALTH_CHECK_LOCAL_URL:-http://127.0.0.1:8083}"
HEALTH_CHECK_PUBLIC_URL="${HEALTH_CHECK_PUBLIC_URL:-https://staging-seleksi.smaafbs.sch.id}"
LOCK_DIR="$STAGING_ROOT/.deploy.lock"

STATUS="FAILED"
STAGE_FAILED=""
COMPOSER_RESULT="SKIPPED"
NPM_BUILD_RESULT="SKIPPED"
MIGRATION_RESULT="SKIPPED"
OPTIMIZE_RESULT="FAILED"
SYMLINK_RESULT="FAILED"
HEALTH_RESULT="FAILED"
ROUTE_CACHE_REBUILT="NO"
ROUTE_CACHE_RESULT="FAIL"
ROUTE_RUNTIME_CHECK="FAIL"
SWITCHED=false
PREVIOUS_RELEASE=""
TARGET_SHA=""
SOURCE=""
START_EPOCH="$(date +%s)"
START_TIMESTAMP="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
LOG_FILE=""

fail() { STAGE_FAILED="${STAGE_FAILED:-validation}"; echo "Error: $*" >&2; exit 1; }

finalize_log() {
    local exit_status="$1" end_epoch end_timestamp duration filename
    end_epoch="$(date +%s)"
    end_timestamp="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    duration=$((end_epoch - START_EPOCH))
    if [[ "$exit_status" -eq 0 ]]; then STATUS="SUCCESS"; fi
    mkdir -p "$DEPLOY_LOG_DIR" || true
    filename="deploy-$(date -u +%Y%m%d-%H%M%S)-${STATUS}.log"
    LOG_FILE="$DEPLOY_LOG_DIR/$filename"
    # Deliberately write only fixed operational metadata, never environment values.
    {
        printf 'timestamp_start=%s\n' "$START_TIMESTAMP"
        printf 'timestamp_end=%s\n' "$end_timestamp"
        printf 'target_sha=%s\n' "$TARGET_SHA"
        printf 'previous_sha=%s\n' "${PREVIOUS_RELEASE##*/}"
        printf 'branch/source=%s\n' "$SOURCE"
        printf 'composer_result=%s\n' "$COMPOSER_RESULT"
        printf 'npm_build_result=%s\n' "$NPM_BUILD_RESULT"
        printf 'migration_command_executed=NO\n'
        printf 'migration_result=%s\n' "$MIGRATION_RESULT"
        printf 'artisan_optimize_result=%s\n' "$OPTIMIZE_RESULT"
        printf 'route_cache_rebuilt=%s\n' "$ROUTE_CACHE_REBUILT"
        printf 'route_cache_result=%s\n' "$ROUTE_CACHE_RESULT"
        printf 'route_runtime_check=%s\n' "$ROUTE_RUNTIME_CHECK"
        printf 'symlink_switch=%s\n' "$SYMLINK_RESULT"
        printf 'health_check=%s\n' "$HEALTH_RESULT"
        printf 'deployment_status=%s\n' "$STATUS"
        printf 'duration_seconds=%s\n' "$duration"
        [[ "$STATUS" == "FAILED" ]] && printf 'stage_failed=%s\n' "${STAGE_FAILED:-unknown}"
    } > "$LOG_FILE" || true
    # Retain only the most recent 30 audit records.
    mapfile -t old_logs < <(find "$DEPLOY_LOG_DIR" -maxdepth 1 -type f -name 'deploy-*.log' -printf '%T@ %p\n' 2>/dev/null | sort -nr | tail -n +31 | cut -d' ' -f2-)
    ((${#old_logs[@]})) && rm -f -- "${old_logs[@]}" || true
}

rollback_after_failure() {
    local exit_status="$1"
    if [[ "$exit_status" -ne 0 ]] && "$SWITCHED" && [[ -n "$PREVIOUS_RELEASE" && -d "$PREVIOUS_RELEASE" ]]; then
        echo "Deployment failed; restoring previous release." >&2
        ln -sfn "$PREVIOUS_RELEASE" "$STAGING_ROOT/current.rollback.tmp"
        mv -Tf "$STAGING_ROOT/current.rollback.tmp" "$CURRENT_LINK" || true
    fi
    [[ -d "$LOCK_DIR" ]] && rmdir "$LOCK_DIR" || true
    finalize_log "$exit_status"
    exit "$exit_status"
}
trap 'rollback_after_failure "$?"' EXIT
trap 'exit 1' INT TERM

for value in "$STAGING_ROOT" "$RELEASES_DIR" "$SHARED_DIR" "$SHARED_ENV" "$SHARED_STORAGE" "$CURRENT_LINK" "$STAGING_URL"; do
    [[ "$value" != *"seleksi.smaafbs.sch.id" || "$value" == "$STAGING_URL" ]] || fail "Refusing production domain in staging configuration."
    [[ "$value" != *production* && "$value" != /home/sman5479/* && "$value" != /var/www/spmb && "$value" != /var/www/spmb/* ]] || fail "Refusing production path or domain: $value"
done
[[ "$STAGING_URL" == "https://staging-seleksi.smaafbs.sch.id" ]] || fail "STAGING_URL must be the staging URL."
[[ "$RELEASES_DIR" == "$STAGING_ROOT/releases" && "$SHARED_DIR" == "$STAGING_ROOT/shared" ]] || fail "Staging layout is inconsistent."
[[ $# -eq 1 && "$1" =~ ^[0-9a-fA-F]{40}$ ]] || fail "Usage: $0 <COMMIT_SHA>"

mkdir -p "$RELEASES_DIR" "$SHARED_DIR" "$DEPLOY_LOG_DIR"
mkdir "$LOCK_DIR" 2>/dev/null || fail "Another staging deployment is already running."
repo_root="$(git rev-parse --show-toplevel 2>/dev/null)" || fail "Must run from a git repository."
TARGET_SHA="$(git -C "$repo_root" rev-parse --verify "${1}^{commit}" 2>/dev/null)" || fail "SHA is not a commit in this repository."
SOURCE="$(git -C "$repo_root" branch --show-current 2>/dev/null || true)"
[[ -n "$SOURCE" ]] || SOURCE="detached"
release_dir="$RELEASES_DIR/$TARGET_SHA"

[[ -f "$SHARED_ENV" ]] || fail "Required shared staging .env is missing: $SHARED_ENV"
env_value() { awk -v key="$1" '$0 ~ "^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=" { sub("^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=", ""); gsub(/^[[:space:]]+|[[:space:]]+$/, ""); print; exit }' "$SHARED_ENV"; }
[[ "$(env_value APP_ENV)" == "staging" ]] || fail "Shared .env APP_ENV must be staging."
[[ "$(env_value APP_URL)" == "$STAGING_URL" ]] || fail "Shared .env APP_URL must be $STAGING_URL."
[[ -e "$CURRENT_LINK" ]] && PREVIOUS_RELEASE="$(readlink -f "$CURRENT_LINK" || true)"
[[ ! -e "$release_dir" ]] || fail "Release already exists: $TARGET_SHA"

route_names_from_json() {
    python3 -c '
import json, sys
try:
    payload = json.load(sys.stdin)
    rows = payload if isinstance(payload, list) else payload.get("routes", [])
    names = sorted({str(row.get("name")) for row in rows if isinstance(row, dict) and row.get("name") and not str(row.get("name")).startswith("generated::")})
except (ValueError, AttributeError, TypeError):
    raise SystemExit(1)
print("\n".join(names))
'
}

verify_runtime_routes() {
    local app_root="$1" expected_names="$2" runtime_output runtime_names diff_output explicit
    runtime_output="$(cd "$app_root" && php artisan route:list --json 2>/dev/null)" || {
        echo "Laravel route:list --json failed or is unsupported." >&2
        return 1
    }
    runtime_names="$(route_names_from_json <<<"$runtime_output")" || {
        echo "Laravel route:list --json returned invalid JSON." >&2
        return 1
    }
    diff_output="$(comm -3 <(printf '%s\n' "$expected_names") <(printf '%s\n' "$runtime_names"))"
    if [[ -n "$diff_output" ]]; then
        echo "Route set mismatch between expected Laravel runtime sets:" >&2
        printf '%s\n' "$diff_output" >&2
        return 1
    fi
    for explicit in peserta.dashboard peserta.akun.username peserta.akun.password; do
        grep -Fxq "$explicit" <<<"$runtime_names" || {
            echo "Missing required runtime route: $explicit" >&2
            return 1
        }
    done
    return 0
}

STAGE_FAILED="archive"
mkdir -p "$release_dir"
git -C "$repo_root" archive "$TARGET_SHA" | tar -x -C "$release_dir"
ln -s "$SHARED_ENV" "$release_dir/.env"
mkdir -p "$SHARED_STORAGE/app/public" "$SHARED_STORAGE/framework/cache/data" "$SHARED_STORAGE/framework/sessions" "$SHARED_STORAGE/framework/views" "$SHARED_STORAGE/logs"
rm -rf "$release_dir/storage" "$release_dir/public/storage"
ln -s "$SHARED_STORAGE" "$release_dir/storage"
ln -s "$SHARED_STORAGE/app/public" "$release_dir/public/storage"
cd "$release_dir"

STAGE_FAILED="composer"
command -v composer >/dev/null 2>&1 || fail "composer is required."
composer install --no-dev --optimize-autoloader --no-interaction
COMPOSER_RESULT="SUCCESS"
STAGE_FAILED="npm_build"
if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then npm ci && npm run build; NPM_BUILD_RESULT="SUCCESS"; fi
STAGE_FAILED="migration"
[[ "${DEPLOY_MIGRATIONS:-0}" == "0" ]] || fail "Migrations are disabled for staging deployment."
MIGRATION_RESULT="SKIPPED"
STAGE_FAILED="artisan_optimize"
php artisan optimize:clear --ansi && php artisan optimize --ansi
OPTIMIZE_RESULT="SUCCESS"
STAGE_FAILED="route_cache"
php artisan route:clear --ansi
uncached_route_output="$(php artisan route:list --json 2>/dev/null)" || fail "Uncached Laravel route:list --json failed."
uncached_route_names="$(route_names_from_json <<<"$uncached_route_output")" || fail "Uncached Laravel route:list --json returned invalid JSON."
[[ -n "$uncached_route_names" ]] || fail "Uncached Laravel route set is empty."
php artisan route:cache --ansi
ROUTE_CACHE_REBUILT="YES"
ROUTE_CACHE_RESULT="PASS"
STAGE_FAILED="route_runtime_check"
verify_runtime_routes "$release_dir" "$uncached_route_names" || fail "Pre-switch runtime route verification failed."
STAGE_FAILED="health_check"
[[ -f public/build/manifest.json ]] || fail "Built asset manifest is missing."

STAGE_FAILED="symlink_switch"
ln -sfn "$release_dir" "$STAGING_ROOT/current.tmp"
mv -Tf "$STAGING_ROOT/current.tmp" "$CURRENT_LINK"
SWITCHED=true
SYMLINK_RESULT="SUCCESS"

# Reload PHP-FPM to flush OPcache for new release if permitted
sudo -n /usr/bin/systemctl reload php8.2-fpm >/dev/null 2>&1 || true

STAGE_FAILED="health_check"
verify_runtime_routes "$CURRENT_LINK" "$uncached_route_names" || fail "Post-switch runtime route verification failed."
ROUTE_RUNTIME_CHECK="PASS"
if [[ "${SKIP_NETWORK_HEALTH_CHECK:-0}" != "1" ]]; then
    [[ "$(curl -s -f -o /dev/null -w '%{http_code}' "$HEALTH_CHECK_LOCAL_URL")" == "200" ]] || fail "Local health check failed."
    public_code="$(curl -sS -o /dev/null -w '%{http_code}' "$HEALTH_CHECK_PUBLIC_URL" || true)"
    [[ "$public_code" == "200" || "$public_code" == "401" ]] || fail "Public health check failed with HTTP $public_code."
fi
HEALTH_RESULT="SUCCESS"
STATUS="SUCCESS"
STAGE_FAILED=""

# Retain current and 4 prior releases (minimum 5 releases)
mapfile -t old_releases < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' 2>/dev/null | sort -nr | tail -n +6 | cut -d' ' -f2-)
for old_release in "${old_releases[@]}"; do
    rm -rf -- "$old_release"
done

echo "Staging deployed: $TARGET_SHA"

#!/usr/bin/env bash
set -euo pipefail

fail() {
    echo "DEPLOY_FAIL: $*" >&2
    exit 1
}

usage() {
    echo "Usage:"
    echo "  $0 <40-char SHA> --preview"
    echo "  PRODUCTION_APPROVAL_SHA=<same SHA> $0 <40-char SHA> --deploy [--allow-migrations]"
    exit 64
}

[[ $# -ge 2 ]] || usage

TARGET_SHA="$1"
shift

[[ "$TARGET_SHA" =~ ^[0-9a-fA-F]{40}$ ]] \
    || fail "Target SHA harus 40 karakter."

MODE=""
ALLOW_MIGRATIONS=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --preview)
            [[ -z "$MODE" ]] || usage
            MODE="preview"
            ;;
        --deploy)
            [[ -z "$MODE" ]] || usage
            MODE="deploy"
            ;;
        --allow-migrations)
            ALLOW_MIGRATIONS=1
            ;;
        *)
            usage
            ;;
    esac
    shift
done

[[ -n "$MODE" ]] || usage

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

PREFLIGHT="$REPO_ROOT/scripts/prod-preflight.sh"
[[ -x "$PREFLIGHT" ]] || fail "prod-preflight.sh tidak ditemukan/executable."

echo "=== PRODUCTION DEPLOYMENT GATE ==="
echo "MODE=$MODE"
echo "TARGET_SHA=$TARGET_SHA"
echo

"$PREFLIGHT" "$TARGET_SHA"

git fetch --quiet origin

CURRENT_PROD_SHA="$(
    ssh rumahweb-smaafbs \
      'cd /home/sman5479/spmb-app && git rev-parse HEAD'
)"

REMOTE_STAGING_SHA="$(
    git ls-remote origin refs/heads/staging | awk '{print $1}'
)"

[[ -n "$REMOTE_STAGING_SHA" ]] \
    || fail "Tidak dapat membaca origin/staging."

git cat-file -e "${TARGET_SHA}^{commit}" \
    || fail "Target SHA tidak tersedia di repo VPS."

git merge-base --is-ancestor "$TARGET_SHA" "$REMOTE_STAGING_SHA" \
    || fail "Target SHA bukan bagian dari origin/staging."

echo
echo "=== DEPENDENCY / ASSET GATE ==="

if ! git diff --quiet "$CURRENT_PROD_SHA" "$TARGET_SHA" -- \
    composer.json composer.lock \
    package.json package-lock.json \
    vite.config.js vite.config.* \
    resources/ \
    public/
then
    echo "Perubahan dependency/frontend/public ditemukan:"
    git diff --name-status "$CURRENT_PROD_SHA" "$TARGET_SHA" -- \
        composer.json composer.lock \
        package.json package-lock.json \
        vite.config.js vite.config.* \
        resources/ \
        public/
    fail "RumahWeb tidak memiliki composer/npm; target ini tidak boleh dideploy dengan pipeline ini."
fi

echo "DEPENDENCY_ASSET_GATE=PASS"

MIGRATION_DIFF="$(
    git diff --name-status \
      "$CURRENT_PROD_SHA" "$TARGET_SHA" \
      -- database/migrations/
)"

echo
echo "=== MIGRATION GATE ==="

if [[ -n "$MIGRATION_DIFF" ]]; then
    echo "MIGRATION_CHANGE=YES"
    printf '%s\n' "$MIGRATION_DIFF"

    if [[ "$MODE" == "deploy" && "$ALLOW_MIGRATIONS" != "1" ]]; then
        fail "Target memiliki migration. Gunakan --allow-migrations setelah review."
    fi
else
    echo "MIGRATION_CHANGE=NO"
fi

echo
echo "CURRENT_PROD_SHA=$CURRENT_PROD_SHA"
echo "TARGET_SHA=$TARGET_SHA"
echo "ORIGIN_STAGING_SHA=$REMOTE_STAGING_SHA"

if [[ "$MODE" == "preview" ]]; then
    echo
    echo "========================================"
    echo "PRODUCTION_DEPLOY_PREVIEW=PASS"
    echo "NO_PRODUCTION_CHANGES_MADE=YES"
    echo "========================================"
    exit 0
fi

[[ "${PRODUCTION_APPROVAL_SHA:-}" == "$TARGET_SHA" ]] \
    || fail "Set PRODUCTION_APPROVAL_SHA tepat sama dengan TARGET_SHA."

echo
echo "Approval SHA cocok."
echo "Memulai deployment production..."

ssh rumahweb-smaafbs bash -s -- \
    "$TARGET_SHA" \
    "$CURRENT_PROD_SHA" \
    "$ALLOW_MIGRATIONS" <<'REMOTE'
set -euo pipefail

TARGET_SHA="$1"
EXPECTED_CURRENT_SHA="$2"
ALLOW_MIGRATIONS="$3"

APP_ROOT="/home/sman5479/spmb-app"
PUBLIC_ROOT="/home/sman5479/public_html/web/www.seleksi"
BACKUP_BASE="/home/sman5479/deploy-backups"
PROD_URL="https://seleksi.smaafbs.sch.id"

CODE_SWITCHED=0
APP_DOWN=0
BACKUP_DIR=""

fail() {
    echo "REMOTE_DEPLOY_FAIL: $*" >&2
    exit 1
}

env_value() {
    local key="$1" value

    value="$(
        grep -m1 -E "^[[:space:]]*(export[[:space:]]+)?${key}[[:space:]]*=" .env \
        | sed -E "s/^[[:space:]]*(export[[:space:]]+)?${key}[[:space:]]*=[[:space:]]*//"
    )"

    value="${value%$'\r'}"

    if [[ ${#value} -ge 2 ]]; then
        if [[ "${value:0:1}" == '"' && "${value: -1}" == '"' ]]; then
            value="${value:1:${#value}-2}"
        elif [[ "${value:0:1}" == "'" && "${value: -1}" == "'" ]]; then
            value="${value:1:${#value}-2}"
        fi
    fi

    printf '%s' "$value"
}

restore_custom_assets() {
    [[ -n "$BACKUP_DIR" ]] || return 0
    [[ -f "$BACKUP_DIR/custom-app-public.tar.gz" ]] || return 0

    tar -xzf "$BACKUP_DIR/custom-app-public.tar.gz" -C "$APP_ROOT"
}

rollback_code() {
    local status="$1"

    [[ "$status" -ne 0 ]] || return 0

    echo
    echo "DEPLOYMENT FAILED."

    if [[ "$CODE_SWITCHED" -eq 1 ]]; then
        echo "Rolling code back to $EXPECTED_CURRENT_SHA"

        cd "$APP_ROOT"

        php artisan down --retry=60 >/dev/null 2>&1 || true

        git reset --hard "$EXPECTED_CURRENT_SHA" || true

        restore_custom_assets || true

        php artisan optimize:clear >/dev/null 2>&1 || true
        php artisan optimize >/dev/null 2>&1 || true
        php artisan up >/dev/null 2>&1 || true

        echo "CODE_ROLLBACK_ATTEMPTED=YES"
        echo "NOTE=Database migrations are NOT automatically rolled back."
    elif [[ "$APP_DOWN" -eq 1 ]]; then
        cd "$APP_ROOT"
        php artisan up >/dev/null 2>&1 || true
    fi

    if [[ -n "$BACKUP_DIR" ]]; then
        echo "BACKUP_DIR=$BACKUP_DIR"
    fi
}

trap 'status=$?; rollback_code "$status"; exit "$status"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

cd "$APP_ROOT"

[[ -f .env ]] || fail ".env production tidak ditemukan."

APP_ENV="$(env_value APP_ENV)"
APP_URL="$(env_value APP_URL)"
DB_HOST="$(env_value DB_HOST)"
DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
DB_PASSWORD="$(env_value DB_PASSWORD)"

[[ "$APP_ENV" == "production" ]] \
    || fail "APP_ENV bukan production."

[[ "$APP_URL" == "$PROD_URL" ]] \
    || fail "APP_URL production tidak sesuai."

[[ "$DB_DATABASE" == "sman5479_ujian" ]] \
    || fail "DB production harus sman5479_ujian."

[[ -n "$DB_USERNAME" && -n "$DB_PASSWORD" ]] \
    || fail "Credential DB production tidak lengkap."

CURRENT_SHA="$(git rev-parse HEAD)"

[[ "$CURRENT_SHA" == "$EXPECTED_CURRENT_SHA" ]] \
    || fail "Production SHA berubah sejak preflight."

echo
echo "=== FETCH TARGET ==="

git fetch origin refs/heads/staging:refs/remotes/origin/staging

git cat-file -e "${TARGET_SHA}^{commit}" \
    || fail "Target SHA tidak tersedia setelah fetch."

git merge-base --is-ancestor "$TARGET_SHA" refs/remotes/origin/staging \
    || fail "Target bukan bagian dari fetched staging."

echo "FETCH_TARGET=PASS"

echo
echo "=== REMOTE MIGRATION CHECK ==="

MIGRATION_DIFF="$(
    git diff --name-status \
      "$CURRENT_SHA" "$TARGET_SHA" \
      -- database/migrations/
)"

if [[ -n "$MIGRATION_DIFF" ]]; then
    echo "MIGRATION_CHANGE=YES"
    printf '%s\n' "$MIGRATION_DIFF"

    [[ "$ALLOW_MIGRATIONS" == "1" ]] \
        || fail "Migration belum diizinkan."
else
    echo "MIGRATION_CHANGE=NO"
fi

echo
echo "=== CREATE BACKUP ==="

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_BASE/spmb-${STAMP}-${CURRENT_SHA:0:7}-to-${TARGET_SHA:0:7}"

mkdir -p "$BACKUP_DIR"

{
    echo "timestamp=$(date -Iseconds)"
    echo "current_sha=$CURRENT_SHA"
    echo "target_sha=$TARGET_SHA"
    echo "app_env=$APP_ENV"
    echo "app_url=$APP_URL"
    echo "db_database=$DB_DATABASE"
} > "$BACKUP_DIR/metadata.txt"

git status --short > "$BACKUP_DIR/git-status.txt"
git diff > "$BACKUP_DIR/local.patch" || true
git ls-files --others --exclude-standard > "$BACKUP_DIR/untracked.txt"

cp -p .env "$BACKUP_DIR/.env"

tar -czf "$BACKUP_DIR/custom-app-public.tar.gz" \
    public/favicon.ico \
    public/icons/icon-192.png \
    public/icons/icon-512.png \
    public/icons/maskable-512.png

tar -czf "$BACKUP_DIR/public-root.tar.gz" \
    -C "$(dirname "$PUBLIC_ROOT")" \
    "$(basename "$PUBLIC_ROOT")"

echo "Backing up production database..."

MYSQL_PWD="$DB_PASSWORD" \
mysqldump \
    -h "${DB_HOST:-localhost}" \
    -u "$DB_USERNAME" \
    --single-transaction \
    --quick \
    --skip-lock-tables \
    "$DB_DATABASE" \
    | gzip -1 > "$BACKUP_DIR/database.sql.gz"

[[ -s "$BACKUP_DIR/database.sql.gz" ]] \
    || fail "Database backup kosong."

gzip -t "$BACKUP_DIR/database.sql.gz" \
    || fail "Database backup gzip rusak."

echo "BACKUP=PASS"
echo "BACKUP_DIR=$BACKUP_DIR"

echo
echo "=== MAINTENANCE MODE ==="

php artisan down --retry=60
APP_DOWN=1

echo
echo "=== SWITCH EXACT SHA ==="

git reset --hard "$TARGET_SHA"
CODE_SWITCHED=1

restore_custom_assets

[[ "$(git rev-parse HEAD)" == "$TARGET_SHA" ]] \
    || fail "Git HEAD bukan target SHA."

echo "CODE_SHA=$TARGET_SHA"

echo
echo "=== AUTOLOAD CHECK ==="

php -r '
require "vendor/autoload.php";

$required = [
    "App\\\\Http\\\\Controllers\\\\Api\\\\SpmbDataBotController",
    "App\\\\Http\\\\Middleware\\\\SpmbDataBotAuth",
    "App\\\\Services\\\\SpmbReadService",
];

foreach ($required as $class) {
    if (! class_exists($class)) {
        fwrite(STDERR, "Autoload failed: ".$class.PHP_EOL);
        exit(1);
    }
}

echo "AUTOLOAD=PASS".PHP_EOL;
'

echo
echo "=== LARAVEL CACHE ==="

php artisan optimize:clear --ansi

if [[ -n "$MIGRATION_DIFF" ]]; then
    echo
    echo "=== MIGRATION PRETEND ==="

    php artisan migrate --pretend --force --ansi

    echo
    echo "=== MIGRATION APPLY ==="

    php artisan migrate --force --ansi
else
    echo "MIGRATION=SKIPPED"
fi

echo
echo "=== OPTIMIZE ==="

php artisan optimize --ansi

echo
echo "=== LEAVE MAINTENANCE ==="

php artisan up
APP_DOWN=0

echo
echo "=== HEALTH CHECK ==="

HOME_CODE="$(
    curl -sS --max-time 20 \
      -o /dev/null \
      -w '%{http_code}' \
      "$PROD_URL/"
)"

LOGIN_CODE="$(
    curl -sS --max-time 20 \
      -o /dev/null \
      -w '%{http_code}' \
      "$PROD_URL/login"
)"

API_CODE="$(
    curl -sS --max-time 20 \
      -o /dev/null \
      -w '%{http_code}' \
      -X POST \
      -H 'Content-Type: application/json' \
      -d '{"action":"get_quota"}' \
      "$PROD_URL/api/v1/bot/query"
)"

echo "HOME_HTTP=$HOME_CODE"
echo "LOGIN_HTTP=$LOGIN_CODE"
echo "API_UNAUTH_HTTP=$API_CODE"

[[ "$HOME_CODE" == "200" ]] \
    || fail "Homepage health check gagal."

[[ "$LOGIN_CODE" == "200" ]] \
    || fail "Login health check gagal."

[[ "$API_CODE" == "401" ]] \
    || fail "Read API unauthenticated health check harus 401."

echo
echo "=== CUSTOM ASSET CHECK ==="

check_asset() {
    local rel="$1"
    local web="${rel#public/}"

    cmp -s "$APP_ROOT/$rel" "$PUBLIC_ROOT/$web" \
        || fail "Custom asset berbeda: $rel"

    echo "MATCH: $rel"
}

check_asset public/favicon.ico
check_asset public/icons/icon-192.png
check_asset public/icons/icon-512.png
check_asset public/icons/maskable-512.png

echo
echo "========================================"
echo "PRODUCTION_DEPLOYMENT=PASS"
echo "CURRENT_SHA=$(git rev-parse HEAD)"
echo "BACKUP_DIR=$BACKUP_DIR"
echo "========================================"

trap - EXIT INT TERM
REMOTE

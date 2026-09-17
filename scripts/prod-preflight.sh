#!/usr/bin/env bash
set -euo pipefail

fail() {
    echo "PREFLIGHT_FAIL: $*" >&2
    exit 1
}

[[ $# -eq 1 ]] || fail "Usage: $0 <40-char SHA>"
TARGET_SHA="$1"
[[ "$TARGET_SHA" =~ ^[0-9a-fA-F]{40}$ ]] || fail "SHA harus 40 karakter."
# Default keeps the established operator alias; automation may provide it directly.
DEPLOY_SSH_TARGET="${DEPLOY_SSH_TARGET:-rumahweb-smaafbs}"
DEPLOY_SSH_PORT="${DEPLOY_SSH_PORT:-}"
ssh_production() {
    if [[ -n "$DEPLOY_SSH_PORT" ]]; then
        ssh -p "$DEPLOY_SSH_PORT" "$DEPLOY_SSH_TARGET" "$@"
    else
        ssh "$DEPLOY_SSH_TARGET" "$@"
    fi
}

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

echo "=== LOCAL ==="
echo "BRANCH=$(git branch --show-current)"
echo "TARGET_SHA=$TARGET_SHA"

git fetch --quiet origin

git cat-file -e "${TARGET_SHA}^{commit}" \
    || fail "Target SHA tidak ditemukan di repo lokal."

git merge-base --is-ancestor "$TARGET_SHA" origin/staging \
    || fail "Target SHA bukan bagian dari origin/staging."

CURRENT_PROD_SHA="$(
    ssh_production \
      'cd /home/sman5479/spmb-app && git rev-parse HEAD'
)"

echo "CURRENT_PROD_SHA=$CURRENT_PROD_SHA"

git cat-file -e "${CURRENT_PROD_SHA}^{commit}" \
    || fail "Current production SHA tidak tersedia di repo lokal."

echo
echo "=== CODE DIFFERENCE ==="
git diff --shortstat "$CURRENT_PROD_SHA" "$TARGET_SHA" || true

echo
echo "=== MIGRATION DIFFERENCE ==="
MIGRATION_DIFF="$(
    git diff --name-status \
      "$CURRENT_PROD_SHA" "$TARGET_SHA" \
      -- database/migrations/
)"

if [[ -n "$MIGRATION_DIFF" ]]; then
    echo "MIGRATION_CHANGE=YES"
    printf '%s\n' "$MIGRATION_DIFF"
else
    echo "MIGRATION_CHANGE=NO"
fi

echo
echo "=== REMOTE PRODUCTION PREFLIGHT ==="

ssh_production bash -s -- "$TARGET_SHA" <<'REMOTE'
set -euo pipefail

TARGET_SHA="$1"

APP_ROOT="/home/sman5479/spmb-app"
PUBLIC_ROOT="/home/sman5479/public_html/web/www.seleksi"

cd "$APP_ROOT"

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

APP_ENV="$(env_value APP_ENV)"
APP_URL="$(env_value APP_URL)"
DB_DATABASE="$(env_value DB_DATABASE)"

echo "APP_ENV=$APP_ENV"
echo "APP_URL=$APP_URL"
echo "DB_DATABASE=$DB_DATABASE"
echo "CURRENT_SHA=$(git rev-parse HEAD)"
echo "TARGET_SHA=$TARGET_SHA"

[[ "$APP_ENV" == "production" ]] \
    || { echo "FAIL: APP_ENV bukan production"; exit 1; }

[[ "$APP_URL" == "https://seleksi.smaafbs.sch.id" ]] \
    || { echo "FAIL: APP_URL salah"; exit 1; }

[[ "$DB_DATABASE" == "sman5479_ujian" ]] \
    || { echo "FAIL: DB production salah"; exit 1; }

echo
echo "--- DIRTY WORKTREE SAFETY ---"

unexpected=0
status_file="$(mktemp)"
git status --porcelain --untracked-files=all > "$status_file"

while IFS= read -r line; do
    [[ -z "$line" ]] && continue

    path="${line:3}"

    case "$path" in
        public/favicon.ico|\
        public/icons/icon-192.png|\
        public/icons/icon-512.png|\
        public/icons/maskable-512.png|\
        .env.bak-*|\
        public/favicon.ico.bak-*)
            echo "ALLOWED: $line"
            ;;
        *)
            echo "UNEXPECTED: $line"
            unexpected=1
            ;;
    esac
done < "$status_file"

rm -f "$status_file"

[[ "$unexpected" -eq 0 ]] \
    || { echo "FAIL: unexpected production changes"; exit 1; }

echo
echo "--- CUSTOM PUBLIC ASSETS ---"

check_custom() {
    local rel="$1"

    if [[ ! -f "$APP_ROOT/$rel" ]]; then
        echo "MISSING_APP: $rel"
        return 1
    fi

    if [[ ! -f "$PUBLIC_ROOT/${rel#public/}" ]]; then
        echo "MISSING_PUBLIC_ROOT: $rel"
        return 1
    fi

    if cmp -s \
        "$APP_ROOT/$rel" \
        "$PUBLIC_ROOT/${rel#public/}"
    then
        echo "MATCH: $rel"
    else
        echo "DIFFERENT: $rel"
        return 1
    fi
}

check_custom public/favicon.ico
check_custom public/icons/icon-192.png
check_custom public/icons/icon-512.png
check_custom public/icons/maskable-512.png

echo
echo "--- REQUIRED COMMANDS ---"

for cmd in php git curl tar gzip; do
    if command -v "$cmd" >/dev/null 2>&1; then
        echo "$cmd=OK"
    else
        echo "$cmd=MISSING"
        exit 1
    fi
done

for cmd in composer npm mysqldump; do
    if command -v "$cmd" >/dev/null 2>&1; then
        echo "$cmd=OK"
    else
        echo "$cmd=NOT_FOUND"
    fi
done

echo
echo "--- DISK ---"
df -h "$APP_ROOT" | tail -1

echo
echo "--- CURRENT PUBLIC SIZE ---"
du -sh "$PUBLIC_ROOT"

echo
echo "REMOTE_PREFLIGHT=PASS"
REMOTE

echo
echo "=== RESULT ==="
echo "PRODUCTION_PREFLIGHT=PASS"
echo "CURRENT_PROD_SHA=$CURRENT_PROD_SHA"
echo "TARGET_SHA=$TARGET_SHA"

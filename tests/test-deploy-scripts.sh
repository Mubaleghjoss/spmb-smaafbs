#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
pass=0

expect_fail() {
    if "$@" >/dev/null 2>&1; then
        echo "Expected failure: $*" >&2
        exit 1
    fi
    pass=$((pass + 1))
}
expect_pass() {
    if ! "$@" >/dev/null 2>&1; then
        echo "Expected success: $*" >&2
        exit 1
    fi
    pass=$((pass + 1))
}

# Test a temporary copy so production's fixed APP_ROOT is never relaxed.
app_root="$TMP/cpanel-app"
mkdir -p "$app_root"
cp "$ROOT/scripts/deploy-cpanel-ssh.sh" "$TMP/deploy-cpanel-ssh.sh"
sed -i "s|EXPECTED_APP_ROOT=\"/home/sman5479/spmb-app\"|EXPECTED_APP_ROOT=\"$app_root\"|" "$TMP/deploy-cpanel-ssh.sh"
chmod +x "$TMP/deploy-cpanel-ssh.sh"

expect_fail env APP_ROOT="$app_root" bash "$TMP/deploy-cpanel-ssh.sh" --dry-run
cat > "$app_root/.env" <<'ENV'
APP_ENV=local
APP_URL=https://seleksi.smaafbs.sch.id
DB_DATABASE=sman5479_spmb
DB_USERNAME=sman5479_spmb
DB_PASSWORD=test-password
ENV
expect_fail env APP_ROOT="$app_root" bash "$TMP/deploy-cpanel-ssh.sh" --dry-run
sed -i 's|APP_ENV=local|APP_ENV=production|; s|https://seleksi.smaafbs.sch.id|https://wrong.example.test|' "$app_root/.env"
expect_fail env APP_ROOT="$app_root" bash "$TMP/deploy-cpanel-ssh.sh" --dry-run
sed -i 's|https://wrong.example.test|https://seleksi.smaafbs.sch.id|; s|DB_DATABASE=sman5479_spmb|DB_DATABASE=sman5479_ujian|' "$app_root/.env"
expect_fail env APP_ROOT="$app_root" bash "$TMP/deploy-cpanel-ssh.sh" --dry-run
sed -i 's|DB_DATABASE=sman5479_ujian|DB_DATABASE=sman5479_spmb|' "$app_root/.env"
expect_pass env APP_ROOT="$app_root" bash "$TMP/deploy-cpanel-ssh.sh" --dry-run

expect_fail bash "$ROOT/scripts/deploy-staging.sh"
expect_fail bash "$ROOT/scripts/deploy-staging.sh" not-a-sha
expect_fail env STAGING_ROOT=/var/www/production bash "$ROOT/scripts/deploy-staging.sh"

echo "Deploy script tests passed ($pass checks)."

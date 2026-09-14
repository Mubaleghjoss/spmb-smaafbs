#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
pass=0

expect_fail() {
    if "$@" >/dev/null 2>&1; then echo "Expected failure: $*" >&2; exit 1; fi
    pass=$((pass + 1))
}
expect_pass() {
    if ! "$@" >/dev/null 2>&1; then echo "Expected success: $*" >&2; exit 1; fi
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

# Keep validation failures isolated from the real audit-log destination.
expect_fail env DEPLOY_LOG_DIR="$TMP/validation-logs" bash "$ROOT/scripts/deploy-staging.sh"
expect_fail env DEPLOY_LOG_DIR="$TMP/validation-logs" bash "$ROOT/scripts/deploy-staging.sh" not-a-sha
expect_fail env DEPLOY_LOG_DIR="$TMP/validation-logs" STAGING_ROOT=/var/www/production bash "$ROOT/scripts/deploy-staging.sh"

mock_bin="$TMP/mock-bin"
mkdir -p "$mock_bin"
cat > "$mock_bin/composer" <<'MOCK'
#!/usr/bin/env bash
[[ "${FAIL_COMPOSER:-0}" != 1 ]]
MOCK
cat > "$mock_bin/npm" <<'MOCK'
#!/usr/bin/env bash
exit 0
MOCK
cat > "$mock_bin/php" <<'MOCK'
#!/usr/bin/env bash
mkdir -p public/build
printf '{}' > public/build/manifest.json
[[ "${FAIL_PHP:-0}" != 1 ]]
MOCK
chmod +x "$mock_bin/composer" "$mock_bin/npm" "$mock_bin/php"

sha="$(git -C "$ROOT" rev-parse HEAD)"
staging_root="$TMP/staging"
log_dir="$TMP/deploy-logs"
mkdir -p "$staging_root/shared"
cat > "$staging_root/shared/.env" <<'ENV'
APP_ENV=staging
APP_URL=https://staging-seleksi.smaafbs.sch.id
DB_PASSWORD=super-secret-password
APP_KEY=base64:do-not-log-this
ENV
run_staging() {
    env PATH="$mock_bin:$PATH" STAGING_ROOT="$staging_root" DEPLOY_LOG_DIR="$log_dir" SKIP_NETWORK_HEALTH_CHECK=1 \
        bash "$ROOT/scripts/deploy-staging.sh" "$sha"
}
expect_pass run_staging
success_log="$(find "$log_dir" -name 'deploy-*-SUCCESS.log' -type f | head -n 1)"
[[ -n "$success_log" && -f "$success_log" ]] || { echo 'Missing success audit log' >&2; exit 1; }
for field in timestamp_start timestamp_end target_sha previous_sha branch/source composer_result npm_build_result migration_result artisan_optimize_result symlink_switch health_check deployment_status duration_seconds; do
    grep -Eq "^${field}=" "$success_log" || { echo "Missing $field" >&2; exit 1; }
done
[[ "$(grep '^deployment_status=' "$success_log")" == 'deployment_status=SUCCESS' ]]
! grep -Eq 'super-secret-password|base64:do-not-log-this|DB_PASSWORD|APP_KEY' "$success_log"
pass=$((pass + 1))

# A composer error must create a finalized FAILED audit entry that names its stage.
failed_root="$TMP/staging-failed"
mkdir -p "$failed_root/shared"
cp "$staging_root/shared/.env" "$failed_root/shared/.env"
expect_fail env FAIL_COMPOSER=1 PATH="$mock_bin:$PATH" STAGING_ROOT="$failed_root" DEPLOY_LOG_DIR="$log_dir" SKIP_NETWORK_HEALTH_CHECK=1 bash "$ROOT/scripts/deploy-staging.sh" "$sha"
failed_log="$(find "$log_dir" -name 'deploy-*-FAILED.log' -type f | head -n 1)"
[[ "$(grep '^deployment_status=' "$failed_log")" == 'deployment_status=FAILED' ]]
[[ "$(grep '^stage_failed=' "$failed_log")" == 'stage_failed=composer' ]]
! grep -Eq 'super-secret-password|base64:do-not-log-this|DB_PASSWORD|APP_KEY' "$failed_log"
pass=$((pass + 1))

# Pre-existing audit records plus this run must be pruned to the 30 newest files.
for n in $(seq 1 31); do printf 'old\n' > "$log_dir/deploy-20000101-0000${n}-SUCCESS.log"; done
retention_root="$TMP/staging-retention"
mkdir -p "$retention_root/shared"
cp "$staging_root/shared/.env" "$retention_root/shared/.env"
expect_pass env PATH="$mock_bin:$PATH" STAGING_ROOT="$retention_root" DEPLOY_LOG_DIR="$log_dir" SKIP_NETWORK_HEALTH_CHECK=1 bash "$ROOT/scripts/deploy-staging.sh" "$sha"
[[ "$(find "$log_dir" -maxdepth 1 -name 'deploy-*.log' -type f | wc -l)" -eq 30 ]] || { echo 'Audit log retention failed' >&2; exit 1; }
pass=$((pass + 1))

echo "Deploy script tests passed ($pass checks)."

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
set -euo pipefail
mkdir -p public/build
printf '{}' > public/build/manifest.json
case "${*:-}" in
    *"route:clear"*)
        [[ "${FAIL_ROUTE_CLEAR:-0}" != 1 ]]
        ;;
    *"optimize:clear"|*" optimize" )
        [[ "${FAIL_PHP:-0}" != 1 ]]
        ;;
    *"route:cache"*)
        [[ "${FAIL_ROUTE_CACHE:-0}" != 1 ]]
        ;;
    *"route:list --json"*)
        [[ "${FAIL_RUNTIME_ROUTE:-0}" != 1 ]] || exit 1
        python3 - <<'PY'
import json, os, pathlib, re
pattern = re.compile(r"(?:->|Route::)name\s*\(\s*(['\"])([^'\"]+)\1")
group_pattern = re.compile(r"->name\s*\(\s*(['\"])([^'\"]*)\1\s*\)(?:(?!->group\s*\().)*->group\s*\(\s*function\s*\([^)]*\)\s*\{(.*?)\}\s*\);", re.S)
names = set()
for path in pathlib.Path('routes').rglob('*.php'):
    text = path.read_text(encoding='utf-8')
    grouped_spans = []
    for group in group_pattern.finditer(text):
        grouped_spans.append((group.start(3), group.end(3)))
        names.update(group.group(2) + match.group(2) for match in pattern.finditer(group.group(3)))
    for match in pattern.finditer(text):
        if not any(start <= match.start() < end for start, end in grouped_spans):
            names.add(match.group(2))
names = sorted(names)
omit = os.environ.get('OMIT_RUNTIME_ROUTE', '')
if omit == '__AUTO__':
    omit = next((name for name in names if name not in {'peserta.dashboard', 'peserta.akun.username', 'peserta.akun.password'}), '')
if omit:
    names = [name for name in names if name != omit]
print(json.dumps([{"uri": "mock/" + name, "name": name, "methods": ["GET"]} for name in names]))
PY
        ;;
    *"route:list --ansi"*)
        [[ "${FAIL_RUNTIME_ROUTE:-0}" != 1 ]] || exit 1
        ;;
esac
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
for field in timestamp_start timestamp_end target_sha previous_sha branch/source composer_result npm_build_result migration_command_executed migration_result artisan_optimize_result route_cache_rebuilt route_cache_result route_runtime_check symlink_switch health_check deployment_status duration_seconds; do
    grep -Eq "^${field}=" "$success_log" || { echo "Missing $field" >&2; exit 1; }
done
[[ "$(grep '^deployment_status=' "$success_log")" == 'deployment_status=SUCCESS' ]]
[[ "$(grep '^route_cache_rebuilt=' "$success_log")" == 'route_cache_rebuilt=YES' ]]
[[ "$(grep '^route_cache_result=' "$success_log")" == 'route_cache_result=PASS' ]]
[[ "$(grep '^route_runtime_check=' "$success_log")" == 'route_runtime_check=PASS' ]]
[[ "$(grep '^migration_command_executed=' "$success_log")" == 'migration_command_executed=NO' ]]
! grep -Eq 'super-secret-password|base64:do-not-log-this|DB_PASSWORD|APP_KEY' "$success_log"
pass=$((pass + 1))

run_isolated_failure() {
    local case_name="$1" variable="$2" expected_route_result="$3" expected_runtime_result="$4"
    local failure_root="$TMP/staging-$case_name" failure_logs="$TMP/logs-$case_name"
    mkdir -p "$failure_root/shared"
    cp "$staging_root/shared/.env" "$failure_root/shared/.env"
    expect_fail env "$variable"=1 PATH="$mock_bin:$PATH" STAGING_ROOT="$failure_root" DEPLOY_LOG_DIR="$failure_logs" SKIP_NETWORK_HEALTH_CHECK=1 bash "$ROOT/scripts/deploy-staging.sh" "$sha"
    [[ ! -e "$failure_root/current" ]] || { echo "$case_name switched current symlink" >&2; exit 1; }
    [[ -z "$(find "$failure_logs" -maxdepth 1 -name 'deploy-*-SUCCESS.log' -type f -print -quit)" ]] || { echo "$case_name wrote SUCCESS audit" >&2; exit 1; }
    local failure_log
    failure_log="$(find "$failure_logs" -maxdepth 1 -name 'deploy-*-FAILED.log' -type f -print -quit)"
    [[ -n "$failure_log" && -f "$failure_log" ]] || { echo "Missing $case_name FAILED audit" >&2; exit 1; }
    [[ "$(grep '^route_cache_result=' "$failure_log")" == "route_cache_result=$expected_route_result" ]] || { echo "$case_name route cache result mismatch" >&2; exit 1; }
    [[ "$(grep '^route_runtime_check=' "$failure_log")" == "route_runtime_check=$expected_runtime_result" ]] || { echo "$case_name runtime route result mismatch" >&2; exit 1; }
}
run_isolated_failure route-clear FAIL_ROUTE_CLEAR FAIL FAIL
run_isolated_failure route-cache FAIL_ROUTE_CACHE FAIL FAIL
run_isolated_failure runtime-route FAIL_RUNTIME_ROUTE PASS FAIL

generic_root="$TMP/staging-generic-route"
generic_logs="$TMP/logs-generic-route"
mkdir -p "$generic_root/shared"
cp "$staging_root/shared/.env" "$generic_root/shared/.env"
expect_fail env OMIT_RUNTIME_ROUTE=__AUTO__ PATH="$mock_bin:$PATH" STAGING_ROOT="$generic_root" DEPLOY_LOG_DIR="$generic_logs" SKIP_NETWORK_HEALTH_CHECK=1 bash "$ROOT/scripts/deploy-staging.sh" "$sha"
[[ ! -e "$generic_root/current" ]] || { echo 'generic route mismatch switched current symlink' >&2; exit 1; }
generic_log="$(find "$generic_logs" -maxdepth 1 -name 'deploy-*-FAILED.log' -type f -print -quit)"
[[ -n "$generic_log" && "$(grep '^route_cache_result=' "$generic_log")" == 'route_cache_result=PASS' ]] || { echo 'generic route mismatch cache result mismatch' >&2; exit 1; }
[[ "$(grep '^route_runtime_check=' "$generic_log")" == 'route_runtime_check=FAIL' ]] || { echo 'generic route mismatch runtime result mismatch' >&2; exit 1; }
pass=$((pass + 4))

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

#!/usr/bin/env bash
set -euo pipefail

EXPECTED_APP_ROOT="/home/sman5479/spmb-app"
EXPECTED_PUBLIC_ROOT="/home/sman5479/public_html/web/www.seleksi"
EXPECTED_APP_URL="https://seleksi.smaafbs.sch.id"
EXPECTED_DATABASE="sman5479_spmb"
APP_ROOT="${APP_ROOT:-$EXPECTED_APP_ROOT}"
PUBLIC_ROOT="${PUBLIC_ROOT:-$EXPECTED_PUBLIC_ROOT}"
DRY_RUN=false

if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
elif [[ $# -gt 0 ]]; then
    echo "Usage: $0 [--dry-run]" >&2
    exit 64
fi

# Read only the requested key, including quoted dotenv values, without sourcing secrets.
env_value() {
    local key="$1" value
    value="$(awk -v key="$key" '
        $0 ~ "^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=" {
            sub("^[[:space:]]*(export[[:space:]]+)?" key "[[:space:]]*=", "")
            gsub(/^[[:space:]]+|[[:space:]]+$/, "")
            if ((substr($0, 1, 1) == "\"" && substr($0, length($0), 1) == "\"") || (substr($0, 1, 1) == "\047" && substr($0, length($0), 1) == "\047"))
                print substr($0, 2, length($0) - 2)
            else print
            exit
        }
    ' "$APP_ROOT/.env")"
    printf '%s' "$value"
}

if [[ "$APP_ROOT" != "$EXPECTED_APP_ROOT" ]]; then
    echo "Refusing unexpected APP_ROOT: $APP_ROOT" >&2
    exit 1
fi
if [[ ! -d "$APP_ROOT" ]]; then
    echo "App root not found: $APP_ROOT" >&2
    exit 1
fi
if [[ ! -f "$APP_ROOT/.env" ]]; then
    echo "Refusing deployment: required server .env is missing at $APP_ROOT/.env" >&2
    exit 1
fi

APP_ENV="$(env_value APP_ENV)"
APP_URL="$(env_value APP_URL)"
DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
DB_PASSWORD="$(env_value DB_PASSWORD)"

[[ "$APP_ENV" == "production" ]] || { echo "Refusing deployment: APP_ENV must be production." >&2; exit 1; }
[[ "$APP_URL" == "$EXPECTED_APP_URL" ]] || { echo "Refusing deployment: APP_URL must be $EXPECTED_APP_URL." >&2; exit 1; }
[[ "$DB_DATABASE" == "$EXPECTED_DATABASE" && "$DB_DATABASE" != *ujian* ]] || { echo "Refusing deployment: DB_DATABASE must be $EXPECTED_DATABASE." >&2; exit 1; }
[[ -n "$DB_USERNAME" ]] || { echo "Refusing deployment: DB_USERNAME is empty." >&2; exit 1; }
[[ -n "$DB_PASSWORD" ]] || { echo "Refusing deployment: DB_PASSWORD is empty in server .env." >&2; exit 1; }

if "$DRY_RUN"; then
    echo "Deployment validation passed."
    exit 0
fi

cd "$APP_ROOT"

if [[ -f .env.production ]]; then
    cp .env .env.production
    echo ".env.production exists; synced it from .env."
fi

if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
elif [[ -f vendor/autoload.php ]]; then
    echo "composer command not found; using existing vendor directory."
else
    echo "composer command not found. Install dependencies from cPanel Composer or upload vendor manually." >&2
    exit 1
fi

if command -v npm >/dev/null 2>&1 && [[ -f package-lock.json ]]; then
    npm ci
    npm run build
else
    echo "npm not found; using committed public/build assets."
fi

mkdir -p "$PUBLIC_ROOT" storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \; || true
find storage bootstrap/cache -type f -exec chmod 664 {} \; || true

shopt -s dotglob nullglob
for item in "$APP_ROOT"/public/*; do
    name="$(basename "$item")"
    case "$name" in storage|uploads|setup-hosting.php|run-migration.php) continue ;; esac
    cp -R "$item" "$PUBLIC_ROOT/"
done
shopt -u dotglob nullglob
mkdir -p "$PUBLIC_ROOT/storage" "$PUBLIC_ROOT/uploads"

cat > "$PUBLIC_ROOT/index.php" <<PHP
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists(\$maintenance = '${APP_ROOT}/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require '${APP_ROOT}/vendor/autoload.php';

(require_once '${APP_ROOT}/bootstrap/app.php')
    ->handleRequest(Request::capture());
PHP

if [[ -f artisan ]]; then
    if php -r '$env = parse_ini_file(".env", false, INI_SCANNER_RAW); $key = $env["APP_KEY"] ?? ""; if (str_starts_with($key, "base64:")) { $decoded = base64_decode(substr($key, 7), true); exit($decoded !== false && in_array(strlen($decoded), [16, 32], true) ? 0 : 1); } exit(in_array(strlen($key), [16, 32], true) ? 0 : 1);'; then
        echo "APP_KEY already exists; keeping current key."
    else
        php artisan key:generate --force --ansi
    fi
    php artisan storage:link || true
    rm -f bootstrap/cache/config.php bootstrap/cache/events.php bootstrap/cache/packages.php bootstrap/cache/routes-v*.php bootstrap/cache/services.php
    php artisan optimize:clear --ansi
    php artisan migrate --force --ansi
    php artisan optimize --ansi
fi

echo "Deploy finished."
echo "App root: $APP_ROOT"
echo "Public root: $PUBLIC_ROOT"
echo "URL: $APP_URL"

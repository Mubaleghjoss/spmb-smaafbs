#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${APP_ROOT:-/home/sman5479/spmb-app}"
PUBLIC_ROOT="${PUBLIC_ROOT:-/home/sman5479/public_html/web/www.seleksi}"
APP_URL="${APP_URL:-https://seleksi.smaafbs.sch.id}"
DB_DATABASE="${DB_DATABASE:-sman5479_ujian}"
DB_USERNAME="${DB_USERNAME:-sman5479_ujian}"
DB_PASSWORD="${DB_PASSWORD:-}"

if [ ! -d "$APP_ROOT" ]; then
    echo "App root not found: $APP_ROOT"
    exit 1
fi

cd "$APP_ROOT"

if [ ! -f .env ]; then
    if [ -z "$DB_PASSWORD" ]; then
        echo "DB_PASSWORD is required for first deploy."
        echo "Example: DB_PASSWORD='your-db-password' bash scripts/deploy-cpanel-ssh.sh"
        exit 1
    fi

    cat > .env <<ENV
APP_NAME="SPMB SMA Al Furqon"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=${APP_URL}

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_CONNECTION=log
CACHE_STORE=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_COOKIE=seleksi_spmb_session
SESSION_SECURE_COOKIE=true

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@smaafbs.sch.id"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"
ENV
fi

if [ -f .env.production ]; then
    cp .env .env.production
    echo ".env.production exists; synced it from .env."
fi

if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
elif [ -f vendor/autoload.php ]; then
    echo "composer command not found; using existing vendor directory."
else
    echo "composer command not found. Install dependencies from cPanel Composer or upload vendor manually."
    exit 1
fi

if command -v npm >/dev/null 2>&1 && [ -f package-lock.json ]; then
    npm ci
    npm run build
else
    echo "npm not found; using committed public/build assets."
fi

mkdir -p "$PUBLIC_ROOT"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \; || true
find storage bootstrap/cache -type f -exec chmod 664 {} \; || true

shopt -s dotglob nullglob
for item in "$APP_ROOT"/public/*; do
    name="$(basename "$item")"
    case "$name" in
        storage|uploads|setup-hosting.php|run-migration.php)
            continue
            ;;
    esac
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

if [ -f artisan ]; then
    if php -r '$env = parse_ini_file(".env", false, INI_SCANNER_RAW); $key = $env["APP_KEY"] ?? ""; if (str_starts_with($key, "base64:")) { $decoded = base64_decode(substr($key, 7), true); exit($decoded !== false && in_array(strlen($decoded), [16, 32], true) ? 0 : 1); } exit(in_array(strlen($key), [16, 32], true) ? 0 : 1);'; then
        echo "APP_KEY already exists; keeping current key."
    else
        php artisan key:generate --force --ansi
    fi

    if php -r 'exit(function_exists("exec") ? 0 : 1);'; then
        php artisan storage:link || true
    else
        echo "PHP exec() is disabled; skipping storage:link."
    fi

    rm -f bootstrap/cache/config.php \
        bootstrap/cache/events.php \
        bootstrap/cache/packages.php \
        bootstrap/cache/routes-v*.php \
        bootstrap/cache/services.php

    php artisan optimize:clear --ansi
    php artisan migrate --force --ansi
    php artisan optimize --ansi
fi

echo "Deploy finished."
echo "App root: $APP_ROOT"
echo "Public root: $PUBLIC_ROOT"
echo "URL: $APP_URL"

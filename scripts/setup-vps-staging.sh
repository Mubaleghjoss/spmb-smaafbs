#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $EUID -ne 0 ]]; then echo "Must be run as root"; exit 1; fi

BASE=/var/www/spmb-staging
SHARED="$BASE/shared"
ENV_FILE="$SHARED/.env"
NGINX_AVAILABLE=/etc/nginx/sites-available/spmb-staging
NGINX_ENABLED=/etc/nginx/sites-enabled/spmb-staging
DB_NAME=spmb_staging
DB_USER=spmb_stg
DB_HOST=127.0.0.1

for command in openssl mysql chown chmod nginx systemctl; do
    command -v "$command" >/dev/null || { echo "Required command not found: $command" >&2; exit 1; }
done

# Create the Laravel shared tree before writing its environment file.
mkdir -p \
    "$BASE/releases" \
    "$SHARED/storage/app/public" \
    "$SHARED/storage/framework/cache/data" \
    "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" \
    "$SHARED/storage/logs"

chown -R hermesadmin:www-data "$BASE"
find "$BASE" -type d -exec chmod 2775 {} +
find "$BASE" -type f -exec chmod 664 {} +

if [[ -f "$ENV_FILE" ]]; then
    # Read only the value; the generated password is never sent to stdout.
    DB_PASSWORD=$(awk -F= '$1 == "DB_PASSWORD" { sub(/^[[:space:]]*/, "", $2); print $2; exit }' "$ENV_FILE")
else
    DB_PASSWORD=
fi
[[ -n "$DB_PASSWORD" ]] || DB_PASSWORD=$(openssl rand -hex 16)
# Escape an existing password before placing it in a MySQL string literal.
MYSQL_PASSWORD=${DB_PASSWORD//\'/\'\'}

if [[ -r /etc/mysql/debian.cnf ]]; then
    MYSQL=(mysql --defaults-file=/etc/mysql/debian.cnf)
else
    MYSQL=(mysql)
fi
"${MYSQL[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$MYSQL_PASSWORD';
ALTER USER '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$MYSQL_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'$DB_HOST';
FLUSH PRIVILEGES;
SQL

echo "Configured MySQL database: $DB_NAME (user: $DB_USER)"

if [[ ! -f "$ENV_FILE" ]]; then
    APP_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
    cat > "$ENV_FILE" <<EOF
APP_NAME="SPMB SMA Al Furqon (Staging)"
APP_ENV=staging
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=https://staging-seleksi.smaafbs.sch.id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spmb_staging
DB_USERNAME=spmb_stg
DB_PASSWORD=$DB_PASSWORD
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
EOF
elif ! grep -qE '^DB_PASSWORD=[^[:space:]]' "$ENV_FILE"; then
    printf '\nDB_PASSWORD=%s\n' "$DB_PASSWORD" >> "$ENV_FILE"
fi

chown hermesadmin:www-data "$ENV_FILE"
chmod 640 "$ENV_FILE"

cat > "$NGINX_AVAILABLE" <<'EOF'
server {
    listen 127.0.0.1:8083;
    server_name staging-seleksi.smaafbs.sch.id;

    root /var/www/spmb-staging/current/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param HTTPS on;
        fastcgi_param SERVER_PORT 443;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF
chmod 644 "$NGINX_AVAILABLE"
ln -sfn "$NGINX_AVAILABLE" "$NGINX_ENABLED"
nginx -t
systemctl reload nginx

echo "Configured staging directories: $BASE"
echo "Configured shared environment: $ENV_FILE (mode 640; password withheld)"
echo "Configured nginx: $NGINX_AVAILABLE"
echo "Nginx validated and reloaded successfully."

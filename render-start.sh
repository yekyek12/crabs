#!/usr/bin/env bash
set -e

if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    export DB_URL="$DATABASE_URL"
fi

if [ -n "$DB_URL" ]; then
    case "$DB_URL" in
        postgres://*|postgresql://*)
            export DB_CONNECTION="pgsql"
            ;;
        mysql://*|mariadb://*)
            export DB_CONNECTION="${DB_CONNECTION:-mysql}"
            ;;
    esac
fi

if [ -n "$RENDER" ] && [ "${DB_CONNECTION:-}" = "mysql" ] && { [ -z "${DB_HOST:-}" ] || [ "$DB_HOST" = "127.0.0.1" ] || [ "$DB_HOST" = "localhost" ]; }; then
    echo "Render is configured for MySQL on ${DB_HOST:-127.0.0.1}, but no MySQL server runs inside this web service." >&2
    echo "Create/connect a Render PostgreSQL database and set DATABASE_URL, or set DB_CONNECTION=pgsql with DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, and DB_PASSWORD." >&2
    exit 1
fi

DB_CONNECTION_NAME="${DB_CONNECTION:-sqlite}"

mkdir -p \
    "${LOCAL_FILESYSTEM_ROOT:-/var/www/html/storage/app/private}" \
    "${PUBLIC_FILESYSTEM_ROOT:-/var/www/html/storage/app/public}" \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

if [ "$DB_CONNECTION_NAME" = "sqlite" ]; then
    mkdir -p "$(dirname "${DB_DATABASE:-/var/www/html/database/database.sqlite}")"
    touch "${DB_DATABASE:-/var/www/html/database/database.sqlite}"
fi

chown -R www-data:www-data \
    "${LOCAL_FILESYSTEM_ROOT:-/var/www/html/storage/app/private}" \
    "${PUBLIC_FILESYSTEM_ROOT:-/var/www/html/storage/app/public}" \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

if [ "$DB_CONNECTION_NAME" = "sqlite" ]; then
    chown -R www-data:www-data "$(dirname "${DB_DATABASE:-/var/www/html/database/database.sqlite}")"
fi

if [ -n "$PORT" ]; then
    sed -ri -e "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
    sed -ri -e "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/*.conf
fi

if [ -n "$RENDER_EXTERNAL_HOSTNAME" ]; then
    export APP_URL="${APP_URL:-https://$RENDER_EXTERNAL_HOSTNAME}"
    export ASSET_URL="${ASSET_URL:-https://$RENDER_EXTERNAL_HOSTNAME}"
fi

if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
elif [[ "$APP_KEY" != base64:* ]]; then
    export RENDER_RAW_APP_KEY="$APP_KEY"
    export APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("RENDER_RAW_APP_KEY"), true));')"
    unset RENDER_RAW_APP_KEY
fi

if [ -n "$AI_SERVICE_URL" ] && [[ "$AI_SERVICE_URL" != http://* ]] && [[ "$AI_SERVICE_URL" != https://* ]]; then
    export AI_SERVICE_URL="http://$AI_SERVICE_URL"
fi

if [ "$DB_CONNECTION_NAME" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
    for attempt in {1..60}; do
        if php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: "3306").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; then
            echo "MySQL is ready."
            break
        fi

        if [ "$attempt" -eq 60 ]; then
            echo "MySQL did not become ready in time." >&2
            exit 1
        fi

        sleep 2
    done
fi

if [ "$DB_CONNECTION_NAME" = "pgsql" ]; then
    echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
    for attempt in {1..60}; do
        if php -r 'try { new PDO("pgsql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: "5432").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; then
            echo "PostgreSQL is ready."
            break
        fi

        if [ "$attempt" -eq 60 ]; then
            echo "PostgreSQL did not become ready in time." >&2
            exit 1
        fi

        sleep 2
    done
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground

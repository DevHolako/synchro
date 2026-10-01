#!/bin/sh
set -e

# Ensure permissions for Laravel directories (storage may be an empty host folder)
mkdir -p /app/storage/app/public \
         /app/storage/app/private/imports \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/bootstrap/cache

chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set in .env. Generate one with: docker compose run --rm --no-deps app php artisan key:generate --show" >&2
    exit 1
fi

# Cache config, routes, views and events. Runs at boot, not build, because config needs the runtime env.
# Non-fatal: on failure, drop any partial cache and boot uncached rather than crash-loop.
php artisan optimize --no-interaction || php artisan optimize:clear --no-interaction || true

exec "$@"

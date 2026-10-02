#!/bin/sh
set -e

# Ensure permissions for Laravel directories (storage may be an empty host volume)
mkdir -p /app/storage/app/public \
         /app/storage/app/private/imports \
         /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/bootstrap/cache

chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true

# Validate or generate runtime APP_KEY
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set in environment! Generating a temporary runtime key." >&2
    echo "IMPORTANT: Please set APP_KEY in your Coolify / production environment variables for data persistence." >&2
    export APP_KEY=$(php artisan key:generate --show)
fi

# Ensure storage symlink exists
if [ ! -L /app/public/storage ]; then
    php artisan storage:link --no-interaction 2>/dev/null || true
fi

# Optional: wait for database connection if MySQL is configured
if [ "$DB_CONNECTION" = "mysql" ] && [ -n "$DB_HOST" ]; then
    echo "Checking database connection to $DB_HOST..."
    php -r '
    $max = 30;
    while ($max > 0) {
        try {
            app()->make("db")->connection()->getPdo();
            echo "Database connection established.\n";
            exit(0);
        } catch (\Throwable $e) {
            $max--;
            sleep(1);
        }
    }
    echo "Warning: Database connection could not be established within 30s. Continuing...\n";
    ' 2>/dev/null || true
fi

# Cache config, routes, views and events.
# Non-fatal: on failure, drop any partial cache and boot uncached rather than crash-loop.
php artisan optimize --no-interaction || php artisan optimize:clear --no-interaction || true

# For single-container deployments (e.g. Coolify Single Container mode with CONTAINER_ROLE=all),
# spawn Horizon worker and Laravel scheduler as background processes.
if [ "$CONTAINER_ROLE" = "all" ]; then
    echo "Starting Horizon worker in background..."
    php artisan horizon &
    echo "Starting Scheduler in background..."
    php artisan schedule:work &
fi

exec "$@"

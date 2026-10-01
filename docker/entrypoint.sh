#!/bin/sh
# Synchro container entrypoint (app, horizon, scheduler).
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: docker compose run --rm app php artisan key:generate --show" >&2
    exit 1
fi

# Only the `app` service runs migrations, so a deploy migrates exactly once.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --isolated
fi

# Cache config, routes, views, and events from the container environment.
php artisan optimize

exec "$@"

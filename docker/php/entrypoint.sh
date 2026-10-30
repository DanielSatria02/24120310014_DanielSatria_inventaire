#!/bin/sh
set -eu

cd /var/www/html

APP_USER="www-data"

# Laravel needs these directories to exist and be writable.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# A config cache baked on the host would override the Docker environment
# variables, so drop it while developing.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php

# Create a local .env on first boot. Real environment variables (Compose)
# still take precedence because Laravel uses an immutable Dotenv repository.
if [ ! -f .env ]; then
    echo "Creating .env from .env.example"
    echo "Edit .env with your database credentials if you are not using the defaults."
    cp .env.example .env
fi

# The default .env.example uses SQLite. Keep the database file inside storage/
# so the PHP-FPM user can always write it (and its journal).
if grep -q '^DB_CONNECTION=sqlite' .env; then
    mkdir -p storage/app
    touch storage/app/database.sqlite
    chown -R "${APP_USER}:${APP_USER}" storage 2>/dev/null || true

    if ! grep -q '^DB_DATABASE=' .env; then
        export DB_DATABASE=/var/www/html/storage/app/database.sqlite
    fi
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "Generating application key"
    php artisan key:generate --force --no-interaction
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running migrations"
    if ! php artisan migrate --force --no-interaction; then
        echo "WARNING: migrations failed, starting the server anyway."
        echo "Check the DB_* values in .env, then run: docker compose exec app php artisan migrate"
    fi
fi

php artisan storage:link --no-interaction >/dev/null 2>&1 || true

# Hand generated files over to the PHP-FPM user. With the UID/GID build args
# this also matches the host user on Linux, so edits stay possible.
chown -R "${APP_USER}:${APP_USER}" storage bootstrap/cache 2>/dev/null || true

# Keep .env editable from the host even when the container user is remapped.
chmod 666 .env 2>/dev/null || true

exec "$@"
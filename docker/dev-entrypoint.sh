#!/bin/sh
set -e
[ -f vendor/autoload.php ] || composer install --no-interaction
[ -f .env ] || { cp .env.example .env; php artisan key:generate --force; }
# Wait for PostgreSQL, then migrate (web container only).
until php -r "new PDO('pgsql:host=${DB_HOST};port=5432;dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do sleep 1; done
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
  php artisan storage:link --quiet 2>/dev/null || true
fi
exec "$@"

#!/bin/sh
set -e
# Web container only (RUN_MIGRATIONS=1): migrations + cached config/routes/views.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  php artisan migrate --force
  php artisan storage:link --quiet 2>/dev/null || true
fi
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet
exec "$@"

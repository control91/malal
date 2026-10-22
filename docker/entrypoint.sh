#!/bin/sh
set -e
# Apply migrations on the web container only (RUN_MIGRATIONS=1), not on workers.
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  python manage.py migrate --noinput
fi
exec "$@"

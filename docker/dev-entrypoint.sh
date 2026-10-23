#!/bin/sh
set -e
# Waits for PostgreSQL, then applies migrations (web container only).
python - <<'PY'
import os, time
import psycopg
url = os.environ["DATABASE_URL"]
for attempt in range(30):
    try:
        psycopg.connect(url, connect_timeout=2).close()
        break
    except psycopg.OperationalError:
        time.sleep(1)
else:
    raise SystemExit("PostgreSQL is not reachable")
PY
if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  python manage.py migrate --noinput
fi
exec "$@"

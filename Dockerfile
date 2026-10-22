# syntax=docker/dockerfile:1.7

# ---- 1. Front-end assets (Tailwind build + vendored JS/fonts/icons) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY assets ./assets
COPY scripts/vendor.mjs ./scripts/vendor.mjs
COPY apps ./apps
COPY templates ./templates
COPY static ./static
RUN npm run build

# ---- 2. Python runtime ---------------------------------------------------------
FROM python:3.13-slim AS runtime
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    DJANGO_SETTINGS_MODULE=config.settings.prod
WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends libpq5 curl \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --system app && useradd --system --gid app --home /app app

COPY requirements ./requirements
RUN pip install -r requirements/prod.txt

COPY --chown=app:app . .
COPY --from=assets --chown=app:app /app/static ./static

# collectstatic needs a syntactically valid key only at build time.
RUN DJANGO_SECRET_KEY=build-only-$(head -c 48 /dev/urandom | base64 | tr -d '\n/+=') \
    DATABASE_URL=sqlite:////tmp/build.db python manage.py collectstatic --noinput \
    && mkdir -p /app/media && chown -R app:app /app/media /app/staticfiles

USER app
EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl -fsS -H "X-Forwarded-Proto: https" http://127.0.0.1:8000/health/ || exit 1
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["gunicorn", "config.wsgi:application", "--bind", "0.0.0.0:8000", "--workers", "3", "--threads", "2", "--timeout", "60", "--access-logfile", "-", "--forwarded-allow-ips", "*"]

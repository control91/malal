# Development image: dependencies only. The source code is bind-mounted by
# docker-compose.dev.yml, so edits on the host are picked up immediately.
FROM python:3.13-slim
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1 \
    DJANGO_SETTINGS_MODULE=config.settings.dev
WORKDIR /app

COPY requirements ./requirements
RUN pip install -r requirements/dev.txt

EXPOSE 8000
ENTRYPOINT ["/app/docker/dev-entrypoint.sh"]
CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]

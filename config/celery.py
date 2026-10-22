"""Celery application. Tasks are auto-discovered in each app's ``tasks.py``."""

import os

from celery import Celery

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings.dev")

app = Celery("malal")
app.config_from_object("django.conf:settings", namespace="CELERY")
app.autodiscover_tasks()

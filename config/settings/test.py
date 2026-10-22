"""Settings used by the automated test-suite."""

from .base import *  # noqa: F401,F403

DEBUG = False
SECRET_KEY = "test-secret-key-not-used-anywhere-else-0123456789abcdef"
PASSWORD_HASHERS = ["django.contrib.auth.hashers.MD5PasswordHasher"]
EMAIL_BACKEND = "django.core.mail.backends.locmem.EmailBackend"
CACHES = {"default": {"BACKEND": "django.core.cache.backends.locmem.LocMemCache"}}
CELERY_TASK_ALWAYS_EAGER = True
CELERY_BROKER_URL = "memory://"
FORM_MIN_SUBMIT_SECONDS = 0
MEDIA_ROOT = BASE_DIR / ".test-media"  # noqa: F405
ANALYTICS_ENABLED = True
AXES_ENABLED = True
LOGGING["root"]["level"] = "WARNING"  # noqa: F405

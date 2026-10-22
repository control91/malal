from django.apps import AppConfig


class CmsConfig(AppConfig):
    name = "apps.cms"
    verbose_name = "Contenus (CMS)"

    def ready(self):
        from . import signals  # noqa: F401

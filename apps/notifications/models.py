from django.conf import settings
from django.db import models
from django.utils.translation import gettext_lazy as _


class Notification(models.Model):
    class Level(models.TextChoices):
        INFO = "info", _("Information")
        SUCCESS = "success", _("Succès")
        WARNING = "warning", _("Attention")

    recipient = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="notifications")
    title = models.CharField(_("titre"), max_length=160)
    body = models.CharField(_("détail"), max_length=255, blank=True)
    url = models.CharField(_("lien"), max_length=255, blank=True)
    icon = models.CharField(_("icône"), max_length=40, default="bell")
    level = models.CharField(_("niveau"), max_length=10, choices=Level.choices, default=Level.INFO)
    read_at = models.DateTimeField(_("lu le"), null=True, blank=True)
    created_at = models.DateTimeField(_("date"), auto_now_add=True, db_index=True)

    class Meta:
        verbose_name = _("notification")
        verbose_name_plural = _("notifications")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return self.title

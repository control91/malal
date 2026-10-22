from django.conf import settings
from django.db import models
from django.utils.translation import gettext_lazy as _


class AuditLog(models.Model):
    class Action(models.TextChoices):
        LOGIN = "login", _("Connexion")
        LOGIN_FAILED = "login_failed", _("Échec de connexion")
        LOGOUT = "logout", _("Déconnexion")
        CREATE = "create", _("Création")
        UPDATE = "update", _("Modification")
        DELETE = "delete", _("Suppression")
        PUBLISH = "publish", _("Publication")
        UNPUBLISH = "unpublish", _("Dépublication")
        ROLE_CHANGE = "role_change", _("Changement de rôle")
        SETTINGS = "settings", _("Paramètres modifiés")
        STATUS = "status", _("Changement de statut")
        ASSIGN = "assign", _("Affectation")
        EMAIL = "email", _("Email envoyé")
        EXPORT = "export", _("Export de données")
        CONVERT = "convert", _("Conversion en client")

    created_at = models.DateTimeField(_("date"), auto_now_add=True, db_index=True)
    actor = models.ForeignKey(settings.AUTH_USER_MODEL, verbose_name=_("auteur"), null=True, blank=True, on_delete=models.SET_NULL, related_name="audit_entries")
    actor_label = models.CharField(_("auteur (texte)"), max_length=255, blank=True)
    action = models.CharField(_("action"), max_length=20, choices=Action.choices, db_index=True)
    object_type = models.CharField(_("type d'objet"), max_length=100, blank=True, db_index=True)
    object_id = models.CharField(_("identifiant"), max_length=64, blank=True)
    object_repr = models.CharField(_("objet"), max_length=255, blank=True)
    changes = models.JSONField(_("détails"), default=dict, blank=True)
    ip_address = models.GenericIPAddressField(_("adresse IP"), null=True, blank=True)
    user_agent = models.CharField(_("navigateur"), max_length=255, blank=True)

    class Meta:
        verbose_name = _("entrée d'audit")
        verbose_name_plural = _("journal d'audit")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return f"{self.get_action_display()} · {self.object_repr or self.object_type}"

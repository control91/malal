"""
Role-based access control for the back-office.

Each role grants a set of *capabilities* ``"<area>.<action>"`` where action is
``view`` or ``edit`` (edit implies view). Every dashboard view declares the
capability it needs and the check happens server-side for each request.
"""

from django.db import models
from django.utils.translation import gettext_lazy as _


class Role(models.TextChoices):
    SUPERADMIN = "superadmin", _("Super administrateur")
    ADMIN = "admin", _("Administrateur")
    EDITOR = "editor", _("Éditeur de contenu")
    SALES = "sales", _("Commercial")
    SUPPORT = "support", _("Support")
    VIEWER = "viewer", _("Lecteur")


class Area(models.TextChoices):
    CONTENT = "content", _("Contenus du site")
    CATALOG = "catalog", _("Catalogue et tarifs")
    BLOG = "blog", _("Articles")
    PORTFOLIO = "portfolio", _("Réalisations")
    MEDIA = "media", _("Médiathèque")
    INQUIRIES = "inquiries", _("Demandes entrantes")
    CRM = "crm", _("Prospects (CRM)")
    NEWSLETTER = "newsletter", _("Newsletter")
    ANALYTICS = "analytics", _("Statistiques")
    USERS = "users", _("Utilisateurs")
    SETTINGS = "settings", _("Configuration")
    AUDIT = "audit", _("Journal d'audit")


ALL_AREAS = [area.value for area in Area]
EDITORIAL = ["content", "catalog", "blog", "portfolio", "media"]


def _caps(edit=(), view=()) -> frozenset[str]:
    caps = {f"{a}.edit" for a in edit} | {f"{a}.view" for a in edit} | {f"{a}.view" for a in view}
    return frozenset(caps)


ROLE_CAPABILITIES: dict[str, frozenset[str]] = {
    Role.SUPERADMIN: _caps(edit=ALL_AREAS),
    Role.ADMIN: _caps(edit=ALL_AREAS),
    Role.EDITOR: _caps(edit=EDITORIAL, view=["analytics"]),
    Role.SALES: _caps(edit=["inquiries", "crm", "newsletter"], view=["catalog", "analytics"]),
    Role.SUPPORT: _caps(edit=["inquiries"], view=["crm", "catalog"]),
    Role.VIEWER: _caps(view=[a for a in ALL_AREAS if a not in ("users", "settings", "audit")]),
}


def capabilities_for(user) -> frozenset[str]:
    if not getattr(user, "is_authenticated", False) or not user.is_active:
        return frozenset()
    if user.is_superuser:
        return ROLE_CAPABILITIES[Role.SUPERADMIN]
    if not user.is_staff:
        return frozenset()
    return ROLE_CAPABILITIES.get(user.role, frozenset())


def has_capability(user, capability: str) -> bool:
    return capability in capabilities_for(user)

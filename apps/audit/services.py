"""Single entry point to write the audit trail."""

import logging
from typing import Any

from django.db import models

from apps.core.middleware import client_ip

from .models import AuditLog

logger = logging.getLogger("apps.audit")

SENSITIVE_FIELDS = {"password", "password1", "password2", "token", "secret"}


def _jsonable(value: Any) -> Any:
    if isinstance(value, models.Model):
        return str(value)
    if isinstance(value, (list, tuple, set)) or hasattr(value, "all"):
        items = value.all() if hasattr(value, "all") else value
        return [_jsonable(v) for v in items]
    if hasattr(value, "name") and hasattr(value, "url"):  # files
        return getattr(value, "name", "")
    if isinstance(value, (str, int, float, bool)) or value is None:
        return value
    return str(value)


def form_changes(form) -> dict[str, Any]:
    """Changed fields of a bound ModelForm, secrets redacted, values truncated."""
    changes = {}
    for name in form.changed_data:
        if name in SENSITIVE_FIELDS:
            changes[name] = "***"
            continue
        value = _jsonable(form.cleaned_data.get(name))
        if isinstance(value, str) and len(value) > 300:
            value = value[:300] + "…"
        changes[name] = value
    return changes


def record(request, action: str, obj=None, *, changes: dict | None = None, object_repr: str = "", actor=None) -> AuditLog:
    user = actor if actor is not None else getattr(request, "user", None)
    if user is not None and not getattr(user, "is_authenticated", False):
        user = None
    entry = AuditLog.objects.create(
        actor=user,
        actor_label=str(user) if user else "",
        action=action,
        object_type=obj._meta.label if obj is not None else "",
        object_id=str(obj.pk) if obj is not None and obj.pk else "",
        object_repr=(object_repr or (str(obj) if obj is not None else ""))[:255],
        changes=changes or {},
        ip_address=(client_ip(request) or None) if request is not None else None,
        user_agent=(request.META.get("HTTP_USER_AGENT", "")[:255] if request is not None else ""),
    )
    logger.info("audit %s %s %s by %s", action, entry.object_type, entry.object_id, entry.actor_label or "-")
    return entry

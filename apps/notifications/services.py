"""Notification helpers: in-app notifications and (asynchronous) emails."""

import logging

from django.contrib.auth import get_user_model
from django.template.loader import render_to_string

from apps.accounts.roles import capabilities_for

from .models import Notification
from .tasks import send_email_task

logger = logging.getLogger("apps.notifications")


def send_email(subject: str, template: str, context: dict, to: list[str], reply_to: list[str] | None = None) -> None:
    """Render ``<template>.txt`` and queue it. Failures are logged, never raised to the visitor."""
    to = [address for address in to if address]
    if not to:
        return
    from django.conf import settings

    body = render_to_string(f"{template}.txt", {"SITE_URL": settings.SITE_URL, **context})
    try:
        send_email_task.delay(subject, body, to, reply_to)
    except Exception:  # broker down: fall back to a synchronous attempt
        logger.exception("Could not queue email, sending synchronously")
        try:
            send_email_task.run(subject, body, to, reply_to)
        except Exception:
            logger.exception("Email delivery failed: %s", subject)


def notify_capability(capability: str, *, title: str, body: str = "", url: str = "", icon: str = "bell", level: str = "info") -> int:
    """Create an in-app notification for every active staff user holding ``capability``."""
    users = get_user_model().objects.filter(is_active=True, is_staff=True)
    created = [
        Notification(recipient=user, title=title, body=body[:255], url=url, icon=icon, level=level)
        for user in users
        if capability in capabilities_for(user)
    ]
    Notification.objects.bulk_create(created)
    return len(created)

from django.contrib.auth.signals import user_logged_in, user_logged_out, user_login_failed
from django.dispatch import receiver

from .models import AuditLog
from .services import record


@receiver(user_logged_in)
def on_login(sender, request, user, **kwargs):
    record(request, AuditLog.Action.LOGIN, user, actor=user)


@receiver(user_logged_out)
def on_logout(sender, request, user, **kwargs):
    if user is not None:
        record(request, AuditLog.Action.LOGOUT, user, actor=user)


@receiver(user_login_failed)
def on_login_failed(sender, credentials, request=None, **kwargs):
    username = str(credentials.get("username", ""))[:120]
    record(request, AuditLog.Action.LOGIN_FAILED, None, object_repr=username, changes={"username": username})

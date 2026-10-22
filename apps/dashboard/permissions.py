"""Server-side access checks for every back-office view."""

from functools import wraps

from django.contrib.auth.views import redirect_to_login
from django.core.exceptions import PermissionDenied

from apps.accounts.roles import has_capability


def check_capability(request, capability: str) -> None:
    user = request.user
    if not user.is_authenticated:
        raise _LoginRequired
    if not user.is_staff or not has_capability(user, capability):
        raise PermissionDenied(f"Missing capability {capability}")


class _LoginRequired(Exception):
    pass


def capability_required(capability: str):
    """Decorator: login + staff + capability, evaluated on each request."""

    def decorator(view):
        @wraps(view)
        def wrapper(request, *args, **kwargs):
            try:
                check_capability(request, capability)
            except _LoginRequired:
                return redirect_to_login(request.get_full_path())
            return view(request, *args, **kwargs)

        wrapper.capability = capability
        return wrapper

    return decorator


def staff_required(view):
    @wraps(view)
    def wrapper(request, *args, **kwargs):
        if not request.user.is_authenticated:
            return redirect_to_login(request.get_full_path())
        if not request.user.is_staff:
            raise PermissionDenied
        return view(request, *args, **kwargs)

    return wrapper

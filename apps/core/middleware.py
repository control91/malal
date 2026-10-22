import ipaddress
import logging
import uuid

from django.conf import settings
from django.http import HttpResponseForbidden

from .logging import set_request_id

logger = logging.getLogger("apps.security")


def client_ip(request) -> str:
    """Client IP, trusting X-Forwarded-For only behind the configured number of proxies."""
    proxies = getattr(settings, "AXES_IPWARE_PROXY_COUNT", 0)
    forwarded = request.META.get("HTTP_X_FORWARDED_FOR", "")
    if proxies and forwarded:
        chain = [part.strip() for part in forwarded.split(",") if part.strip()]
        if len(chain) >= proxies:
            return chain[-proxies]
    return request.META.get("REMOTE_ADDR", "")


class RequestIDMiddleware:
    """Tags every request (and its log lines) with an id, echoed in X-Request-ID."""

    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        incoming = request.META.get("HTTP_X_REQUEST_ID", "")
        request_id = incoming[:64] if incoming.replace("-", "").isalnum() else uuid.uuid4().hex
        request.request_id = request_id
        set_request_id(request_id)
        try:
            response = self.get_response(request)
        finally:
            set_request_id(None)
        response["X-Request-ID"] = request_id
        return response


class AdminAccessMiddleware:
    """Optional IP allow-list for the back-office (dashboard and Django admin)."""

    def __init__(self, get_response):
        self.get_response = get_response
        self.networks = [ipaddress.ip_network(item, strict=False) for item in settings.ADMIN_ALLOWED_IPS]
        self.prefixes = ("/" + settings.DASHBOARD_URL, "/" + settings.ADMIN_URL)

    def __call__(self, request):
        if self.networks and request.path.startswith(self.prefixes):
            try:
                ip = ipaddress.ip_address(client_ip(request))
            except ValueError:
                ip = None
            if ip is None or not any(ip in net for net in self.networks):
                logger.warning("Back-office access denied for %s on %s", ip, request.path)
                return HttpResponseForbidden("Accès refusé.")
        return self.get_response(request)

import hashlib
import logging
import re

from django.conf import settings
from django.db.models import F
from django.utils import timezone

from apps.core.middleware import client_ip

from .models import DailyPageView, DailyVisitor

logger = logging.getLogger("apps.analytics")

BOT_PATTERN = re.compile(r"bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse", re.I)
EXCLUDED_PREFIXES = ("/static/", "/media/", "/api/", "/health", "/favicon", "/robots.txt", "/sitemap", "/comptes/")


class PageViewMiddleware:
    """Counts successful HTML GET requests on the public site."""

    def __init__(self, get_response):
        self.get_response = get_response
        self.excluded = EXCLUDED_PREFIXES + ("/" + settings.DASHBOARD_URL, "/" + settings.ADMIN_URL)

    def __call__(self, request):
        response = self.get_response(request)
        if settings.ANALYTICS_ENABLED and self._should_count(request, response):
            try:
                self._record(request)
            except Exception:  # analytics must never break a page
                logger.warning("page view not recorded", exc_info=True)
        return response

    def _should_count(self, request, response) -> bool:
        if request.method != "GET" or response.status_code != 200:
            return False
        if request.path.startswith(self.excluded) or request.headers.get("HX-Request"):
            return False
        if "text/html" not in response.get("Content-Type", ""):
            return False
        if request.headers.get("DNT") == "1" or request.headers.get("Sec-GPC") == "1":
            return False
        user = getattr(request, "user", None)
        if user is not None and user.is_authenticated and user.is_staff:
            return False
        return not BOT_PATTERN.search(request.META.get("HTTP_USER_AGENT", ""))

    def _record(self, request) -> None:
        today = timezone.localdate()
        path = request.path[:255]
        updated = DailyPageView.objects.filter(date=today, path=path).update(views=F("views") + 1)
        if not updated:
            DailyPageView.objects.bulk_create([DailyPageView(date=today, path=path, views=1)], ignore_conflicts=True)
        raw = f"{settings.SECRET_KEY}|{today}|{client_ip(request)}|{request.META.get('HTTP_USER_AGENT', '')}"
        visitor = hashlib.sha256(raw.encode()).hexdigest()[:32]
        DailyVisitor.objects.bulk_create([DailyVisitor(date=today, visitor=visitor)], ignore_conflicts=True)

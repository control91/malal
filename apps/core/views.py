import logging

from django.conf import settings
from django.db import connection
from django.http import HttpResponse, JsonResponse
from django.shortcuts import render
from django.views.decorators.cache import cache_page, never_cache
from django.views.decorators.http import require_GET

logger = logging.getLogger("apps.core")


@require_GET
@cache_page(60 * 60)
def robots_txt(request):
    lines = [
        "User-agent: *",
        f"Disallow: /{settings.DASHBOARD_URL}",
        f"Disallow: /{settings.ADMIN_URL}",
        "Disallow: /comptes/",
        "Disallow: /merci/",
        "Disallow: /newsletter/",
        "",
        f"Sitemap: {settings.SITE_URL}/sitemap.xml",
    ]
    return HttpResponse("\n".join(lines), content_type="text/plain")


@never_cache
def health(request):
    """Liveness/readiness probe used by Docker and the reverse proxy."""
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT 1")
        db = "ok"
    except Exception:
        logger.exception("health check: database unavailable")
        db = "error"
    status = 200 if db == "ok" else 503
    return JsonResponse({"status": "ok" if status == 200 else "degraded", "database": db}, status=status)


def error_400(request, exception=None):
    return render(request, "errors/400.html", status=400)


def error_403(request, exception=None):
    return render(request, "errors/403.html", status=403)


def error_404(request, exception=None):
    return render(request, "errors/404.html", status=404)


def error_500(request):
    # Rendered without the context processors to survive a broken database.
    from django.template import loader

    return HttpResponse(loader.get_template("errors/500.html").render({}), status=500)


def csrf_failure(request, reason=""):
    return render(request, "errors/403_csrf.html", status=403)

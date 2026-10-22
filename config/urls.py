from django.conf import settings
from django.conf.urls.i18n import i18n_patterns
from django.contrib import admin
from django.contrib.sitemaps.views import sitemap
from django.urls import include, path
from django.views.decorators.cache import cache_page
from django.views.i18n import set_language

from apps.cms.urls import page_urlpatterns
from apps.core import views as core_views
from apps.core.sitemaps import SITEMAPS

admin.site.site_header = "Administration de secours"
admin.site.site_title = "Administration"

urlpatterns = [
    path("robots.txt", core_views.robots_txt, name="robots"),
    path("sitemap.xml", cache_page(60 * 30)(sitemap), {"sitemaps": SITEMAPS}, name="sitemap"),
    path("health/", core_views.health, name="health"),
    path("i18n/setlang/", set_language, name="set_language"),
    path("api/", include("apps.api.urls")),
    path(settings.ADMIN_URL, admin.site.urls),
    path(settings.DASHBOARD_URL, include("apps.dashboard.urls")),
    path("comptes/", include("apps.accounts.urls")),
]

# Public site. French is the default language and has no URL prefix (/en/... later).
urlpatterns += i18n_patterns(
    path("", include("apps.cms.urls")),
    path("", include("apps.services.urls")),
    path("", include("apps.products.urls")),
    path("", include("apps.pricing.urls")),
    path("", include("apps.blog.urls")),
    path("", include("apps.portfolio.urls")),
    path("", include("apps.crm.urls")),
    path("", include((page_urlpatterns, "cms_pages"))),
    prefix_default_language=False,
)

handler400 = "apps.core.views.error_400"
handler403 = "apps.core.views.error_403"
handler404 = "apps.core.views.error_404"
handler500 = "apps.core.views.error_500"

if settings.DEBUG:
    from django.conf.urls.static import static

    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)

import time

from django.conf import settings
from django.core import signing

from .models import SiteSettings


def site(request):
    """Global template context: site settings, navigation, request flags."""
    from apps.cms.models import NavigationMenu

    path = request.path
    is_backoffice = path.startswith(("/" + settings.DASHBOARD_URL, "/comptes/", "/" + settings.ADMIN_URL))
    context = {
        "site": SiteSettings.load(),
        "SITE_URL": settings.SITE_URL,
        "is_backoffice": is_backoffice,
    }
    from .antispam import SIGNER_SALT

    # Menus are cached; error pages rendered under /dashboard/ also need them.
    context["menus"] = NavigationMenu.cached_menus()
    context["antispam_ts"] = "" if is_backoffice else signing.dumps(int(time.time()), salt=SIGNER_SALT)
    return context

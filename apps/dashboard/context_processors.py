from django.conf import settings
from django.urls import reverse

from apps.accounts.roles import capabilities_for

from .crud import registry

GROUP_ORDER = ["Commercial", "Site", "Catalogue", "Éditorial", "Administration"]
GROUP_ICONS = {"Commercial": "briefcase", "Site": "panels-top-left", "Catalogue": "package", "Éditorial": "notebook-pen", "Administration": "shield"}


def dashboard(request):
    """Sidebar navigation, filtered by the user's capabilities (UI only — views re-check)."""
    if not request.path.startswith("/" + settings.DASHBOARD_URL) or not request.user.is_authenticated:
        return {}
    caps = capabilities_for(request.user)
    groups: dict[str, list[dict]] = {name: [] for name in GROUP_ORDER}
    extra = {
        "Commercial": [
            ("crm.view", "Pipeline", "kanban", reverse("dashboard:pipeline"), 1),
            ("crm.view", "Relances", "calendar-check", reverse("dashboard:followups"), 2),
        ],
        "Administration": [
            ("analytics.view", "Statistiques", "chart-line", reverse("dashboard:analytics"), 0),
            ("settings.view", "Configuration", "settings", reverse("dashboard:settings"), 1),
        ],
    }
    for resource in registry.values():
        if resource.show_in_menu and resource.view_cap in caps:
            groups.setdefault(resource.group, []).append({"label": resource.label_plural, "icon": resource.icon, "url": resource.url("list")})
    for group, entries in extra.items():
        for cap, label, icon, url, position in entries:
            if cap in caps:
                groups[group].insert(min(position, len(groups[group])), {"label": label, "icon": icon, "url": url})
    # Highlight the most specific matching entry only.
    matches = [item for items in groups.values() for item in items if request.path.startswith(item["url"])]
    if matches:
        max(matches, key=lambda item: len(item["url"]))["active"] = True
    nav = [{"title": name, "icon": GROUP_ICONS.get(name, "circle"), "items": items} for name, items in groups.items() if items]
    return {
        "dashboard_nav": nav,
        "caps": caps,
        "unread_notifications": request.user.notifications.filter(read_at__isnull=True).count(),
        "new_inquiries": _new_inquiries(caps),
    }


def _new_inquiries(caps) -> int:
    if "inquiries.view" not in caps:
        return 0
    from apps.crm.models import Inquiry

    return Inquiry.objects.filter(status=Inquiry.Status.NEW).count()

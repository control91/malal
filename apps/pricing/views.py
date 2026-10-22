from django.db.models import Prefetch
from django.shortcuts import render

from apps.core.models import SiteSettings
from apps.core.seo import build_seo

from .models import PricingGroup, PricingPlan


def pricing(request):
    groups = PricingGroup.objects.filter(is_published=True).prefetch_related(
        Prefetch("plans", queryset=PricingPlan.objects.filter(is_published=True).prefetch_related("features")), "options"
    ).select_related("category", "product")
    tables = []
    for group in groups:
        plans, rows = group.comparison()
        if plans:
            tables.append({"group": group, "plans": plans, "rows": rows})
    seo = build_seo(request, title="Tarifs", description="Des offres transparentes, mensuelles ou annuelles, et des devis sur mesure.")
    return render(request, "public/pricing.html", {"tables": tables, "site": SiteSettings.load(), "seo": seo})

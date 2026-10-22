from django.shortcuts import get_object_or_404, render

from apps.core.models import Availability, SiteSettings
from apps.core.seo import breadcrumb_ld, build_seo

from .models import ServiceCategory, StatusComponent


def category_detail(request, slug):
    category = get_object_or_404(ServiceCategory, slug=slug, is_published=True)
    services = list(category.services.filter(is_published=True))
    groups = [
        (status, label, [s for s in services if s.status == status])
        for status, label in Availability.choices
    ]
    products = category.products.published()
    faqs = category.faqs.filter(is_published=True)
    case_studies = category.case_studies.published()[:3]
    seo = build_seo(request, obj=category, title=category.name, description=category.tagline or category.summary)
    seo.json_ld = [
        breadcrumb_ld([("Accueil", "/"), (category.name, category.get_absolute_url())]),
        {
            "@context": "https://schema.org",
            "@type": "Service",
            "name": category.name,
            "description": category.summary,
            "provider": {"@type": "Organization", "name": SiteSettings.load().company_name},
            "areaServed": "Africa",
        },
    ]
    return render(request, "public/category_detail.html", {
        "category": category,
        "service_groups": [g for g in groups if g[2]],
        "products": products,
        "faqs": faqs,
        "case_studies": case_studies,
        "seo": seo,
    })


def status_page(request):
    components = StatusComponent.objects.filter(is_published=True)
    overall = "operational"
    states = {c.state for c in components}
    for state in ("outage", "degraded", "maintenance"):
        if state in states:
            overall = state
            break
    seo = build_seo(request, title="Statut des services", description="État des services en production.")
    return render(request, "public/status.html", {"components": components, "overall": overall, "seo": seo})

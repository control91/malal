from django.core.paginator import Paginator
from django.db.models import Q
from django.shortcuts import get_object_or_404, render

from apps.core.models import Availability, SiteSettings
from apps.core.seo import absolute, breadcrumb_ld, build_seo
from apps.services.models import ServiceCategory

from .models import Product


def catalog(request):
    products = Product.objects.published().select_related("category")
    categories = ServiceCategory.objects.filter(is_published=True, products__is_published=True).distinct()
    q = request.GET.get("q", "").strip()[:100]
    category = request.GET.get("domaine", "")
    status = request.GET.get("statut", "")
    if q:
        products = products.filter(Q(name__icontains=q) | Q(tagline__icontains=q) | Q(summary__icontains=q) | Q(features__title__icontains=q)).distinct()
    if category:
        products = products.filter(category__slug=category)
    if status in Availability.values:
        products = products.filter(status=status)
    page = Paginator(products, 12).get_page(request.GET.get("page"))
    context = {
        "page_obj": page,
        "categories": categories,
        "statuses": Availability.choices,
        "filters": {"q": q, "domaine": category, "statut": status},
        "seo": build_seo(request, title="Catalogue des solutions", description="Logiciels SaaS, services Cloud et solutions d'IA : découvrez notre catalogue."),
    }
    template = "public/partials/catalog_results.html" if request.headers.get("HX-Request") else "public/catalog.html"
    return render(request, template, context)


def product_detail(request, slug):
    product = get_object_or_404(Product.objects.published().select_related("category"), slug=slug)
    groups = product.pricing_groups.filter(is_published=True).prefetch_related("plans__features") if product.show_pricing else []
    related = Product.objects.published().filter(category=product.category).exclude(pk=product.pk)[:3]
    seo = build_seo(request, obj=product, title=product.name, description=product.tagline, image=product.logo or None)
    site = SiteSettings.load()
    seo.json_ld = [
        breadcrumb_ld([("Accueil", "/"), ("Solutions", "/solutions/"), (product.name, product.get_absolute_url())]),
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": product.name,
            "description": product.summary,
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web",
            "publisher": {"@type": "Organization", "name": site.company_name},
            **({"image": absolute(product.logo.url)} if product.logo else {}),
        },
    ]
    return render(request, "public/product_detail.html", {"product": product, "pricing_groups": groups, "related": related, "seo": seo})

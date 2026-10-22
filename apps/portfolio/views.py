from django.shortcuts import get_object_or_404, render

from apps.core.seo import breadcrumb_ld, build_seo

from .models import CaseStudy, Testimonial


def case_list(request):
    cases = CaseStudy.objects.published().prefetch_related("services")
    sector = request.GET.get("secteur", "")
    sectors = sorted({c.sector for c in cases})
    if sector:
        cases = [c for c in cases if c.sector == sector]
    seo = build_seo(request, title="Réalisations", description="Projets menés et résultats obtenus avec nos clients.")
    return render(request, "public/case_list.html", {"cases": cases, "sectors": sectors, "current_sector": sector, "testimonials": Testimonial.objects.published()[:6], "seo": seo})


def case_detail(request, slug):
    case = get_object_or_404(CaseStudy.objects.published(), slug=slug)
    seo = build_seo(request, obj=case, title=case.title, description=case.summary, image=case.cover or None)
    seo.json_ld = [breadcrumb_ld([("Accueil", "/"), ("Réalisations", "/realisations/"), (case.title, case.get_absolute_url())])]
    others = CaseStudy.objects.published().exclude(pk=case.pk)[:2]
    return render(request, "public/case_detail.html", {"case": case, "testimonials": case.testimonials.published(), "others": others, "seo": seo})

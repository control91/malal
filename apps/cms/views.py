from django.http import Http404
from django.shortcuts import get_object_or_404, render

from apps.core.models import PublishStatus
from apps.core.seo import breadcrumb_ld, build_seo, organization_ld

from .models import FAQ, JobOpening, Page, Partner
from .sections import load_sections


def _published_page(request, slug):
    page = Page.objects.filter(slug=slug).first()
    if page is None:
        return None
    if page.status != PublishStatus.PUBLISHED and not (request.user.is_authenticated and request.user.is_staff):
        return None
    return page


def home(request):
    page = _published_page(request, "accueil")
    sections = load_sections(page) if page else []
    from apps.core.models import SiteSettings

    site = SiteSettings.load()
    seo = build_seo(request, obj=page, title=site.meta_title or site.company_name, description=site.meta_description or site.tagline)
    seo.json_ld = [organization_ld(site), {"@context": "https://schema.org", "@type": "WebSite", "name": site.company_name, "url": seo.canonical}]
    return render(request, "public/home.html", {"page": page, "sections": sections, "seo": seo})


def page_detail(request, slug):
    page = _published_page(request, slug)
    if page is None or slug == "accueil":
        raise Http404
    sections = load_sections(page)
    seo = build_seo(request, obj=page, title=page.title, description=page.intro)
    seo.json_ld = [breadcrumb_ld([("Accueil", "/"), (page.title, page.get_absolute_url())])]
    template = "public/page_legal.html" if page.template == Page.Template.LEGAL else "public/page.html"
    return render(request, template, {"page": page, "sections": sections, "seo": seo})


def faq(request):
    from apps.services.models import ServiceCategory

    questions = FAQ.objects.filter(is_published=True).select_related("category")
    groups = [("Général", [q for q in questions if q.category_id is None])]
    for category in ServiceCategory.objects.filter(is_published=True):
        items = [q for q in questions if q.category_id == category.pk]
        if items:
            groups.append((category.name, items))
    groups = [g for g in groups if g[1]]
    seo = build_seo(request, title="Questions fréquentes", description="Réponses aux questions les plus courantes sur nos services Cloud, SaaS et IA.")
    seo.json_ld = [{
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {"@type": "Question", "name": q.question, "acceptedAnswer": {"@type": "Answer", "text": q.answer}} for q in questions
        ],
    }] if questions else []
    return render(request, "public/faq.html", {"groups": groups, "seo": seo})


def careers(request):
    jobs = JobOpening.objects.open()
    seo = build_seo(request, title="Carrières", description="Rejoignez une équipe qui construit des services numériques pour l'Afrique.")
    return render(request, "public/careers.html", {"jobs": jobs, "seo": seo})


def job_detail(request, slug):
    job = get_object_or_404(JobOpening.objects.open(), slug=slug)
    seo = build_seo(request, title=job.title, description=job.summary)
    seo.json_ld = [{
        "@context": "https://schema.org",
        "@type": "JobPosting",
        "title": job.title,
        "description": job.description,
        "datePosted": job.created_at.date().isoformat(),
        "employmentType": {"cdi": "FULL_TIME", "cdd": "TEMPORARY", "internship": "INTERN", "freelance": "CONTRACTOR"}[job.contract],
        "jobLocation": {"@type": "Place", "address": {"@type": "PostalAddress", "addressLocality": job.location, "addressCountry": "GN"}},
        **({"validThrough": job.closes_on.isoformat()} if job.closes_on else {}),
    }]
    return render(request, "public/job_detail.html", {"job": job, "seo": seo})


def partners(request):
    items = Partner.objects.filter(is_published=True, agreement_confirmed=True)
    seo = build_seo(request, title="Partenaires", description="Les organisations avec lesquelles nous collaborons.")
    return render(request, "public/partners.html", {"partners": items, "seo": seo})

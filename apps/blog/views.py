from django.core.paginator import Paginator
from django.db.models import Q
from django.shortcuts import get_object_or_404, render

from apps.core.models import SiteSettings
from apps.core.seo import absolute, breadcrumb_ld, build_seo

from .models import BlogArticle, BlogCategory, BlogTag


def _listing(request, queryset, *, title, description, heading=None, extra=None):
    q = request.GET.get("q", "").strip()[:100]
    if q:
        queryset = queryset.filter(Q(title__icontains=q) | Q(excerpt__icontains=q) | Q(body__icontains=q))
    page = Paginator(queryset.select_related("category", "author").prefetch_related("tags"), 9).get_page(request.GET.get("page"))
    context = {
        "page_obj": page,
        "categories": BlogCategory.objects.filter(articles__isnull=False).distinct(),
        "q": q,
        "heading": heading or title,
        "seo": build_seo(request, title=title, description=description),
        **(extra or {}),
    }
    return render(request, "public/blog_list.html", context)


def article_list(request):
    return _listing(request, BlogArticle.objects.published(), title="Actualités et ressources",
                    description="Analyses, guides et actualités sur le Cloud, le SaaS et l'IA en Afrique.")


def category(request, slug):
    cat = get_object_or_404(BlogCategory, slug=slug)
    return _listing(request, BlogArticle.objects.published().filter(category=cat), title=cat.name,
                    description=cat.description, extra={"current_category": cat})


def tag(request, slug):
    t = get_object_or_404(BlogTag, slug=slug)
    return _listing(request, BlogArticle.objects.published().filter(tags=t), title=f"#{t.name}",
                    description=f"Articles étiquetés {t.name}.", extra={"current_tag": t})


def article_detail(request, slug):
    queryset = BlogArticle.objects.all() if (request.user.is_authenticated and request.user.is_staff) else BlogArticle.objects.published()
    article = get_object_or_404(queryset.select_related("category", "author"), slug=slug)
    site = SiteSettings.load()
    seo = build_seo(request, obj=article, title=article.title, description=article.excerpt, image=article.cover or None, type="article")
    seo.noindex = seo.noindex or not article.is_live
    if article.published_at:
        seo.published_time = article.published_at.isoformat()
    seo.json_ld = [
        breadcrumb_ld([("Accueil", "/"), ("Blog", "/blog/"), (article.title, article.get_absolute_url())]),
        {
            "@context": "https://schema.org",
            "@type": "BlogPosting",
            "headline": article.title,
            "description": article.excerpt,
            "datePublished": article.published_at.isoformat() if article.published_at else None,
            "dateModified": article.updated_at.isoformat(),
            "author": {"@type": "Person", "name": str(article.author)} if article.author else {"@type": "Organization", "name": site.company_name},
            "publisher": {"@type": "Organization", "name": site.company_name},
            "mainEntityOfPage": absolute(article.get_absolute_url()),
            **({"image": absolute(article.cover.url)} if article.cover else {}),
        },
    ]
    return render(request, "public/blog_detail.html", {"article": article, "related": article.related(), "seo": seo, "preview": not article.is_live})

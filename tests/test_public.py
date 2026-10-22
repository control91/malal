import pytest
from django.urls import reverse
from django.utils import timezone

from apps.blog.models import BlogArticle
from apps.core.models import PublishStatus
from apps.portfolio.models import CaseStudy
from apps.products.models import Product
from apps.services.models import ServiceCategory

pytestmark = pytest.mark.django_db

PAGES = ["/", "/entreprise/", "/expertises/cloud-computing/", "/solutions/", "/tarifs/", "/blog/", "/realisations/",
         "/contact/", "/devis/", "/demo/", "/faq/", "/carrieres/", "/partenaires/", "/statut/", "/mentions-legales/",
         "/confidentialite/", "/conditions-generales/", "/ressources/"]


@pytest.mark.parametrize("url", PAGES)
def test_public_pages_render(client, site_content, url):
    response = client.get(url)
    assert response.status_code == 200
    assert b"<title>" in response.content


def test_home_content_is_editable_from_database(client, site_content):
    from apps.cms.models import PageSection

    hero = PageSection.objects.get(page__slug="accueil", kind="hero")
    hero.title = "Titre modifié depuis le CMS"
    hero.save()
    assert "Titre modifié depuis le CMS" in client.get("/").content.decode()


def test_unknown_page_returns_custom_404(client, site_content):
    response = client.get("/cette-page-nexiste-pas/")
    assert response.status_code == 404
    assert "introuvable" in response.content.decode()


def test_draft_page_hidden_from_public(client, site_content):
    from apps.cms.models import Page

    Page.objects.create(title="Brouillon", slug="brouillon", status=PublishStatus.DRAFT)
    assert client.get("/brouillon/").status_code == 404


def test_scheduled_article_is_not_public_until_due(client, site_content):
    future = BlogArticle.objects.create(title="Futur", slug="futur", body="<p>x</p>", status=PublishStatus.PUBLISHED,
                                        published_at=timezone.now() + timezone.timedelta(days=2))
    assert future.is_scheduled
    assert client.get(reverse("blog:detail", args=["futur"])).status_code == 404
    assert "Futur" not in client.get("/blog/").content.decode()
    future.published_at = timezone.now() - timezone.timedelta(minutes=1)
    future.save()
    assert client.get(reverse("blog:detail", args=["futur"])).status_code == 200


def test_rich_text_is_sanitized_on_save(db):
    article = BlogArticle.objects.create(title="XSS", slug="xss", body='<p onclick="evil()">ok</p><script>alert(1)</script><a href="javascript:x">l</a>')
    assert "<script" not in article.body
    assert "onclick" not in article.body
    assert "javascript:" not in article.body


def test_catalog_filters_and_htmx_partial(client, site_content):
    cloud = ServiceCategory.objects.get(slug="cloud-computing")
    Product.objects.create(category=cloud, name="Alpha Cloud", slug="alpha", tagline="t", summary="s", is_published=True)
    Product.objects.create(category=cloud, name="Hidden", slug="hidden", tagline="t", summary="s", is_published=False)
    response = client.get("/solutions/?q=alpha", HTTP_HX_REQUEST="true")
    body = response.content.decode()
    assert "Alpha Cloud" in body and "Hidden" not in body
    assert "<html" not in body  # partial only


def test_case_study_requires_consent_unless_example(client, db):
    case = CaseStudy.objects.create(title="Projet", slug="projet", sector="Banque", summary="s", challenge="c", solution="s", status=PublishStatus.PUBLISHED)
    assert client.get("/realisations/projet/").status_code == 404
    case.publication_consent = True
    case.save()
    assert client.get("/realisations/projet/").status_code == 200


def test_seo_assets(client, site_content):
    robots = client.get("/robots.txt").content.decode()
    assert "Disallow: /dashboard/" in robots and "Sitemap:" in robots
    sitemap = client.get("/sitemap.xml").content.decode()
    assert "/expertises/cloud-computing/" in sitemap
    home = client.get("/").content.decode()
    assert 'rel="canonical"' in home and 'property="og:title"' in home and "application/ld+json" in home


def test_security_headers(client, site_content):
    response = client.get("/")
    assert response["X-Frame-Options"] == "DENY"
    assert "Content-Security-Policy" in response
    assert response["X-Request-ID"]


def test_health_endpoint(client, db):
    assert client.get("/health/").json() == {"status": "ok", "database": "ok"}


def test_public_api(client, site_content):
    data = client.get("/api/v1/expertises/").json()
    assert data["count"] >= 6
    assert client.post("/api/v1/expertises/", {}).status_code in (401, 403, 405)

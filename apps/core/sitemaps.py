from django.contrib.sitemaps import Sitemap
from django.urls import reverse

from apps.blog.models import BlogArticle
from apps.cms.models import JobOpening, Page
from apps.core.models import PublishStatus
from apps.portfolio.models import CaseStudy
from apps.products.models import Product
from apps.services.models import ServiceCategory


class StaticSitemap(Sitemap):
    priority = 0.7
    changefreq = "weekly"

    def items(self):
        return ["cms:home", "products:catalog", "pricing:index", "blog:list", "portfolio:list", "crm:contact", "crm:quote", "crm:demo", "cms:faq", "cms:careers"]

    def location(self, item):
        return reverse(item)


class PageSitemap(Sitemap):
    changefreq = "monthly"
    priority = 0.6

    def items(self):
        return Page.objects.filter(status=PublishStatus.PUBLISHED, noindex=False).exclude(slug="accueil")

    def lastmod(self, obj):
        return obj.updated_at


class CategorySitemap(Sitemap):
    priority = 0.9

    def items(self):
        return ServiceCategory.objects.filter(is_published=True, noindex=False)

    def lastmod(self, obj):
        return obj.updated_at


class ProductSitemap(Sitemap):
    priority = 0.8

    def items(self):
        return Product.objects.published().filter(noindex=False)

    def lastmod(self, obj):
        return obj.updated_at


class ArticleSitemap(Sitemap):
    changefreq = "monthly"
    priority = 0.6

    def items(self):
        return BlogArticle.objects.published().filter(noindex=False)

    def lastmod(self, obj):
        return obj.updated_at


class CaseStudySitemap(Sitemap):
    priority = 0.5

    def items(self):
        return CaseStudy.objects.published().filter(noindex=False)

    def lastmod(self, obj):
        return obj.updated_at


class JobSitemap(Sitemap):
    priority = 0.4

    def items(self):
        return JobOpening.objects.open()


SITEMAPS = {
    "static": StaticSitemap,
    "pages": PageSitemap,
    "expertises": CategorySitemap,
    "solutions": ProductSitemap,
    "blog": ArticleSitemap,
    "realisations": CaseStudySitemap,
    "carrieres": JobSitemap,
}

"""Resolves the data each section kind needs, with one query per kind."""

from django.db.models import Prefetch

from .models import FAQ, PageSection, Partner, SectionItem, TeamMember


def load_sections(page):
    sections = list(
        page.sections.filter(is_active=True).prefetch_related(Prefetch("items", queryset=SectionItem.objects.all()))
    )
    kinds = {section.kind for section in sections}
    data = {}
    if kinds & {"domains", "marquee"}:
        from apps.services.models import ServiceCategory

        data["domains"] = list(ServiceCategory.objects.filter(is_published=True, show_on_home=True))
        data["marquee"] = data["domains"]
    if "solutions" in kinds:
        from apps.products.models import Product

        data["solutions"] = list(Product.objects.published().filter(is_featured=True).select_related("category").prefetch_related("features"))
    if "news" in kinds:
        from apps.blog.models import BlogArticle

        data["news"] = list(BlogArticle.objects.published().select_related("category", "author")[:6])
    if "team" in kinds:
        data["team"] = list(TeamMember.objects.filter(is_published=True))
    if "partners" in kinds:
        data["partners"] = list(Partner.objects.filter(is_published=True, agreement_confirmed=True))
    if "testimonials" in kinds:
        from apps.portfolio.models import Testimonial

        data["testimonials"] = list(Testimonial.objects.published())
    if "case_studies" in kinds:
        from apps.portfolio.models import CaseStudy

        data["case_studies"] = list(CaseStudy.objects.published())
    if "faq" in kinds:
        data["faq"] = list(FAQ.objects.filter(is_published=True, category__isnull=True))
    for section in sections:
        rows = data.get(section.kind)
        section.data = rows[: section.limit] if rows is not None and section.limit else rows
    return sections


__all__ = ["load_sections", "PageSection"]

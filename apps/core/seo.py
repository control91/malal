"""Helpers building page metadata (title, description, Open Graph, JSON-LD)."""

from dataclasses import dataclass, field
from typing import Any

from django.conf import settings


def absolute(url: str | None) -> str:
    if not url:
        return ""
    if url.startswith(("http://", "https://")):
        return url
    return f"{settings.SITE_URL}{url}"


@dataclass
class Seo:
    title: str = ""
    description: str = ""
    image: str = ""
    canonical: str = ""
    type: str = "website"
    noindex: bool = False
    json_ld: list[dict[str, Any]] = field(default_factory=list)
    published_time: str = ""


def build_seo(request, *, title="", description="", image=None, obj=None, type="website", noindex=False, json_ld=None) -> Seo:
    """Prefer the object's own SEO fields, then explicit values, then site defaults (in the template)."""
    if obj is not None:
        title = getattr(obj, "meta_title", "") or title
        description = getattr(obj, "meta_description", "") or description
        og = getattr(obj, "og_image", None)
        if og:
            image = og.url
        noindex = noindex or getattr(obj, "noindex", False)
    image_url = image.url if hasattr(image, "url") else image
    return Seo(
        title=title,
        description=(description or "")[:170],
        image=absolute(image_url),
        canonical=absolute(request.path),
        type=type,
        noindex=noindex,
        json_ld=json_ld or [],
    )


def organization_ld(site) -> dict[str, Any]:
    data: dict[str, Any] = {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": site.company_name,
        "url": settings.SITE_URL,
        "description": site.tagline,
        "address": {"@type": "PostalAddress", "addressLocality": site.city, "addressCountry": site.country},
    }
    if site.legal_name:
        data["legalName"] = site.legal_name
    if site.logo:
        data["logo"] = absolute(site.logo.url)
    if site.email_contact:
        data["email"] = site.email_contact
    if site.phone:
        data["telephone"] = site.phone
    same_as = [url for _label, url, _icon in site.social_links]
    if same_as:
        data["sameAs"] = same_as
    return data


def breadcrumb_ld(items: list[tuple[str, str]]) -> dict[str, Any]:
    return {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": i, "name": name, "item": absolute(url)}
            for i, (name, url) in enumerate(items, start=1)
        ],
    }

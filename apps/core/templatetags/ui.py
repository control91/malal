"""UI helpers available in every template (registered as a builtin)."""

import json
import re
from functools import lru_cache

from django import template
from django.conf import settings
from django.utils.html import escape
from django.utils.safestring import mark_safe

register = template.Library()

ICON_DIR = settings.BASE_DIR / "static" / "icons"
_SVG_OPEN = re.compile(r"<svg\b[^>]*>", re.S)


@lru_cache(maxsize=512)
def _icon_markup(name: str) -> str | None:
    if not re.fullmatch(r"[a-z0-9-]+", name or ""):
        return None
    path = ICON_DIR / f"{name}.svg"
    if not path.exists():
        return None
    svg = path.read_text()
    match = _SVG_OPEN.search(svg)
    inner = svg[match.end():].replace("</svg>", "").strip() if match else ""
    filled = 'fill="currentColor"' in (match.group(0) if match else "")
    return inner, filled  # type: ignore[return-value]


@register.simple_tag
def icon(name, css="size-5", stroke="1.75", label=""):
    """Inline a Lucide (or brand) SVG icon: {% icon "cloud" "size-6 text-primary" %}"""
    found = _icon_markup(str(name))
    if not found:
        found = _icon_markup("circle-dot")
        if not found:
            return ""
    inner, filled = found
    paint = 'fill="currentColor" stroke="none"' if filled else (
        f'fill="none" stroke="currentColor" stroke-width="{escape(stroke)}" stroke-linecap="round" stroke-linejoin="round"'
    )
    aria = f'role="img" aria-label="{escape(label)}"' if label else 'aria-hidden="true"'
    return mark_safe(
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{escape(css)}" {paint} {aria} focusable="false">{inner}</svg>'
    )


@register.filter
def jsonld(value):
    """Serialise a dict for a <script type="application/ld+json"> block safely."""
    data = json.dumps(value, ensure_ascii=False, default=str)
    return mark_safe(data.replace("<", "\\u003c").replace(">", "\\u003e").replace("&", "\\u0026"))


@register.simple_tag(takes_context=True)
def query_transform(context, **kwargs):
    """Rebuild the current query-string with some keys replaced (pagination + filters)."""
    query = context["request"].GET.copy()
    for key, value in kwargs.items():
        if value in (None, ""):
            query.pop(key, None)
        else:
            query[key] = value
    encoded = query.urlencode()
    return f"?{encoded}" if encoded else "?"


@register.simple_tag(takes_context=True)
def nav_active(context, url, exact=False):
    path = context["request"].path
    if not url or url.startswith(("http", "#", "mailto:")):
        return ""
    if exact or url == "/":
        return "is-active" if path == url else ""
    return "is-active" if path.startswith(url) else ""


@register.filter
def get_item(mapping, key):
    try:
        return mapping.get(key)
    except AttributeError:
        return None


@register.filter
def attr(obj, name):
    """Resolve a dotted attribute path; calls callables (used by the dashboard tables)."""
    value = obj
    for part in str(name).split("."):
        value = getattr(value, part, None)
        if callable(value):
            value = value()
        if value is None:
            return None
    return value


@register.filter
def money(value, currency=""):
    if value is None or value == "":
        return ""
    try:
        number = float(value)
    except (TypeError, ValueError):
        return value
    text = f"{number:,.0f}" if number == int(number) else f"{number:,.2f}"
    text = text.replace(",", " ")
    return f"{text} {currency}".strip()


@register.filter
def initials(value):
    parts = [p for p in str(value or "").replace("@", " ").split() if p]
    return "".join(p[0] for p in parts[:2]).upper() or "?"


@register.filter
def split_lines(value):
    return [line.strip() for line in str(value or "").splitlines() if line.strip()]


@register.filter
def split_csv(value):
    return [item.strip() for item in str(value or "").split(",") if item.strip()]


@register.filter
def theme_class(section):
    return {"dark": "dark-zone", "muted": "muted-zone"}.get(getattr(section, "theme", ""), "")


@register.filter
def emphasis(value):
    """Escape text and turn *words* into a gradient highlight (editor-friendly markup)."""
    text = escape(str(value or ""))
    return mark_safe(re.sub(r"\*(.+?)\*", r'<span class="text-gradient">\1</span>', text))


@register.filter
def plain(value):
    """Remove the *emphasis* markers (breadcrumbs, meta tags)."""
    return str(value or "").replace("*", "")

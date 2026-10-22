"""Server-side HTML sanitising for rich-text content (defence in depth)."""

import nh3

ALLOWED_TAGS = {
    "p", "br", "strong", "b", "em", "i", "u", "s", "del", "a", "ul", "ol", "li",
    "h2", "h3", "h4", "blockquote", "pre", "code", "hr", "div", "span", "figure", "figcaption", "img",
}
ALLOWED_ATTRIBUTES = {
    "a": {"href", "title", "target"},
    "img": {"src", "alt", "width", "height"},
    "*": set(),
}


def sanitize_html(value: str | None) -> str:
    if not value:
        return ""
    return nh3.clean(
        value,
        tags=ALLOWED_TAGS,
        attributes=ALLOWED_ATTRIBUTES,
        url_schemes={"http", "https", "mailto", "tel"},
        link_rel="noopener noreferrer",
        strip_comments=True,
    )

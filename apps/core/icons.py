"""
Curated Lucide icon set (https://lucide.dev, ISC licence).

Only these icons are vendored into ``static/icons`` by ``npm run vendor`` so
the repository stays light. The same list feeds every "icon" field of the CMS,
which lets non-technical editors pick an icon from a dropdown.
"""

ICONS: list[str] = [
    # Cloud & infrastructure
    "cloud", "cloud-cog", "cloud-upload", "server", "server-cog", "database", "database-backup",
    "hard-drive", "container", "boxes", "box", "network", "globe", "globe-2", "wifi", "router",
    "cpu", "memory-stick", "layers", "layers-3", "gauge", "activity", "radar", "satellite",
    # Software & SaaS
    "app-window", "layout-dashboard", "layout-grid", "panels-top-left", "code", "code-xml",
    "terminal", "git-branch", "workflow", "blocks", "puzzle", "plug", "plug-zap", "webhook",
    "smartphone", "monitor", "monitor-smartphone", "package", "rocket", "settings", "sliders-horizontal",
    # AI
    "brain", "brain-circuit", "bot", "sparkles", "wand-sparkles", "message-square", "messages-square",
    "scan-text", "file-search", "file-text", "chart-line", "chart-bar", "chart-pie", "trending-up", "zap",
    # Business
    "briefcase", "building", "building-2", "landmark", "handshake", "users", "user", "user-check",
    "user-plus", "contact", "target", "flag", "map", "map-pin", "compass", "lightbulb", "graduation-cap",
    "heart-handshake", "award", "badge-check", "star", "calendar", "calendar-check", "clock",
    "wallet", "receipt", "shopping-cart", "store", "truck", "stethoscope", "school", "wheat", "factory",
    # Trust & security
    "shield", "shield-check", "lock", "key-round", "fingerprint", "eye", "eye-off", "scale",
    "life-buoy", "headset", "circle-help", "info",
    # UI
    "arrow-right", "arrow-left", "arrow-up-right", "arrow-up", "arrow-down", "chevron-down", "chevron-right",
    "chevron-left", "chevron-up", "chevrons-up-down", "check", "check-check", "circle-check", "circle-x", "x",
    "plus", "minus", "menu", "search", "filter", "list", "list-filter", "pencil", "trash-2", "copy",
    "external-link", "link", "download", "upload", "image", "images", "file", "folder", "paperclip",
    "mail", "mail-open", "send", "phone", "message-circle", "bell", "bell-ring", "inbox", "archive",
    "log-in", "log-out", "house", "newspaper", "book-open", "notebook-pen", "tag", "tags", "hash",
    "history", "refresh-cw", "loader-circle", "triangle-alert", "octagon-alert", "circle-alert",
    "ellipsis", "ellipsis-vertical", "grip-vertical", "move", "eye", "share-2", "quote", "sun", "moon",
    "languages", "type", "palette", "navigation", "panel-left", "square-pen", "kanban", "columns-3",
    "list-checks", "clipboard-list", "user-cog", "users-round", "circle-dot", "circle", "dot",
]
# de-duplicate while keeping order
ICONS = list(dict.fromkeys(ICONS))
# Simple brand glyphs drawn in-house (Lucide no longer ships brand icons).
BRAND_ICONS = ["brand-linkedin", "brand-facebook", "brand-instagram", "brand-youtube", "brand-github", "brand-x", "brand-whatsapp"]
ICON_CHOICES = [("", "—")] + [(name, name) for name in ICONS]

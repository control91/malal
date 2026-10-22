"""Upload validation: extension, size and real content (not just the name)."""

from pathlib import Path

from django.conf import settings
from django.core.exceptions import ValidationError
from django.utils.translation import gettext_lazy as _
from PIL import Image, UnidentifiedImageError

IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".gif"}
SVG_EXTENSION = ".svg"
DOCUMENT_EXTENSIONS = {".pdf", ".docx", ".xlsx", ".pptx", ".txt", ".csv", ".zip"}


def _check_size(file, max_mb: int) -> None:
    if file.size and file.size > max_mb * 1024 * 1024:
        raise ValidationError(_("Le fichier dépasse la taille maximale autorisée (%(mb)s Mo)."), params={"mb": max_mb})


def validate_image_file(file) -> None:
    ext = Path(file.name).suffix.lower()
    if ext not in IMAGE_EXTENSIONS:
        raise ValidationError(_("Format d'image non autorisé. Formats acceptés : JPG, PNG, WebP, GIF."))
    _check_size(file, settings.MAX_IMAGE_UPLOAD_MB)
    try:
        pos = file.tell() if hasattr(file, "tell") else 0
        with Image.open(file) as img:
            img.verify()
        file.seek(pos)
    except (UnidentifiedImageError, OSError, SyntaxError) as exc:
        raise ValidationError(_("Le fichier n'est pas une image valide.")) from exc


def validate_logo_file(file) -> None:
    """Logos/favicons may additionally be SVG; SVG content is screened for scripts."""
    ext = Path(file.name).suffix.lower()
    if ext != SVG_EXTENSION:
        validate_image_file(file)
        return
    _check_size(file, 1)
    content = file.read().decode("utf-8", errors="ignore").lower()
    file.seek(0)
    forbidden = ("<script", "javascript:", "onload=", "onerror=", "<foreignobject", "xlink:href=\"http")
    if "<svg" not in content or any(token in content for token in forbidden):
        raise ValidationError(_("Ce fichier SVG contient des éléments non autorisés."))


def validate_document_file(file) -> None:
    ext = Path(file.name).suffix.lower()
    if ext not in DOCUMENT_EXTENSIONS | IMAGE_EXTENSIONS:
        raise ValidationError(_("Type de fichier non autorisé."))
    if ext in IMAGE_EXTENSIONS:
        validate_image_file(file)
        return
    _check_size(file, settings.MAX_FILE_UPLOAD_MB)
    head = file.read(8)
    file.seek(0)
    if ext == ".pdf" and not head.startswith(b"%PDF"):
        raise ValidationError(_("Le fichier PDF est invalide."))
    if ext in {".docx", ".xlsx", ".pptx", ".zip"} and not head.startswith(b"PK"):
        raise ValidationError(_("Le document est invalide ou corrompu."))


def validate_hex_color(value: str) -> None:
    import re

    if value and not re.fullmatch(r"#[0-9a-fA-F]{6}", value):
        raise ValidationError(_("Couleur invalide : utilisez le format #RRGGBB."))

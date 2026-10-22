"""Custom model fields shared across apps."""

import io
import logging
from pathlib import Path

from django.core.files.base import ContentFile
from django.db import models
from PIL import Image, ImageOps

from .sanitize import sanitize_html
from .validators import validate_image_file

logger = logging.getLogger(__name__)

MAX_DIMENSION = 2000


def optimize_image(field_file) -> None:
    """Downscale, strip metadata and re-encode a freshly uploaded image as WebP."""
    name = Path(field_file.name)
    if name.suffix.lower() == ".gif":  # keep animations untouched
        return
    try:
        field_file.seek(0)
        with Image.open(field_file) as img:
            img = ImageOps.exif_transpose(img)
            if img.mode not in ("RGB", "RGBA"):
                img = img.convert("RGBA" if "A" in img.getbands() else "RGB")
            img.thumbnail((MAX_DIMENSION, MAX_DIMENSION), Image.Resampling.LANCZOS)
            buffer = io.BytesIO()
            img.save(buffer, format="WEBP", quality=82, method=5)
    except Exception:  # pragma: no cover - never block an upload on optimisation
        logger.warning("Image optimisation failed for %s", field_file.name, exc_info=True)
        return
    field_file.save(f"{name.stem}.webp", ContentFile(buffer.getvalue()), save=False)


class OptimizedImageField(models.ImageField):
    """ImageField that validates content and stores an optimised WebP copy."""

    def __init__(self, *args, **kwargs):
        kwargs.setdefault("validators", [validate_image_file])
        super().__init__(*args, **kwargs)

    def pre_save(self, model_instance, add):
        file = getattr(model_instance, self.attname)
        if file and not file._committed:
            optimize_image(file)
        return super().pre_save(model_instance, add)


class RichTextField(models.TextField):
    """TextField whose HTML is sanitised every time it is saved."""

    def pre_save(self, model_instance, add):
        value = sanitize_html(getattr(model_instance, self.attname))
        setattr(model_instance, self.attname, value)
        return value

    def formfield(self, **kwargs):
        from .widgets import RichTextWidget

        kwargs.setdefault("widget", RichTextWidget)
        return super().formfield(**kwargs)

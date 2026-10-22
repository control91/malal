import io

import pytest
from django.core.exceptions import ValidationError
from django.core.files.uploadedfile import SimpleUploadedFile
from PIL import Image

from apps.cms.models import TeamMember
from apps.core.validators import validate_document_file, validate_image_file, validate_logo_file

pytestmark = pytest.mark.django_db


def png_bytes(size=(3000, 1500)):
    buffer = io.BytesIO()
    Image.new("RGB", size, "blue").save(buffer, format="PNG")
    return buffer.getvalue()


def test_fake_image_rejected():
    with pytest.raises(ValidationError):
        validate_image_file(SimpleUploadedFile("evil.png", b"<?php echo 1; ?>"))


def test_wrong_extension_rejected():
    with pytest.raises(ValidationError):
        validate_image_file(SimpleUploadedFile("x.exe", png_bytes((10, 10))))


def test_svg_with_script_rejected():
    with pytest.raises(ValidationError):
        validate_logo_file(SimpleUploadedFile("logo.svg", b'<svg onload="alert(1)"><script>1</script></svg>'))


def test_fake_pdf_rejected():
    with pytest.raises(ValidationError):
        validate_document_file(SimpleUploadedFile("doc.pdf", b"not a pdf"))


def test_images_are_resized_and_converted_to_webp(settings, tmp_path):
    settings.MEDIA_ROOT = tmp_path
    member = TeamMember.objects.create(name="A", role="B", photo=SimpleUploadedFile("photo.png", png_bytes()))
    assert member.photo.name.endswith(".webp")
    with Image.open(member.photo.path) as img:
        assert max(img.size) <= 2000

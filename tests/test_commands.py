import pytest
from django.core.management import CommandError, call_command

from apps.accounts.models import User
from apps.cms.models import Page, PageSection
from apps.services.models import Service

pytestmark = pytest.mark.django_db


def test_seed_site_is_idempotent_and_preserves_edits():
    call_command("seed_site", verbosity=0)
    hero = PageSection.objects.get(page__slug="accueil", kind="hero")
    hero.title = "Edité"
    hero.save()
    count = Service.objects.count()
    call_command("seed_site", verbosity=0)
    assert Service.objects.count() == count
    assert PageSection.objects.get(pk=hero.pk).title == "Edité"
    assert Page.objects.filter(slug="mentions-legales").exists()


def test_create_admin_from_env(monkeypatch):
    monkeypatch.setenv("DJANGO_ADMIN_PASSWORD", "Very-str0ng-admin-pass")
    call_command("create_admin", "--email", "Root@Test.local", "--noinput")
    user = User.objects.get(email="root@test.local")
    assert user.is_superuser and user.role == "superadmin" and user.check_password("Very-str0ng-admin-pass")
    with pytest.raises(CommandError):
        call_command("create_admin", "--email", "root@test.local", "--noinput")


def test_create_admin_rejects_weak_password(monkeypatch):
    monkeypatch.setenv("DJANGO_ADMIN_PASSWORD", "123")
    with pytest.raises(CommandError):
        call_command("create_admin", "--email", "weak@test.local", "--noinput")


def test_seed_demo_refuses_production(settings):
    settings.DEBUG = False
    with pytest.raises(CommandError):
        call_command("seed_demo", verbosity=0)

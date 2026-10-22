import pytest
from django.core.cache import cache
from django.core.management import call_command

from apps.accounts.models import User
from apps.accounts.roles import Role


@pytest.fixture(autouse=True)
def _clear_cache():
    cache.clear()
    yield
    cache.clear()


@pytest.fixture
def site_content(db):
    call_command("seed_site", verbosity=0)


@pytest.fixture
def make_user(db):
    def factory(role=Role.VIEWER, email=None, **extra):
        email = email or f"{role}@test.local"
        return User.objects.create_user(email=email, password="Str0ng-pass-for-tests!", role=role, is_staff=True, **extra)

    return factory


@pytest.fixture
def staff_client(client, make_user):
    def login(role):
        user = make_user(role)
        client.force_login(user)
        return client, user

    return login

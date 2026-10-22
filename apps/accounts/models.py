from django.contrib.auth.models import AbstractUser, BaseUserManager
from django.db import models
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField

from .roles import Role, capabilities_for


class UserManager(BaseUserManager):
    use_in_migrations = True

    def _create_user(self, email, password, **extra):
        if not email:
            raise ValueError("Email is required")
        email = self.normalize_email(email).lower()
        user = self.model(email=email, **extra)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_user(self, email, password=None, **extra):
        extra.setdefault("is_staff", False)
        extra.setdefault("is_superuser", False)
        return self._create_user(email, password, **extra)

    def create_superuser(self, email, password=None, **extra):
        extra.update(is_staff=True, is_superuser=True, role=Role.SUPERADMIN)
        return self._create_user(email, password, **extra)


class User(AbstractUser):
    """
    Central identity for the back-office today and, later, for the client portal
    (see docs/ARCHITECTURE.md — the same table can back an OIDC provider).
    """

    username = None
    email = models.EmailField(_("adresse email"), unique=True)
    role = models.CharField(_("rôle"), max_length=20, choices=Role.choices, default=Role.VIEWER)
    job_title = models.CharField(_("fonction"), max_length=120, blank=True)
    phone = models.CharField(_("téléphone"), max_length=40, blank=True)
    avatar = OptimizedImageField(_("photo"), upload_to="avatars/", blank=True)
    bio = models.TextField(_("biographie (auteur)"), blank=True)

    USERNAME_FIELD = "email"
    REQUIRED_FIELDS: list[str] = []

    objects = UserManager()

    class Meta:
        verbose_name = _("utilisateur")
        verbose_name_plural = _("utilisateurs")
        ordering = ["first_name", "last_name", "email"]

    def __str__(self) -> str:
        return self.get_full_name() or self.email

    def save(self, *args, **kwargs):
        self.email = (self.email or "").lower()
        if self.role == Role.SUPERADMIN:
            self.is_superuser = True
            self.is_staff = True
        super().save(*args, **kwargs)

    @property
    def display_name(self) -> str:
        return self.get_full_name() or self.email.split("@")[0]

    @property
    def capabilities(self) -> frozenset[str]:
        return capabilities_for(self)

    def can(self, capability: str) -> bool:
        return capability in self.capabilities

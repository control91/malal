"""Shared abstract models and the singleton site configuration."""

from django.core.cache import cache
from django.db import models
from django.utils import timezone
from django.utils.translation import gettext_lazy as _

from .fields import OptimizedImageField
from .validators import validate_hex_color, validate_logo_file


class TimeStampedModel(models.Model):
    created_at = models.DateTimeField(_("créé le"), auto_now_add=True)
    updated_at = models.DateTimeField(_("modifié le"), auto_now=True)

    class Meta:
        abstract = True


class OrderedModel(models.Model):
    order = models.PositiveIntegerField(_("ordre d'affichage"), default=0, db_index=True)

    class Meta:
        abstract = True
        ordering = ["order", "pk"]


class SeoModel(models.Model):
    meta_title = models.CharField(_("meta title"), max_length=70, blank=True, help_text=_("≤ 60 caractères conseillés."))
    meta_description = models.CharField(
        _("meta description"), max_length=170, blank=True, help_text=_("≤ 155 caractères conseillés.")
    )
    og_image = OptimizedImageField(_("image de partage (Open Graph)"), upload_to="seo/", blank=True)
    noindex = models.BooleanField(_("masquer aux moteurs de recherche"), default=False)

    class Meta:
        abstract = True


class Availability(models.TextChoices):
    """Honest lifecycle status for every service and product."""

    AVAILABLE = "available", _("Disponible")
    BETA = "beta", _("Bêta")
    COMING_SOON = "coming_soon", _("À venir")


class PublishStatus(models.TextChoices):
    DRAFT = "draft", _("Brouillon")
    PUBLISHED = "published", _("Publié")
    ARCHIVED = "archived", _("Archivé")


class PublishedQuerySet(models.QuerySet):
    def published(self):
        return self.filter(status=PublishStatus.PUBLISHED)


class SiteSettings(TimeStampedModel):
    """Single row holding the whole company identity, editable from the dashboard."""

    CACHE_KEY = "core:site-settings"

    # Identity
    company_name = models.CharField(_("nom commercial"), max_length=120, default="MALAL")
    legal_name = models.CharField(_("raison sociale"), max_length=200, blank=True)
    tagline = models.CharField(_("signature"), max_length=200, default="Cloud, SaaS & Intelligence Artificielle pour l'Afrique")
    logo = models.FileField(_("logo (fond clair)"), upload_to="brand/", blank=True, validators=[validate_logo_file])
    logo_dark = models.FileField(_("logo (fond sombre)"), upload_to="brand/", blank=True, validators=[validate_logo_file])
    favicon = models.FileField(_("favicon"), upload_to="brand/", blank=True, validators=[validate_logo_file])
    registration_number = models.CharField(_("RCCM"), max_length=80, blank=True)
    tax_number = models.CharField(_("NIF"), max_length=80, blank=True)

    # Brand colours (injected as CSS custom properties)
    color_primary = models.CharField(_("couleur principale"), max_length=7, default="#2563EB", validators=[validate_hex_color])
    color_accent = models.CharField(_("couleur d'accent"), max_length=7, default="#22D3EE", validators=[validate_hex_color])
    color_ink = models.CharField(_("bleu nuit"), max_length=7, default="#0A1330", validators=[validate_hex_color])

    # Contact
    email_contact = models.EmailField(_("email de contact"), default="contact@example.com")
    email_sales = models.EmailField(_("email commercial"), blank=True)
    email_support = models.EmailField(_("email support"), blank=True)
    phone = models.CharField(_("téléphone"), max_length=40, blank=True)
    whatsapp = models.CharField(_("WhatsApp"), max_length=40, blank=True, help_text=_("Format international, ex. +224…"))
    address = models.CharField(_("adresse"), max_length=255, blank=True)
    city = models.CharField(_("ville"), max_length=80, default="Conakry")
    country = models.CharField(_("pays"), max_length=80, default="Guinée")
    opening_hours = models.CharField(_("horaires"), max_length=120, blank=True)
    map_embed_url = models.URLField(_("URL de carte intégrée (OpenStreetMap)"), blank=True)

    # Social
    linkedin_url = models.URLField("LinkedIn", blank=True)
    x_url = models.URLField("X (Twitter)", blank=True)
    facebook_url = models.URLField("Facebook", blank=True)
    instagram_url = models.URLField("Instagram", blank=True)
    youtube_url = models.URLField("YouTube", blank=True)
    github_url = models.URLField("GitHub", blank=True)

    # SEO defaults
    meta_title = models.CharField(_("meta title par défaut"), max_length=70, blank=True)
    meta_description = models.CharField(_("meta description par défaut"), max_length=170, blank=True)
    og_image = OptimizedImageField(_("image de partage par défaut"), upload_to="seo/", blank=True)
    twitter_handle = models.CharField(_("compte X (@...)"), max_length=40, blank=True)

    # Transparency
    hosting_disclosure = models.TextField(
        _("transparence sur l'hébergement"),
        blank=True,
        default=(
            "Nos services d'hébergement s'appuient aujourd'hui sur des infrastructures louées auprès de "
            "fournisseurs cloud établis, situées hors de Guinée. Le déploiement d'une infrastructure locale "
            "fait partie de notre feuille de route."
        ),
        help_text=_("Affiché sur les pages Cloud et Tarifs. Restez factuel sur la localisation des données."),
    )
    footer_text = models.TextField(_("texte du footer"), blank=True)
    announcement = models.CharField(_("bandeau d'annonce"), max_length=200, blank=True)
    announcement_url = models.CharField(_("lien du bandeau"), max_length=255, blank=True)

    # Languages & notifications
    enabled_languages = models.CharField(
        _("langues actives"), max_length=60, default="fr", help_text=_("Codes séparés par des virgules (fr,en).")
    )
    notification_emails = models.TextField(
        _("emails de notification"), blank=True, help_text=_("Une adresse par ligne : reçoivent les nouvelles demandes.")
    )
    notify_on_inquiry = models.BooleanField(_("notifier les nouvelles demandes par email"), default=True)
    send_acknowledgement = models.BooleanField(_("envoyer un accusé de réception aux demandeurs"), default=True)

    class Meta:
        verbose_name = _("paramètres du site")
        verbose_name_plural = _("paramètres du site")

    def __str__(self) -> str:
        return self.company_name

    def save(self, *args, **kwargs):
        self.pk = 1
        super().save(*args, **kwargs)
        cache.delete(self.CACHE_KEY)

    def delete(self, *args, **kwargs):  # the singleton is never deleted
        return (0, {})

    @classmethod
    def load(cls) -> "SiteSettings":
        obj = cache.get(cls.CACHE_KEY)
        if obj is None:
            obj, _created = cls.objects.get_or_create(pk=1)
            cache.set(cls.CACHE_KEY, obj, 300)
        return obj

    @property
    def notification_recipients(self) -> list[str]:
        return [line.strip() for line in self.notification_emails.splitlines() if line.strip()]

    @property
    def languages(self) -> list[str]:
        return [code.strip() for code in self.enabled_languages.split(",") if code.strip()]

    @property
    def social_links(self) -> list[tuple[str, str, str]]:
        links = [
            ("LinkedIn", self.linkedin_url, "brand-linkedin"),
            ("X", self.x_url, "brand-x"),
            ("Facebook", self.facebook_url, "brand-facebook"),
            ("Instagram", self.instagram_url, "brand-instagram"),
            ("YouTube", self.youtube_url, "brand-youtube"),
            ("GitHub", self.github_url, "brand-github"),
        ]
        return [link for link in links if link[1]]

    @property
    def year(self) -> int:
        return timezone.now().year

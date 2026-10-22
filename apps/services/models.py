"""Business domains (Cloud, SaaS, IA, sur-mesure…) and the services they contain."""

from django.db import models
from django.urls import reverse
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField, RichTextField
from apps.core.icons import ICON_CHOICES
from apps.core.models import Availability, OrderedModel, SeoModel, TimeStampedModel


class ServiceCategory(OrderedModel, TimeStampedModel, SeoModel):
    """A domain of expertise; each one gets its own public page."""

    name = models.CharField(_("nom"), max_length=120)
    slug = models.SlugField(_("slug"), max_length=120, unique=True)
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    tagline = models.CharField(_("accroche"), max_length=200, blank=True)
    summary = models.TextField(_("résumé (cartes)"), blank=True)
    hero_title = models.CharField(_("titre de la page"), max_length=200, blank=True)
    hero_subtitle = models.TextField(_("sous-titre de la page"), blank=True)
    description = RichTextField(_("présentation détaillée"), blank=True)
    image = OptimizedImageField(_("illustration"), upload_to="categories/", blank=True)
    show_hosting_disclosure = models.BooleanField(
        _("afficher la transparence hébergement"), default=False, help_text=_("Recommandé pour les pages Cloud et hébergement.")
    )
    demo_space_enabled = models.BooleanField(
        _("afficher l'espace démonstrations"), default=False, help_text=_("Espace réservé aux futures démos interactives (IA).")
    )
    is_published = models.BooleanField(_("publié"), default=True)
    show_on_home = models.BooleanField(_("afficher sur l'accueil"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("domaine d'expertise")
        verbose_name_plural = _("domaines d'expertise")

    def __str__(self) -> str:
        return self.name

    def get_absolute_url(self) -> str:
        return reverse("services:category", kwargs={"slug": self.slug})


class Service(OrderedModel, TimeStampedModel):
    category = models.ForeignKey(ServiceCategory, verbose_name=_("domaine"), on_delete=models.CASCADE, related_name="services")
    name = models.CharField(_("nom"), max_length=160)
    slug = models.SlugField(_("slug"), max_length=160)
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    summary = models.TextField(_("description courte"))
    details = models.TextField(_("points clés"), blank=True, help_text=_("Un point par ligne."))
    status = models.CharField(_("disponibilité"), max_length=20, choices=Availability.choices, default=Availability.AVAILABLE)
    is_featured = models.BooleanField(_("mis en avant"), default=False)
    is_published = models.BooleanField(_("publié"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("service")
        verbose_name_plural = _("services")
        constraints = [models.UniqueConstraint(fields=["category", "slug"], name="unique_service_slug_per_category")]

    def __str__(self) -> str:
        return self.name


class StatusComponent(OrderedModel, TimeStampedModel):
    """Manually maintained service-status entry (public /statut/ page)."""

    class State(models.TextChoices):
        OPERATIONAL = "operational", _("Opérationnel")
        DEGRADED = "degraded", _("Performances dégradées")
        OUTAGE = "outage", _("Incident en cours")
        MAINTENANCE = "maintenance", _("Maintenance")

    name = models.CharField(_("composant"), max_length=120)
    description = models.CharField(_("description"), max_length=255, blank=True)
    state = models.CharField(_("état"), max_length=20, choices=State.choices, default=State.OPERATIONAL)
    message = models.TextField(_("message public"), blank=True)
    is_published = models.BooleanField(_("publié"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("composant de statut")
        verbose_name_plural = _("statut des services")

    def __str__(self) -> str:
        return self.name

"""Catalogue of the company's own solutions (SaaS, AI products, cloud offers)."""

from django.db import models
from django.urls import reverse
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField, RichTextField
from apps.core.icons import ICON_CHOICES
from apps.core.models import Availability, OrderedModel, SeoModel, TimeStampedModel


class ProductQuerySet(models.QuerySet):
    def published(self):
        return self.filter(is_published=True, category__is_published=True)


class Product(OrderedModel, TimeStampedModel, SeoModel):
    category = models.ForeignKey(
        "services.ServiceCategory", verbose_name=_("domaine"), on_delete=models.PROTECT, related_name="products"
    )
    name = models.CharField(_("nom"), max_length=120)
    slug = models.SlugField(_("slug"), max_length=120, unique=True)
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    tagline = models.CharField(_("accroche"), max_length=200)
    summary = models.TextField(_("résumé"))
    description = RichTextField(_("description détaillée"), blank=True)
    benefits = models.TextField(_("avantages"), blank=True, help_text=_("Un avantage par ligne."))
    audience = models.CharField(_("pour qui ?"), max_length=255, blank=True)
    logo = OptimizedImageField(_("logo / visuel"), upload_to="products/", blank=True)
    status = models.CharField(_("disponibilité"), max_length=20, choices=Availability.choices, default=Availability.AVAILABLE)
    is_example = models.BooleanField(
        _("contenu d'exemple"), default=False, help_text=_("Affiche un badge « Exemple » : à décocher une fois le contenu réel saisi.")
    )
    demo_enabled = models.BooleanField(_("demande de démo possible"), default=True)
    show_pricing = models.BooleanField(_("afficher les tarifs publics"), default=True)
    external_url = models.URLField(_("lien vers l'application"), blank=True)
    is_featured = models.BooleanField(_("mis en avant sur l'accueil"), default=False)
    is_published = models.BooleanField(_("publié"), default=False)

    objects = ProductQuerySet.as_manager()

    class Meta(OrderedModel.Meta):
        verbose_name = _("solution")
        verbose_name_plural = _("solutions")

    def __str__(self) -> str:
        return self.name

    def get_absolute_url(self) -> str:
        return reverse("products:detail", kwargs={"slug": self.slug})


class ProductFeature(OrderedModel):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name="features")
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    title = models.CharField(_("fonctionnalité"), max_length=120)
    description = models.TextField(_("description"), blank=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("fonctionnalité")
        verbose_name_plural = _("fonctionnalités")

    def __str__(self) -> str:
        return self.title


class ProductScreenshot(OrderedModel):
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name="screenshots")
    image = OptimizedImageField(_("capture d'écran"), upload_to="products/screens/")
    caption = models.CharField(_("légende"), max_length=200, blank=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("capture d'écran")
        verbose_name_plural = _("captures d'écran")

    def __str__(self) -> str:
        return self.caption or f"Capture {self.pk}"

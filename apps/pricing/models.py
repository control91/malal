"""Fully administrable commercial offers."""

from decimal import Decimal

from django.db import models
from django.utils.translation import gettext_lazy as _

from apps.core.models import Availability, OrderedModel, TimeStampedModel


class PricingGroup(OrderedModel):
    """A family of plans compared side by side (e.g. « Hébergement web »)."""

    name = models.CharField(_("nom"), max_length=120)
    slug = models.SlugField(_("slug"), max_length=120, unique=True)
    description = models.TextField(_("description"), blank=True)
    category = models.ForeignKey("services.ServiceCategory", verbose_name=_("domaine"), null=True, blank=True, on_delete=models.SET_NULL, related_name="pricing_groups")
    product = models.ForeignKey("products.Product", verbose_name=_("solution"), null=True, blank=True, on_delete=models.SET_NULL, related_name="pricing_groups")
    is_published = models.BooleanField(_("publié"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("groupe d'offres")
        verbose_name_plural = _("groupes d'offres")

    def __str__(self) -> str:
        return self.name

    def comparison(self):
        """Rows ``(label, [cell per plan])`` for the feature comparison table."""
        plans = [p for p in self.plans.all() if p.is_published]
        labels: list[str] = []
        cells: dict[tuple[int, str], PricingFeature] = {}
        for plan in plans:
            for feature in plan.features.all():
                if feature.label not in labels:
                    labels.append(feature.label)
                cells[(plan.pk, feature.label)] = feature
        return plans, [(label, [cells.get((plan.pk, label)) for plan in plans]) for label in labels]


class PricingPlan(OrderedModel, TimeStampedModel):
    class Currency(models.TextChoices):
        GNF = "GNF", "GNF"
        XOF = "XOF", "FCFA (XOF)"
        EUR = "EUR", "EUR"
        USD = "USD", "USD"

    group = models.ForeignKey(PricingGroup, verbose_name=_("groupe"), on_delete=models.CASCADE, related_name="plans")
    name = models.CharField(_("nom de l'offre"), max_length=120)
    description = models.CharField(_("description"), max_length=255, blank=True)
    currency = models.CharField(_("devise"), max_length=3, choices=Currency.choices, default=Currency.GNF)
    price_monthly = models.DecimalField(_("prix mensuel"), max_digits=14, decimal_places=2, null=True, blank=True)
    price_yearly = models.DecimalField(
        _("prix annuel"), max_digits=14, decimal_places=2, null=True, blank=True, help_text=_("Montant total facturé par an.")
    )
    price_on_request = models.BooleanField(_("prix sur devis"), default=False, help_text=_("Masque les prix et affiche « Sur devis »."))
    price_note = models.CharField(_("mention sous le prix"), max_length=120, blank=True, help_text=_("Ex. HT, par utilisateur…"))
    status = models.CharField(_("disponibilité"), max_length=20, choices=Availability.choices, default=Availability.AVAILABLE)
    is_highlighted = models.BooleanField(_("offre recommandée"), default=False)
    cta_label = models.CharField(_("libellé du bouton"), max_length=60, default="Demander un devis")
    cta_url = models.CharField(_("lien du bouton"), max_length=255, blank=True, help_text=_("Vide = formulaire de devis pré-rempli."))
    is_published = models.BooleanField(_("publié"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("offre tarifaire")
        verbose_name_plural = _("offres tarifaires")

    def __str__(self) -> str:
        return f"{self.group} · {self.name}"

    @property
    def yearly_monthly_equivalent(self) -> Decimal | None:
        if self.price_yearly is None:
            return None
        return (self.price_yearly / 12).quantize(Decimal("1"))

    @property
    def yearly_saving_percent(self) -> int | None:
        if not self.price_monthly or not self.price_yearly:
            return None
        full = self.price_monthly * 12
        if full <= self.price_yearly:
            return None
        return int(round((1 - self.price_yearly / full) * 100))


class PricingFeature(OrderedModel):
    plan = models.ForeignKey(PricingPlan, on_delete=models.CASCADE, related_name="features")
    label = models.CharField(_("caractéristique"), max_length=160, help_text=_("Même libellé entre offres pour les comparer."))
    value = models.CharField(_("valeur"), max_length=80, blank=True, help_text=_("Ex. 10 Go, Illimité. Vide = coche."))
    included = models.BooleanField(_("inclus"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("caractéristique")
        verbose_name_plural = _("caractéristiques")

    def __str__(self) -> str:
        return self.label


class PricingOption(OrderedModel):
    """Paid add-on displayed under a group (backups, extra storage…)."""

    group = models.ForeignKey(PricingGroup, on_delete=models.CASCADE, related_name="options")
    name = models.CharField(_("option"), max_length=120)
    description = models.CharField(_("description"), max_length=255, blank=True)
    price_label = models.CharField(_("prix (texte libre)"), max_length=80, blank=True, help_text=_("Ex. 50 000 GNF / mois. Vide = sur devis."))

    class Meta(OrderedModel.Meta):
        verbose_name = _("option")
        verbose_name_plural = _("options")

    def __str__(self) -> str:
        return self.name

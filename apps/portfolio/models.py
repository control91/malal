"""Case studies and testimonials — published only with documented consent."""

from django.core.exceptions import ValidationError
from django.db import models
from django.urls import reverse
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField, RichTextField
from apps.core.models import OrderedModel, PublishStatus, SeoModel, TimeStampedModel


class CaseStudyQuerySet(models.QuerySet):
    def published(self):
        return self.filter(status=PublishStatus.PUBLISHED).filter(models.Q(publication_consent=True) | models.Q(is_example=True))


class CaseStudy(OrderedModel, TimeStampedModel, SeoModel):
    title = models.CharField(_("nom du projet"), max_length=200)
    slug = models.SlugField(_("slug"), max_length=200, unique=True)
    client_name = models.CharField(_("client"), max_length=160, blank=True)
    show_client_name = models.BooleanField(_("nommer le client publiquement"), default=False)
    sector = models.CharField(_("secteur d'activité"), max_length=120)
    summary = models.CharField(_("résumé"), max_length=255)
    challenge = RichTextField(_("problématique"))
    solution = RichTextField(_("solution apportée"))
    results = RichTextField(_("résultats"), blank=True, help_text=_("Uniquement des résultats vérifiables."))
    technologies = models.CharField(_("technologies"), max_length=255, blank=True, help_text=_("Séparées par des virgules."))
    cover = OptimizedImageField(_("image principale"), upload_to="portfolio/", blank=True)
    services = models.ManyToManyField("services.ServiceCategory", verbose_name=_("domaines"), blank=True, related_name="case_studies")
    year = models.PositiveSmallIntegerField(_("année"), null=True, blank=True)
    publication_consent = models.BooleanField(_("consentement de publication obtenu"), default=False)
    consent_reference = models.CharField(_("référence du consentement"), max_length=200, blank=True, help_text=_("Ex. email du 12/03/2026, contrat §7…"))
    is_example = models.BooleanField(_("exemple illustratif"), default=False, help_text=_("Affiche un badge « Exemple illustratif » sur le site."))
    status = models.CharField(_("statut"), max_length=20, choices=PublishStatus.choices, default=PublishStatus.DRAFT)

    objects = CaseStudyQuerySet.as_manager()

    class Meta(OrderedModel.Meta):
        verbose_name = _("réalisation")
        verbose_name_plural = _("réalisations")

    def __str__(self) -> str:
        return self.title

    def clean(self):
        if self.status == PublishStatus.PUBLISHED and not (self.publication_consent or self.is_example):
            raise ValidationError({"publication_consent": _("Le consentement du client est requis pour publier.")})

    def get_absolute_url(self) -> str:
        return reverse("portfolio:detail", kwargs={"slug": self.slug})

    @property
    def public_client(self) -> str:
        return self.client_name if self.show_client_name and self.publication_consent else ""


class CaseStudyImage(OrderedModel):
    case_study = models.ForeignKey(CaseStudy, on_delete=models.CASCADE, related_name="images")
    image = OptimizedImageField(_("image"), upload_to="portfolio/gallery/")
    caption = models.CharField(_("légende"), max_length=200, blank=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("image")
        verbose_name_plural = _("galerie")


class TestimonialQuerySet(models.QuerySet):
    def published(self):
        return self.filter(is_published=True, consent_obtained=True)


class Testimonial(OrderedModel, TimeStampedModel):
    author_name = models.CharField(_("nom"), max_length=120)
    author_role = models.CharField(_("fonction"), max_length=120, blank=True)
    organization = models.CharField(_("organisation"), max_length=160, blank=True)
    quote = models.TextField(_("témoignage"))
    photo = OptimizedImageField(_("photo"), upload_to="testimonials/", blank=True)
    case_study = models.ForeignKey(CaseStudy, null=True, blank=True, on_delete=models.SET_NULL, related_name="testimonials", verbose_name=_("réalisation"))
    consent_obtained = models.BooleanField(_("consentement écrit obtenu"), default=False)
    consent_reference = models.CharField(_("référence du consentement"), max_length=200, blank=True)
    is_published = models.BooleanField(_("publié"), default=False)

    objects = TestimonialQuerySet.as_manager()

    class Meta(OrderedModel.Meta):
        verbose_name = _("témoignage")
        verbose_name_plural = _("témoignages")

    def __str__(self) -> str:
        return f"{self.author_name} — {self.organization}"

    def clean(self):
        if self.is_published and not self.consent_obtained:
            raise ValidationError({"consent_obtained": _("Un témoignage ne peut être publié sans consentement écrit.")})

"""
Commercial pipeline.

* ``Inquiry``  — any request submitted on the website (contact, quote, demo…).
* ``Lead``     — a person/organisation followed by the sales team (mini CRM).
* ``Activity`` — timeline shared by leads and inquiries (notes, calls, emails…).
"""

import secrets

from django.conf import settings
from django.db import models
from django.urls import reverse
from django.utils import timezone
from django.utils.translation import gettext_lazy as _

from apps.core.models import TimeStampedModel


class Inquiry(TimeStampedModel):
    class Kind(models.TextChoices):
        CONTACT = "contact", _("Contact")
        QUOTE = "quote", _("Demande de devis")
        DEMO = "demo", _("Demande de démonstration")
        PARTNERSHIP = "partnership", _("Partenariat")
        CAREER = "career", _("Candidature")
        SUPPORT = "support", _("Support")

    class Status(models.TextChoices):
        NEW = "new", _("Nouvelle")
        IN_PROGRESS = "in_progress", _("En cours")
        ANSWERED = "answered", _("Répondue")
        CLOSED = "closed", _("Clôturée")
        SPAM = "spam", _("Indésirable")

    class Budget(models.TextChoices):
        UNKNOWN = "", _("Non précisé")
        SMALL = "lt_10m", _("Moins de 10 M GNF")
        MEDIUM = "10m_50m", _("10 à 50 M GNF")
        LARGE = "50m_200m", _("50 à 200 M GNF")
        XLARGE = "gt_200m", _("Plus de 200 M GNF")

    class Timeline(models.TextChoices):
        UNKNOWN = "", _("Non précisé")
        ASAP = "asap", _("Dès que possible")
        QUARTER = "quarter", _("Dans les 3 mois")
        SEMESTER = "semester", _("Dans les 6 mois")
        LATER = "later", _("Plus tard / exploration")

    reference = models.CharField(_("référence"), max_length=20, unique=True, editable=False)
    kind = models.CharField(_("type de demande"), max_length=20, choices=Kind.choices, default=Kind.CONTACT, db_index=True)
    status = models.CharField(_("statut"), max_length=20, choices=Status.choices, default=Status.NEW, db_index=True)
    first_name = models.CharField(_("prénom"), max_length=80)
    last_name = models.CharField(_("nom"), max_length=80)
    organization = models.CharField(_("organisation"), max_length=160, blank=True)
    job_title = models.CharField(_("fonction"), max_length=120, blank=True)
    email = models.EmailField(_("email"), db_index=True)
    phone = models.CharField(_("téléphone"), max_length=40, blank=True)
    country = models.CharField(_("pays"), max_length=80, blank=True)
    subject = models.CharField(_("objet"), max_length=200)
    message = models.TextField(_("message"))
    category = models.ForeignKey("services.ServiceCategory", verbose_name=_("domaine concerné"), null=True, blank=True, on_delete=models.SET_NULL, related_name="inquiries")
    product = models.ForeignKey("products.Product", verbose_name=_("solution concernée"), null=True, blank=True, on_delete=models.SET_NULL, related_name="inquiries")
    plan = models.ForeignKey("pricing.PricingPlan", verbose_name=_("offre concernée"), null=True, blank=True, on_delete=models.SET_NULL, related_name="inquiries")
    budget = models.CharField(_("budget indicatif"), max_length=20, choices=Budget.choices, blank=True)
    timeline = models.CharField(_("échéance"), max_length=20, choices=Timeline.choices, blank=True)
    consent = models.BooleanField(_("consentement au traitement"), default=False)
    consent_at = models.DateTimeField(_("date du consentement"), null=True, blank=True)
    source_url = models.CharField(_("page d'origine"), max_length=255, blank=True)
    ip_address = models.GenericIPAddressField(_("adresse IP"), null=True, blank=True)
    user_agent = models.CharField(_("navigateur"), max_length=255, blank=True)
    assigned_to = models.ForeignKey(settings.AUTH_USER_MODEL, verbose_name=_("responsable"), null=True, blank=True, on_delete=models.SET_NULL, related_name="assigned_inquiries")
    lead = models.ForeignKey("Lead", verbose_name=_("prospect"), null=True, blank=True, on_delete=models.SET_NULL, related_name="inquiries")
    answered_at = models.DateTimeField(_("répondue le"), null=True, blank=True)

    class Meta:
        verbose_name = _("demande")
        verbose_name_plural = _("demandes")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return f"{self.reference} · {self.full_name}"

    def save(self, *args, **kwargs):
        if not self.reference:
            prefix = {"quote": "DEV", "demo": "DEM", "career": "CAN", "partnership": "PAR", "support": "SUP"}.get(self.kind, "CON")
            self.reference = f"{prefix}-{timezone.now():%y%m}-{secrets.token_hex(3).upper()}"
        super().save(*args, **kwargs)

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.last_name}".strip()

    def get_dashboard_url(self) -> str:
        return reverse("dashboard:inquiry_detail", kwargs={"pk": self.pk})


class Lead(TimeStampedModel):
    class Status(models.TextChoices):
        NEW = "new", _("Nouveau")
        QUALIFIED = "qualified", _("Qualifié")
        CONTACTED = "contacted", _("Contacté")
        DEMO = "demo", _("Démonstration")
        PROPOSAL = "proposal", _("Proposition")
        WON = "won", _("Gagné")
        LOST = "lost", _("Perdu")

    class Priority(models.TextChoices):
        LOW = "low", _("Basse")
        NORMAL = "normal", _("Normale")
        HIGH = "high", _("Haute")

    class Source(models.TextChoices):
        WEBSITE = "website", _("Site web")
        REFERRAL = "referral", _("Recommandation")
        EVENT = "event", _("Événement")
        SOCIAL = "social", _("Réseaux sociaux")
        OUTBOUND = "outbound", _("Prospection")
        PARTNER = "partner", _("Partenaire")
        OTHER = "other", _("Autre")

    PIPELINE = [Status.NEW, Status.QUALIFIED, Status.CONTACTED, Status.DEMO, Status.PROPOSAL, Status.WON, Status.LOST]

    first_name = models.CharField(_("prénom"), max_length=80)
    last_name = models.CharField(_("nom"), max_length=80, blank=True)
    email = models.EmailField(_("email"), db_index=True)
    phone = models.CharField(_("téléphone"), max_length=40, blank=True)
    organization = models.CharField(_("entreprise"), max_length=160, blank=True)
    job_title = models.CharField(_("fonction"), max_length=120, blank=True)
    country = models.CharField(_("pays"), max_length=80, blank=True)
    source = models.CharField(_("source"), max_length=20, choices=Source.choices, default=Source.WEBSITE)
    status = models.CharField(_("statut"), max_length=20, choices=Status.choices, default=Status.NEW, db_index=True)
    priority = models.CharField(_("priorité"), max_length=10, choices=Priority.choices, default=Priority.NORMAL)
    owner = models.ForeignKey(settings.AUTH_USER_MODEL, verbose_name=_("responsable"), null=True, blank=True, on_delete=models.SET_NULL, related_name="leads")
    interest = models.ForeignKey("services.ServiceCategory", verbose_name=_("service demandé"), null=True, blank=True, on_delete=models.SET_NULL, related_name="leads")
    product = models.ForeignKey("products.Product", verbose_name=_("solution"), null=True, blank=True, on_delete=models.SET_NULL, related_name="leads")
    estimated_value = models.DecimalField(_("valeur estimée"), max_digits=14, decimal_places=2, null=True, blank=True)
    currency = models.CharField(_("devise"), max_length=3, default="GNF")
    next_follow_up_at = models.DateTimeField(_("prochaine relance"), null=True, blank=True, db_index=True)
    follow_up_note = models.CharField(_("objet de la relance"), max_length=200, blank=True)
    notes = models.TextField(_("notes internes"), blank=True)
    is_client = models.BooleanField(_("client"), default=False)
    converted_at = models.DateTimeField(_("converti le"), null=True, blank=True)
    lost_reason = models.CharField(_("raison de la perte"), max_length=200, blank=True)

    class Meta:
        verbose_name = _("prospect")
        verbose_name_plural = _("prospects")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        name = f"{self.first_name} {self.last_name}".strip()
        return f"{name} ({self.organization})" if self.organization else name

    @property
    def full_name(self) -> str:
        return f"{self.first_name} {self.last_name}".strip()

    @property
    def follow_up_overdue(self) -> bool:
        return bool(self.next_follow_up_at and self.next_follow_up_at < timezone.now() and self.status not in ("won", "lost"))

    def get_dashboard_url(self) -> str:
        return reverse("dashboard:lead_detail", kwargs={"pk": self.pk})


class Activity(models.Model):
    class Kind(models.TextChoices):
        NOTE = "note", _("Note")
        CALL = "call", _("Appel")
        EMAIL = "email", _("Email")
        MEETING = "meeting", _("Rendez-vous")
        DEMO = "demo", _("Démonstration")
        STATUS = "status", _("Changement de statut")
        ASSIGNMENT = "assignment", _("Affectation")
        FOLLOW_UP = "follow_up", _("Relance")
        INQUIRY = "inquiry", _("Demande reçue")
        SYSTEM = "system", _("Système")

    lead = models.ForeignKey(Lead, null=True, blank=True, on_delete=models.CASCADE, related_name="activities")
    inquiry = models.ForeignKey(Inquiry, null=True, blank=True, on_delete=models.CASCADE, related_name="activities")
    kind = models.CharField(_("type"), max_length=20, choices=Kind.choices, default=Kind.NOTE)
    content = models.TextField(_("contenu"))
    author = models.ForeignKey(settings.AUTH_USER_MODEL, null=True, blank=True, on_delete=models.SET_NULL, related_name="crm_activities")
    created_at = models.DateTimeField(_("date"), default=timezone.now, db_index=True)

    class Meta:
        verbose_name = _("activité")
        verbose_name_plural = _("activités")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return f"{self.get_kind_display()} · {self.content[:40]}"


class NewsletterSubscriber(TimeStampedModel):
    email = models.EmailField(_("email"), unique=True)
    token = models.CharField(max_length=64, unique=True, editable=False)
    consent_at = models.DateTimeField(_("consentement"), default=timezone.now)
    confirmed_at = models.DateTimeField(_("confirmé le"), null=True, blank=True)
    unsubscribed_at = models.DateTimeField(_("désinscrit le"), null=True, blank=True)
    source = models.CharField(_("page d'inscription"), max_length=255, blank=True)

    class Meta:
        verbose_name = _("abonné newsletter")
        verbose_name_plural = _("abonnés newsletter")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return self.email

    def save(self, *args, **kwargs):
        if not self.token:
            self.token = secrets.token_urlsafe(32)
        super().save(*args, **kwargs)

    @property
    def is_active(self) -> bool:
        return bool(self.confirmed_at) and not self.unsubscribed_at

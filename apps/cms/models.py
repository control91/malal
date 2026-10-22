"""Editorial content: pages built from sections, navigation, FAQ, team, partners, careers, media."""

from pathlib import Path

from django.conf import settings
from django.core.cache import cache
from django.db import models
from django.urls import reverse
from django.utils import timezone
from django.utils.translation import gettext_lazy as _

from apps.core.fields import OptimizedImageField, RichTextField
from apps.core.icons import ICON_CHOICES
from apps.core.models import OrderedModel, PublishedQuerySet, PublishStatus, SeoModel, TimeStampedModel
from apps.core.validators import validate_document_file, validate_logo_file

MENUS_CACHE_KEY = "cms:menus"


class Page(TimeStampedModel, SeoModel):
    """A page of the public site, made of an optional rich body and ordered sections."""

    class Template(models.TextChoices):
        HOME = "home", _("Accueil")
        STANDARD = "standard", _("Standard (sections)")
        LEGAL = "legal", _("Texte légal / long")

    title = models.CharField(_("titre"), max_length=200)
    slug = models.SlugField(_("adresse (slug)"), max_length=120, unique=True)
    template = models.CharField(_("gabarit"), max_length=20, choices=Template.choices, default=Template.STANDARD)
    eyebrow = models.CharField(_("sur-titre"), max_length=120, blank=True)
    intro = models.TextField(_("introduction"), blank=True)
    body = RichTextField(_("contenu"), blank=True)
    status = models.CharField(_("statut"), max_length=20, choices=PublishStatus.choices, default=PublishStatus.DRAFT)
    is_system = models.BooleanField(_("page système"), default=False, help_text=_("Les pages système ne peuvent pas être supprimées."))

    objects = PublishedQuerySet.as_manager()

    class Meta:
        verbose_name = _("page")
        verbose_name_plural = _("pages")
        ordering = ["title"]

    def __str__(self) -> str:
        return self.title

    def get_absolute_url(self) -> str:
        if self.template == self.Template.HOME or self.slug == "accueil":
            return reverse("cms:home")
        return reverse("cms_pages:page", kwargs={"slug": self.slug})


class PageSection(OrderedModel, TimeStampedModel):
    """A configurable block. ``kind`` selects the template used to render it."""

    class Kind(models.TextChoices):
        HERO = "hero", _("Hero (bandeau principal)")
        DOMAINS = "domains", _("Domaines d'expertise (catégories)")
        SOLUTIONS = "solutions", _("Solutions mises en avant")
        FEATURES = "features", _("Grille d'éléments (icône + texte)")
        VISION = "vision", _("Vision Afrique (carte)")
        NEWS = "news", _("Dernières actualités")
        TEXT = "text", _("Texte riche")
        SPLIT = "split", _("Texte + image")
        ROADMAP = "roadmap", _("Feuille de route")
        TEAM = "team", _("Équipe")
        PARTNERS = "partners", _("Partenaires")
        TESTIMONIALS = "testimonials", _("Témoignages")
        CASE_STUDIES = "case_studies", _("Réalisations")
        FAQ = "faq", _("Questions fréquentes")
        CTA = "cta", _("Appel à l'action")

    class Theme(models.TextChoices):
        LIGHT = "light", _("Clair")
        MUTED = "muted", _("Gris léger")
        DARK = "dark", _("Sombre (bleu nuit)")

    page = models.ForeignKey(Page, verbose_name=_("page"), on_delete=models.CASCADE, related_name="sections")
    kind = models.CharField(_("type de section"), max_length=20, choices=Kind.choices, default=Kind.TEXT)
    theme = models.CharField(_("thème"), max_length=10, choices=Theme.choices, default=Theme.LIGHT)
    anchor = models.SlugField(_("ancre"), max_length=60, blank=True, help_text=_("Identifiant pour les liens #ancre."))
    eyebrow = models.CharField(_("sur-titre"), max_length=120, blank=True)
    title = models.CharField(_("titre"), max_length=200, blank=True)
    subtitle = models.TextField(_("sous-titre"), blank=True)
    body = RichTextField(_("texte"), blank=True)
    image = OptimizedImageField(_("image"), upload_to="sections/", blank=True)
    image_alt = models.CharField(_("texte alternatif de l'image"), max_length=200, blank=True)
    cta_label = models.CharField(_("bouton principal — libellé"), max_length=60, blank=True)
    cta_url = models.CharField(_("bouton principal — lien"), max_length=255, blank=True)
    cta2_label = models.CharField(_("bouton secondaire — libellé"), max_length=60, blank=True)
    cta2_url = models.CharField(_("bouton secondaire — lien"), max_length=255, blank=True)
    limit = models.PositiveSmallIntegerField(_("nombre d'éléments affichés"), default=6, help_text=_("Pour les sections alimentées automatiquement (actualités, solutions…)."))
    is_active = models.BooleanField(_("visible"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("section")
        verbose_name_plural = _("sections")

    def __str__(self) -> str:
        return f"{self.page} · {self.title or self.get_kind_display()}"

    @property
    def template_name(self) -> str:
        return f"public/sections/{self.kind}.html"


class SectionItem(OrderedModel):
    """Repeatable element of a section (feature card, value, roadmap milestone…)."""

    class Progress(models.TextChoices):
        NONE = "", _("—")
        DONE = "done", _("Réalisé")
        IN_PROGRESS = "in_progress", _("En cours")
        PLANNED = "planned", _("Prévu")

    section = models.ForeignKey(PageSection, on_delete=models.CASCADE, related_name="items")
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    title = models.CharField(_("titre"), max_length=160)
    text = models.TextField(_("texte"), blank=True)
    badge = models.CharField(_("badge / période"), max_length=60, blank=True)
    progress = models.CharField(_("avancement"), max_length=20, blank=True, choices=Progress.choices)
    link_url = models.CharField(_("lien"), max_length=255, blank=True)
    link_label = models.CharField(_("libellé du lien"), max_length=60, blank=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("élément de section")
        verbose_name_plural = _("éléments de section")

    def __str__(self) -> str:
        return self.title


class NavigationMenu(models.Model):
    class Location(models.TextChoices):
        HEADER = "header", _("En-tête")
        FOOTER_SOLUTIONS = "footer_solutions", _("Footer — Solutions")
        FOOTER_COMPANY = "footer_company", _("Footer — Entreprise")
        FOOTER_RESOURCES = "footer_resources", _("Footer — Ressources")
        FOOTER_LEGAL = "footer_legal", _("Footer — Légal")

    key = models.CharField(_("emplacement"), max_length=30, choices=Location.choices, unique=True)
    title = models.CharField(_("titre affiché"), max_length=80, blank=True)

    class Meta:
        verbose_name = _("menu de navigation")
        verbose_name_plural = _("menus de navigation")

    def __str__(self) -> str:
        return self.get_key_display()

    @classmethod
    def cached_menus(cls) -> dict:
        menus = cache.get(MENUS_CACHE_KEY)
        if menus is None:
            menus = {}
            items = (
                NavigationItem.objects.filter(is_active=True, parent__isnull=True)
                .select_related("menu")
                .prefetch_related(models.Prefetch("children", queryset=NavigationItem.objects.filter(is_active=True)))
            )
            for menu in cls.objects.all():
                menus[menu.key] = {"title": menu.title, "items": []}
            for item in items:
                menus.setdefault(item.menu.key, {"title": item.menu.title, "items": []})["items"].append(
                    {
                        "label": item.label,
                        "url": item.url,
                        "description": item.description,
                        "icon": item.icon,
                        "new_tab": item.open_in_new_tab,
                        "is_button": item.style == NavigationItem.Style.BUTTON,
                        "children": [
                            {"label": c.label, "url": c.url, "description": c.description, "icon": c.icon, "new_tab": c.open_in_new_tab}
                            for c in item.children.all()
                        ],
                    }
                )
            cache.set(MENUS_CACHE_KEY, menus, 600)
        return menus


class NavigationItem(OrderedModel):
    class Style(models.TextChoices):
        LINK = "link", _("Lien")
        BUTTON = "button", _("Bouton")

    menu = models.ForeignKey(NavigationMenu, verbose_name=_("menu"), on_delete=models.CASCADE, related_name="items")
    parent = models.ForeignKey(
        "self", verbose_name=_("élément parent"), null=True, blank=True, on_delete=models.CASCADE, related_name="children",
        help_text=_("Pour créer un sous-menu déroulant."),
    )
    label = models.CharField(_("libellé"), max_length=80)
    url = models.CharField(_("lien"), max_length=255, help_text=_("Chemin interne (/blog/) ou URL complète."))
    description = models.CharField(_("description courte"), max_length=140, blank=True)
    icon = models.CharField(_("icône"), max_length=40, blank=True, choices=ICON_CHOICES)
    style = models.CharField(_("style"), max_length=10, choices=Style.choices, default=Style.LINK)
    open_in_new_tab = models.BooleanField(_("ouvrir dans un nouvel onglet"), default=False)
    is_active = models.BooleanField(_("actif"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("élément de menu")
        verbose_name_plural = _("éléments de menu")

    def __str__(self) -> str:
        return self.label


class FAQ(OrderedModel, TimeStampedModel):
    question = models.CharField(_("question"), max_length=255)
    answer = RichTextField(_("réponse"))
    category = models.ForeignKey(
        "services.ServiceCategory", verbose_name=_("domaine"), null=True, blank=True, on_delete=models.SET_NULL, related_name="faqs",
        help_text=_("Laisser vide pour une question générale."),
    )
    is_published = models.BooleanField(_("publiée"), default=True)

    class Meta(OrderedModel.Meta):
        verbose_name = _("question fréquente")
        verbose_name_plural = _("FAQ")

    def __str__(self) -> str:
        return self.question


class TeamMember(OrderedModel, TimeStampedModel):
    name = models.CharField(_("nom"), max_length=120)
    role = models.CharField(_("fonction"), max_length=120)
    bio = models.TextField(_("biographie"), blank=True)
    photo = OptimizedImageField(_("photo"), upload_to="team/", blank=True)
    linkedin_url = models.URLField("LinkedIn", blank=True)
    is_leadership = models.BooleanField(_("membre de la direction"), default=False)
    is_published = models.BooleanField(_("publié"), default=False)

    class Meta(OrderedModel.Meta):
        verbose_name = _("membre de l'équipe")
        verbose_name_plural = _("équipe")

    def __str__(self) -> str:
        return self.name


class Partner(OrderedModel, TimeStampedModel):
    class Kind(models.TextChoices):
        TECHNOLOGY = "technology", _("Technologique")
        COMMERCIAL = "commercial", _("Commercial")
        INSTITUTIONAL = "institutional", _("Institutionnel")
        ACADEMIC = "academic", _("Académique")

    name = models.CharField(_("nom"), max_length=120)
    kind = models.CharField(_("type"), max_length=20, choices=Kind.choices, default=Kind.TECHNOLOGY)
    logo = models.FileField(_("logo"), upload_to="partners/", blank=True, validators=[validate_logo_file])
    url = models.URLField(_("site web"), blank=True)
    description = models.TextField(_("description"), blank=True)
    agreement_confirmed = models.BooleanField(
        _("partenariat formalisé"), default=False,
        help_text=_("Ne publiez un partenaire que si l'accord existe réellement et autorise la mention publique."),
    )
    is_published = models.BooleanField(_("publié"), default=False)

    class Meta(OrderedModel.Meta):
        verbose_name = _("partenaire")
        verbose_name_plural = _("partenaires")

    def __str__(self) -> str:
        return self.name


class JobOpeningQuerySet(models.QuerySet):
    def open(self):
        today = timezone.localdate()
        return self.filter(is_open=True).filter(models.Q(closes_on__isnull=True) | models.Q(closes_on__gte=today))


class JobOpening(TimeStampedModel):
    class Contract(models.TextChoices):
        CDI = "cdi", "CDI"
        CDD = "cdd", "CDD"
        INTERNSHIP = "internship", _("Stage")
        FREELANCE = "freelance", _("Freelance")

    title = models.CharField(_("intitulé"), max_length=160)
    slug = models.SlugField(_("slug"), max_length=160, unique=True)
    department = models.CharField(_("pôle"), max_length=80, blank=True)
    location = models.CharField(_("lieu"), max_length=80, default="Conakry")
    contract = models.CharField(_("contrat"), max_length=20, choices=Contract.choices, default=Contract.CDI)
    remote_possible = models.BooleanField(_("télétravail possible"), default=False)
    summary = models.CharField(_("résumé"), max_length=255, blank=True)
    description = RichTextField(_("description"))
    is_open = models.BooleanField(_("ouverte"), default=True)
    closes_on = models.DateField(_("date limite"), null=True, blank=True)

    objects = JobOpeningQuerySet.as_manager()

    class Meta:
        verbose_name = _("offre d'emploi")
        verbose_name_plural = _("offres d'emploi")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return self.title

    def get_absolute_url(self) -> str:
        return reverse("cms:job_detail", kwargs={"slug": self.slug})


class MediaAsset(TimeStampedModel):
    """Shared media library (images, PDFs, brochures) referenced by URL."""

    title = models.CharField(_("titre"), max_length=160)
    file = models.FileField(_("fichier"), upload_to="library/%Y/%m/", validators=[validate_document_file])
    alt_text = models.CharField(_("texte alternatif"), max_length=200, blank=True)
    uploaded_by = models.ForeignKey(settings.AUTH_USER_MODEL, null=True, blank=True, on_delete=models.SET_NULL, verbose_name=_("ajouté par"))
    size = models.PositiveIntegerField(_("taille (octets)"), default=0, editable=False)

    class Meta:
        verbose_name = _("média")
        verbose_name_plural = _("médiathèque")
        ordering = ["-created_at"]

    def __str__(self) -> str:
        return self.title

    def save(self, *args, **kwargs):
        if self.file and not self.file._committed:
            self.size = self.file.size or 0
        super().save(*args, **kwargs)

    @property
    def is_image(self) -> bool:
        return Path(self.file.name).suffix.lower() in {".jpg", ".jpeg", ".png", ".webp", ".gif"}

    @property
    def extension(self) -> str:
        return Path(self.file.name).suffix.lower().lstrip(".")

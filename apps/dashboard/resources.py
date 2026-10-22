"""Back-office resources. Adding a model to the dashboard = adding a Resource here."""

from django.urls import reverse

from apps.accounts.models import User
from apps.audit.models import AuditLog
from apps.audit.services import record
from apps.blog.models import BlogArticle, BlogCategory, BlogTag
from apps.cms.models import FAQ, JobOpening, MediaAsset, NavigationItem, NavigationMenu, Page, PageSection, Partner, SectionItem, TeamMember
from apps.crm.models import Inquiry, Lead, NewsletterSubscriber
from apps.portfolio.models import CaseStudy, CaseStudyImage, Testimonial
from apps.pricing.models import PricingFeature, PricingGroup, PricingOption, PricingPlan
from apps.products.models import Product, ProductFeature, ProductScreenshot
from apps.services.models import Service, ServiceCategory, StatusComponent

from .crud import Column, Inline, Resource, register
from .forms import LeadForm, UserForm

SEO = ("Référencement (SEO)", ["meta_title", "meta_description", "og_image", "noindex"])

# ---------------------------------------------------------------------------
# Site content
# ---------------------------------------------------------------------------


class PageResource(Resource):
    def success_url(self, request, obj):
        if request.POST.get("_continue") or not request.resolver_match.kwargs.get("pk"):
            return self.url("update", obj.pk)
        return super().success_url(request, obj)


register(PageResource(
    slug="pages", model=Page, area="content", label="page", label_plural="Pages", icon="panels-top-left", group="Site",
    description="Pages du site et leurs sections (accueil, entreprise, mentions légales…).",
    columns=[Column("title", "Titre", "title", sort="title"), Column("slug", "Adresse", "muted"), Column("get_template_display", "Gabarit"),
             Column("get_status_display", "Statut", "badge"), Column("updated_at", "Modifiée", "datetime", sort="updated_at")],
    search_fields=["title", "slug"], filters=["status", "template"], publish_field="status",
    fieldsets=[("Page", ["title", "slug", "template", "status", "eyebrow", "intro", "body"]), SEO],
    form_template="dashboard/cms/page_form.html",
))


class SectionResource(Resource):
    def success_url(self, request, obj):
        if request.POST.get("_continue"):
            return self.url("update", obj.pk)
        return reverse("dashboard:pages_update", args=[obj.page_id]) + "#sections"


register(SectionResource(
    slug="sections", model=PageSection, area="content", label="section", label_plural="Sections", icon="layers", group="Site",
    show_in_menu=False, select_related=["page"],
    columns=[Column("page", "Page"), Column("get_kind_display", "Type"), Column("title", "Titre", "title"), Column("is_active", "Visible", "bool")],
    filters=["page", "kind"], search_fields=["title"], publish_field="is_active",
    fieldsets=[
        ("Section", ["page", "kind", "theme", "order", "is_active", "anchor"]),
        ("Textes", ["eyebrow", "title", "subtitle", "body"]),
        ("Image", ["image", "image_alt"]),
        ("Boutons", ["cta_label", "cta_url", "cta2_label", "cta2_url", "limit"]),
    ],
    inlines=[Inline(SectionItem, ["order", "icon", "title", "text", "badge", "progress", "link_label", "link_url"], "Éléments de la section",
                    help="Cartes, valeurs, étapes de la feuille de route…")],
    form_template="dashboard/cms/section_form.html",
))

register(Resource(
    slug="menus", model=NavigationMenu, area="content", label="menu", label_plural="Navigation", icon="navigation", group="Site",
    description="En-tête et colonnes du footer.", can_delete=False,
    columns=[Column("get_key_display", "Emplacement", "title"), Column("title", "Titre affiché")],
    fields=["key", "title"],
    inlines=[Inline(NavigationItem, ["order", "label", "url", "parent", "description", "icon", "style", "open_in_new_tab", "is_active"], "Liens du menu", extra=1)],
))

register(Resource(
    slug="faq", model=FAQ, area="content", label="question", label_plural="FAQ", icon="circle-help", group="Site",
    columns=[Column("question", "Question", "title"), Column("category", "Domaine"), Column("order", "Ordre", sort="order"), Column("is_published", "Publiée", "bool")],
    search_fields=["question"], filters=["category", "is_published"], publish_field="is_published",
    fields=["question", "answer", "category", "order", "is_published"],
))

register(Resource(
    slug="equipe", model=TeamMember, area="content", label="membre", label_plural="Équipe", icon="users", group="Site",
    columns=[Column("photo", "", "image"), Column("name", "Nom", "title"), Column("role", "Fonction"), Column("is_leadership", "Direction", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["name", "role"], filters=["is_published", "is_leadership"], publish_field="is_published",
    fields=["name", "role", "bio", "photo", "linkedin_url", "is_leadership", "order", "is_published"],
))

register(Resource(
    slug="partenaires", model=Partner, area="content", label="partenaire", label_plural="Partenaires", icon="handshake", group="Site",
    description="Ne publiez que des partenariats formalisés autorisant la mention publique.",
    columns=[Column("name", "Nom", "title"), Column("get_kind_display", "Type"), Column("agreement_confirmed", "Formalisé", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["name"], filters=["kind", "is_published"], publish_field="is_published",
    fields=["name", "kind", "logo", "url", "description", "agreement_confirmed", "order", "is_published"],
))

register(Resource(
    slug="offres-emploi", model=JobOpening, area="content", label="offre d'emploi", label_plural="Carrières", icon="briefcase", group="Site",
    columns=[Column("title", "Intitulé", "title"), Column("get_contract_display", "Contrat"), Column("location", "Lieu"), Column("closes_on", "Date limite", "date"), Column("is_open", "Ouverte", "bool")],
    search_fields=["title"], filters=["contract", "is_open"], publish_field="is_open",
    fields=["title", "slug", "department", "location", "contract", "remote_possible", "summary", "description", "closes_on", "is_open"],
))

register(Resource(
    slug="medias", model=MediaAsset, area="media", label="média", label_plural="Médiathèque", icon="images", group="Site",
    description="Images et documents réutilisables : copiez leur adresse pour les insérer dans un contenu.",
    columns=[Column("file", "Aperçu", "image"), Column("title", "Titre", "title"), Column("extension", "Type", "muted"), Column("created_at", "Ajouté", "datetime", sort="created_at")],
    search_fields=["title", "alt_text"], fields=["title", "file", "alt_text"], list_template="dashboard/cms/media_list.html", per_page=24,
))

# ---------------------------------------------------------------------------
# Catalogue
# ---------------------------------------------------------------------------
register(Resource(
    slug="domaines", model=ServiceCategory, area="catalog", label="domaine", label_plural="Domaines d'expertise", icon="layout-grid", group="Catalogue",
    columns=[Column("name", "Domaine", "title", sort="name"), Column("slug", "Adresse", "muted"), Column("order", "Ordre", sort="order"),
             Column("show_on_home", "Accueil", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["name"], filters=["is_published"], publish_field="is_published",
    fieldsets=[
        ("Domaine", ["name", "slug", "icon", "tagline", "summary", "order", "is_published", "show_on_home"]),
        ("Page dédiée", ["hero_title", "hero_subtitle", "description", "image", "show_hosting_disclosure", "demo_space_enabled"]),
        SEO,
    ],
    inlines=[Inline(Service, ["order", "name", "slug", "icon", "summary", "details", "status", "is_featured", "is_published"], "Services du domaine",
                    help="Distinguez clairement : Disponible, Bêta, À venir.")],
))

register(Resource(
    slug="services", model=Service, area="catalog", label="service", label_plural="Services", icon="server", group="Catalogue",
    select_related=["category"],
    columns=[Column("name", "Service", "title", sort="name"), Column("category", "Domaine"), Column("get_status_display", "Disponibilité", "badge"),
             Column("is_featured", "En avant", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["name", "summary"], filters=["category", "status", "is_published"], publish_field="is_published",
    fields=["category", "name", "slug", "icon", "summary", "details", "status", "is_featured", "order", "is_published"],
))

register(Resource(
    slug="solutions", model=Product, area="catalog", label="solution", label_plural="Solutions", icon="package", group="Catalogue",
    select_related=["category"],
    columns=[Column("logo", "", "image"), Column("name", "Solution", "title", sort="name"), Column("category", "Domaine"), Column("get_status_display", "Disponibilité", "badge"),
             Column("is_featured", "Accueil", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["name", "tagline"], filters=["category", "status", "is_published", "is_example"], publish_field="is_published",
    fieldsets=[
        ("Présentation", ["name", "slug", "category", "icon", "tagline", "summary", "description", "benefits", "audience", "logo"]),
        ("Publication", ["status", "is_published", "is_featured", "is_example", "order"]),
        ("Commercial", ["demo_enabled", "show_pricing", "external_url"]),
        SEO,
    ],
    inlines=[
        Inline(ProductFeature, ["order", "icon", "title", "description"], "Fonctionnalités", extra=1),
        Inline(ProductScreenshot, ["order", "image", "caption"], "Captures d'écran"),
    ],
))

register(Resource(
    slug="tarifs", model=PricingGroup, area="catalog", label="groupe d'offres", label_plural="Tarifs", icon="wallet", group="Catalogue",
    description="Chaque groupe est un tableau comparatif sur la page Tarifs.",
    columns=[Column("name", "Groupe", "title"), Column("category", "Domaine"), Column("product", "Solution"), Column("order", "Ordre", sort="order"), Column("is_published", "Publié", "bool")],
    search_fields=["name"], filters=["is_published", "category"], publish_field="is_published",
    fields=["name", "slug", "description", "category", "product", "order", "is_published"],
    inlines=[Inline(PricingOption, ["order", "name", "description", "price_label"], "Options payantes")],
    form_template="dashboard/pricing/group_form.html",
))


class PlanResource(Resource):
    def success_url(self, request, obj):
        if request.POST.get("_continue"):
            return self.url("update", obj.pk)
        return reverse("dashboard:tarifs_update", args=[obj.group_id])


register(PlanResource(
    slug="offres", model=PricingPlan, area="catalog", label="offre", label_plural="Offres tarifaires", icon="receipt", group="Catalogue",
    show_in_menu=False, select_related=["group"],
    columns=[Column("name", "Offre", "title"), Column("group", "Groupe"), Column("price_monthly", "Mensuel", "money"), Column("get_status_display", "Disponibilité", "badge"), Column("is_published", "Publié", "bool")],
    filters=["group", "status"], publish_field="is_published",
    fieldsets=[
        ("Offre", ["group", "name", "description", "status", "is_highlighted", "order", "is_published"]),
        ("Prix", ["currency", "price_monthly", "price_yearly", "price_on_request", "price_note"]),
        ("Bouton", ["cta_label", "cta_url"]),
    ],
    inlines=[Inline(PricingFeature, ["order", "label", "value", "included"], "Caractéristiques comparées", extra=2,
                    help="Utilisez le même libellé dans chaque offre du groupe pour aligner le tableau comparatif.")],
))

register(Resource(
    slug="statut", model=StatusComponent, area="catalog", label="composant", label_plural="Statut des services", icon="activity", group="Catalogue",
    columns=[Column("name", "Composant", "title"), Column("get_state_display", "État", "badge"), Column("updated_at", "Mis à jour", "datetime"), Column("is_published", "Publié", "bool")],
    filters=["state"], publish_field="is_published", fields=["name", "description", "state", "message", "order", "is_published"],
))

# ---------------------------------------------------------------------------
# Editorial
# ---------------------------------------------------------------------------
register(Resource(
    slug="articles", model=BlogArticle, area="blog", label="article", label_plural="Articles", icon="newspaper", group="Éditorial",
    select_related=["category", "author"], ordering=["-created_at"],
    columns=[Column("cover", "", "image"), Column("title", "Titre", "title", sort="title"), Column("category", "Catégorie"), Column("author", "Auteur"),
             Column("display_status", "Statut", "badge"), Column("published_at", "Publication", "datetime", sort="published_at")],
    search_fields=["title", "excerpt"], filters=["status", "category", "author", "is_featured"], publish_field="status",
    fieldsets=[
        ("Article", ["title", "slug", "excerpt", "body"]),
        ("Classement", ["category", "tags", "author", "is_featured"]),
        ("Couverture", ["cover", "cover_alt"]),
        ("Publication", ["status", "published_at"]),
        SEO,
    ],
    form_template="dashboard/blog/article_form.html",
))
register(Resource(
    slug="categories-blog", model=BlogCategory, area="blog", label="catégorie", label_plural="Catégories", icon="folder", group="Éditorial",
    columns=[Column("name", "Nom", "title"), Column("slug", "Adresse", "muted"), Column("order", "Ordre")], fields=["name", "slug", "description", "order"],
))
register(Resource(
    slug="etiquettes", model=BlogTag, area="blog", label="étiquette", label_plural="Étiquettes", icon="tags", group="Éditorial",
    columns=[Column("name", "Nom", "title"), Column("slug", "Adresse", "muted")], search_fields=["name"], fields=["name", "slug"],
))
register(Resource(
    slug="realisations", model=CaseStudy, area="portfolio", label="réalisation", label_plural="Réalisations", icon="award", group="Éditorial",
    columns=[Column("cover", "", "image"), Column("title", "Projet", "title"), Column("sector", "Secteur"), Column("publication_consent", "Consentement", "bool"),
             Column("is_example", "Exemple", "bool"), Column("get_status_display", "Statut", "badge")],
    search_fields=["title", "client_name", "sector"], filters=["status", "is_example", "publication_consent"], publish_field="status",
    fieldsets=[
        ("Projet", ["title", "slug", "sector", "year", "summary", "services", "technologies", "cover"]),
        ("Client", ["client_name", "show_client_name", "publication_consent", "consent_reference", "is_example"]),
        ("Contenu", ["challenge", "solution", "results"]),
        ("Publication", ["status", "order"]),
        SEO,
    ],
    inlines=[Inline(CaseStudyImage, ["order", "image", "caption"], "Galerie d'images")],
))
register(Resource(
    slug="temoignages", model=Testimonial, area="portfolio", label="témoignage", label_plural="Témoignages", icon="quote", group="Éditorial",
    description="Uniquement des témoignages réels, avec consentement écrit.",
    columns=[Column("author_name", "Auteur", "title"), Column("organization", "Organisation"), Column("consent_obtained", "Consentement", "bool"), Column("is_published", "Publié", "bool")],
    search_fields=["author_name", "organization"], filters=["is_published"], publish_field="is_published",
    fields=["author_name", "author_role", "organization", "quote", "photo", "case_study", "consent_obtained", "consent_reference", "order", "is_published"],
))

# ---------------------------------------------------------------------------
# Sales
# ---------------------------------------------------------------------------
register(Resource(
    slug="demandes", model=Inquiry, area="inquiries", label="demande", label_plural="Demandes", icon="inbox", group="Commercial",
    select_related=["assigned_to", "product", "category"], can_create=False, can_edit=False, detail_url_name="dashboard:inquiry_detail",
    description="Contacts, devis, démonstrations et candidatures reçus via le site.",
    columns=[Column("reference", "Réf.", "muted"), Column("full_name", "Demandeur", "title"), Column("organization", "Organisation"), Column("get_kind_display", "Type"),
             Column("get_status_display", "Statut", "badge"), Column("assigned_to", "Responsable"), Column("created_at", "Reçue", "datetime", sort="created_at")],
    search_fields=["reference", "first_name", "last_name", "email", "organization", "subject"],
    filters=["kind", "status", "assigned_to", "category"],
    exportable=["reference", "created_at", "kind", "status", "first_name", "last_name", "organization", "job_title", "email", "phone", "country",
                "subject", "message", "category", "product", "budget", "timeline", "assigned_to"],
))


class LeadResource(Resource):
    def success_url(self, request, obj):
        return reverse("dashboard:lead_detail", args=[obj.pk])


register(LeadResource(
    slug="prospects", model=Lead, area="crm", label="prospect", label_plural="Prospects", icon="contact", group="Commercial",
    select_related=["owner", "interest"], form_class=LeadForm, detail_url_name="dashboard:lead_detail",
    columns=[Column("full_name", "Prospect", "title"), Column("organization", "Entreprise"), Column("get_status_display", "Statut", "badge"),
             Column("get_priority_display", "Priorité"), Column("owner", "Responsable"), Column("next_follow_up_at", "Relance", "datetime", sort="next_follow_up_at"),
             Column("created_at", "Créé", "datetime", sort="created_at")],
    search_fields=["first_name", "last_name", "email", "organization"], filters=["status", "priority", "owner", "source", "interest", "is_client"],
    fieldsets=[
        ("Contact", ["first_name", "last_name", "email", "phone", "organization", "job_title", "country"]),
        ("Qualification", ["source", "status", "priority", "owner", "interest", "product", "estimated_value", "currency"]),
        ("Suivi", ["next_follow_up_at", "follow_up_note", "lost_reason", "notes"]),
    ],
    exportable=["created_at", "first_name", "last_name", "email", "phone", "organization", "job_title", "country", "source", "status", "priority",
                "owner", "interest", "estimated_value", "currency", "next_follow_up_at", "is_client", "converted_at"],
))
register(Resource(
    slug="newsletter", model=NewsletterSubscriber, area="newsletter", label="abonné", label_plural="Newsletter", icon="mail", group="Commercial",
    can_create=False, can_edit=False,
    columns=[Column("email", "Email", "title"), Column("is_active", "Actif", "bool"), Column("confirmed_at", "Confirmé", "datetime"), Column("unsubscribed_at", "Désinscrit", "datetime"),
             Column("created_at", "Inscription", "datetime", sort="created_at")],
    search_fields=["email"], exportable=["email", "consent_at", "confirmed_at", "unsubscribed_at", "source"],
))

# ---------------------------------------------------------------------------
# Administration
# ---------------------------------------------------------------------------


class UserResource(Resource):
    def get_queryset(self, request):
        return User.objects.filter(is_staff=True).order_by("first_name", "email")

    def get_form_kwargs(self, request, obj=None):
        return {"request_user": request.user}

    def can_delete_obj(self, obj):
        return False  # deactivate instead: keeps the audit trail intact

    def before_save(self, request, obj, form, created):
        if not created and "role" in form.changed_data:
            record(request, AuditLog.Action.ROLE_CHANGE, obj, changes={"role": [form.initial.get("role"), obj.role]})


register(UserResource(
    slug="utilisateurs", model=User, area="users", label="utilisateur", label_plural="Utilisateurs", icon="user-cog", group="Administration",
    description="Comptes du back-office. Désactivez un compte plutôt que de le supprimer.", form_class=UserForm, can_delete=False,
    columns=[Column("avatar", "", "image"), Column("display_name", "Nom", "title"), Column("email", "Email", "muted"), Column("get_role_display", "Rôle", "badge"),
             Column("is_active", "Actif", "bool"), Column("last_login", "Dernière connexion", "datetime", sort="last_login")],
    search_fields=["email", "first_name", "last_name"], filters=["role", "is_active"],
    fieldsets=[("Identité", ["email", "first_name", "last_name", "job_title", "phone", "avatar", "bio"]), ("Accès", ["role", "is_active", "password"])],
))
register(Resource(
    slug="audit", model=AuditLog, area="audit", label="entrée", label_plural="Journal d'audit", icon="history", group="Administration",
    can_create=False, can_edit=False, can_delete=False, select_related=["actor"], per_page=50,
    columns=[Column("created_at", "Date", "datetime", sort="created_at"), Column("actor_label", "Auteur", "title"), Column("get_action_display", "Action", "badge"),
             Column("object_type", "Type", "muted"), Column("object_repr", "Objet"), Column("ip_address", "IP", "muted")],
    search_fields=["actor_label", "object_repr", "object_type"], filters=["action", "actor"],
    exportable=["created_at", "actor_label", "action", "object_type", "object_id", "object_repr", "ip_address"],
    list_template="dashboard/audit/list.html",
))

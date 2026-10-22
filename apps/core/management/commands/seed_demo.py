"""
Development / demonstration data — NEVER run in production.

Everything created here is explicitly flagged as an example (``is_example``
flags, « Exemple » badges, example.com emails, « [Démo] » notes). No real
client, testimonial, certification or performance figure is invented.

    python manage.py seed_demo [--with-users]
"""

import secrets
from datetime import timedelta
from decimal import Decimal

from django.conf import settings
from django.contrib.auth import get_user_model
from django.core.management import call_command
from django.core.management.base import BaseCommand, CommandError
from django.db import transaction
from django.utils import timezone

from apps.accounts.roles import Role
from apps.blog.models import BlogArticle, BlogCategory, BlogTag
from apps.cms.models import JobOpening
from apps.core.models import Availability, PublishStatus
from apps.crm.models import Activity, Inquiry, Lead
from apps.portfolio.models import CaseStudy
from apps.pricing.models import PricingFeature, PricingGroup, PricingOption, PricingPlan
from apps.products.models import Product, ProductFeature
from apps.services.models import ServiceCategory, StatusComponent

PRODUCTS = [
    ("saas", "gestion-commerciale", "Exemple Ventes", "shopping-cart", "La gestion commerciale simplifiée pour les PME",
     "Devis, factures, stocks et suivi des paiements (y compris mobile money) dans un seul outil en ligne.", Availability.AVAILABLE,
     [("receipt", "Devis et factures", "Création en quelques clics, envoi par email ou WhatsApp."), ("boxes", "Stocks", "Suivi des entrées, sorties et alertes de réapprovisionnement."), ("wallet", "Paiements", "Rapprochement des paiements, y compris mobile money."), ("chart-bar", "Tableaux de bord", "Chiffre d'affaires et impayés en temps réel.")]),
    ("saas", "gestion-rh", "Exemple RH", "users", "Les ressources humaines sans paperasse",
     "Dossiers du personnel, congés, présences et préparation de la paie.", Availability.BETA,
     [("contact", "Dossiers salariés", "Centralisation sécurisée des informations."), ("calendar", "Congés et absences", "Demandes et validations en ligne."), ("clock", "Présences", "Pointage simple, y compris sur mobile.")]),
    ("intelligence-artificielle", "assistant-documentaire", "Exemple Assistant", "bot", "Un assistant qui répond à partir de vos documents",
     "Posez vos questions en français : l'assistant cherche la réponse dans vos procédures, contrats et notes internes.", Availability.BETA,
     [("file-search", "Recherche intelligente", "Réponses sourcées à partir de vos documents."), ("lock", "Confidentialité", "Accès restreint par utilisateur et par dossier."), ("languages", "Multilingue", "Français et anglais, autres langues à l'étude.")]),
    ("cloud-computing", "hebergement-applications", "Exemple Hébergement", "cloud", "Votre application en ligne, administrée",
     "Hébergement d'applications web et Docker avec sauvegardes, certificats et supervision.", Availability.AVAILABLE,
     [("container", "Docker et frameworks", "Django, Laravel, Node.js ou vos propres images."), ("database-backup", "Sauvegardes", "Sauvegardes régulières et restauration accompagnée."), ("shield-check", "TLS inclus", "Certificats HTTPS gérés automatiquement.")]),
    ("intelligence-artificielle", "api-ia", "Exemple API IA", "webhook", "Des API d'IA pour les développeurs",
     "Classification de textes, extraction d'informations et transcription, accessibles par API.", Availability.COMING_SOON,
     [("code", "API REST", "Documentation et exemples."), ("zap", "Paiement à l'usage", "Facturation simple au volume.")]),
]

ARTICLES = [
    ("comment-choisir-hebergement-application", "Comment choisir l'hébergement de son application ?", "guides",
     "Mutualisé, VPS, conteneurs, plateforme administrée : les critères pour faire le bon choix."),
    ("ia-pme-par-ou-commencer", "Intelligence artificielle en PME : par où commencer ?", "ia",
     "Identifier un premier cas d'usage utile, mesurer son intérêt et maîtriser les risques."),
    ("sauvegardes-regle-3-2-1", "Sauvegardes : comprendre la règle 3-2-1", "cloud",
     "Trois copies, deux supports, une copie hors site : une règle simple pour protéger vos données."),
]

BODY = """<p>Article d'exemple destiné à illustrer la mise en page du blog. Remplacez-le par vos propres contenus.</p>
<h2>Le contexte</h2><p>Chaque organisation a des contraintes propres : budget, compétences internes, qualité de la connexion, exigences réglementaires.</p>
<h2>Les critères à examiner</h2><ul><li>La disponibilité attendue et le coût d'une interruption.</li><li>La localisation et la protection des données.</li><li>La capacité à évoluer avec votre activité.</li><li>La qualité de l'accompagnement.</li></ul>
<blockquote>Le bon choix est celui que votre équipe saura exploiter au quotidien.</blockquote>
<h2>En résumé</h2><p>Commencez petit, mesurez, puis faites évoluer votre solution.</p>"""


class Command(BaseCommand):
    help = "Crée des données d'EXEMPLE pour le développement (jamais en production)."

    def add_arguments(self, parser):
        parser.add_argument("--with-users", action="store_true", help="Crée un compte de démonstration par rôle (mots de passe aléatoires affichés).")

    @transaction.atomic
    def handle(self, *args, with_users=False, **options):
        if not settings.DEBUG and not getattr(settings, "ALLOW_DEMO_SEED", False):
            raise CommandError("seed_demo est réservé au développement (DEBUG=True).")
        call_command("seed_site", stdout=self.stdout)
        cats = {c.slug: c for c in ServiceCategory.objects.all()}

        for order, (cat, slug, name, icon, tagline, summary, status, features) in enumerate(PRODUCTS):
            product, created = Product.objects.get_or_create(slug=slug, defaults={
                "category": cats[cat], "name": name, "icon": icon, "tagline": tagline, "summary": summary, "status": status,
                "is_example": True, "is_published": True, "is_featured": order < 3, "order": order * 10,
                "benefits": "Prise en main rapide\nAccessible depuis un navigateur\nSupport en français",
                "audience": "PME, associations et institutions (exemple)",
                "description": "<p>Fiche d'exemple : décrivez ici la solution réelle, son fonctionnement et ses bénéfices.</p>",
            })
            if created:
                for f_order, (f_icon, title, desc) in enumerate(features):
                    ProductFeature.objects.create(product=product, icon=f_icon, title=title, description=desc, order=f_order)

        self._pricing(cats)
        self._articles()
        self._case_study(cats)
        self._crm(cats)
        JobOpening.objects.get_or_create(slug="developpeur-django-exemple", defaults={
            "title": "Développeur·se Django (offre d'exemple)", "department": "Ingénierie", "summary": "Offre fictive pour illustrer la page Carrières.",
            "description": "<p>Offre d'exemple. Missions, profil recherché et processus de recrutement à compléter.</p>", "remote_possible": True,
        })
        StatusComponent.objects.get_or_create(name="Site institutionnel (exemple)", defaults={"description": "Composant d'exemple pour la page statut."})
        if with_users:
            self._users()
        self.stdout.write(self.style.SUCCESS("Données d'exemple créées."))

    def _pricing(self, cats):
        group, created = PricingGroup.objects.get_or_create(slug="hebergement-exemple", defaults={
            "name": "Hébergement d'applications (exemple)", "category": cats["cloud-computing"],
            "description": "Grille d'exemple : les montants sont fictifs et doivent être remplacés avant publication.",
        })
        if not created:
            return
        plans = [
            ("Essentiel", "Pour un site ou une petite application.", Decimal("250000"), Decimal("2500000"), False, [("Applications", "1"), ("Stockage", "10 Go"), ("Sauvegardes", "Hebdomadaires"), ("Support", "Email")]),
            ("Pro", "Pour les applications métier.", Decimal("650000"), Decimal("6500000"), True, [("Applications", "5"), ("Stockage", "50 Go"), ("Sauvegardes", "Quotidiennes"), ("Support", "Email et téléphone")]),
            ("Entreprise", "Infrastructure dédiée et accompagnement.", None, None, False, [("Applications", "Sur mesure"), ("Stockage", "Sur mesure"), ("Sauvegardes", "Sur mesure"), ("Support", "Interlocuteur dédié")]),
        ]
        for order, (name, desc, monthly, yearly, highlighted, features) in enumerate(plans):
            plan = PricingPlan.objects.create(group=group, name=name, description=desc, price_monthly=monthly, price_yearly=yearly,
                                              price_on_request=monthly is None, is_highlighted=highlighted, order=order, price_note="Montant d'exemple, HT")
            for f_order, (label, value) in enumerate(features):
                PricingFeature.objects.create(plan=plan, label=label, value=value, order=f_order)
        PricingOption.objects.create(group=group, name="Nom de domaine", description="Enregistrement et renouvellement (exemple)", price_label="Sur devis")

    def _articles(self):
        author = get_user_model().objects.filter(is_superuser=True).first()
        tag, _ = BlogTag.objects.get_or_create(slug="exemple", defaults={"name": "Exemple"})
        for i, (slug, title, cat, excerpt) in enumerate(ARTICLES):
            article, created = BlogArticle.objects.get_or_create(slug=slug, defaults={
                "title": title, "excerpt": excerpt, "body": BODY, "category": BlogCategory.objects.filter(slug=cat).first(),
                "author": author, "status": PublishStatus.PUBLISHED, "published_at": timezone.now() - timedelta(days=4 * (i + 1)),
            })
            if created:
                article.tags.add(tag)
        BlogArticle.objects.get_or_create(slug="article-programme-exemple", defaults={
            "title": "Article programmé (exemple)", "excerpt": "Illustration de la publication différée.", "body": BODY,
            "status": PublishStatus.PUBLISHED, "published_at": timezone.now() + timedelta(days=7), "author": author,
        })

    def _case_study(self, cats):
        case, created = CaseStudy.objects.get_or_create(slug="digitalisation-cooperative-exemple", defaults={
            "title": "Digitalisation d'une coopérative agricole", "sector": "Agriculture", "is_example": True, "status": PublishStatus.PUBLISHED,
            "summary": "Exemple illustratif : suivi des collectes et paiements des producteurs via une application mobile hors-ligne.",
            "challenge": "<p>Exemple : les collectes étaient enregistrées sur papier, ce qui retardait les paiements et compliquait les contrôles.</p>",
            "solution": "<p>Exemple : application mobile utilisable sans connexion, synchronisée dès que le réseau est disponible, et tableau de bord web.</p>",
            "results": "<p>Section à remplir uniquement avec des résultats mesurés et validés par le client.</p>",
            "technologies": "Django, PostgreSQL, Flutter, Docker",
        })
        if created:
            case.services.add(cats["developpement-sur-mesure"])

    def _crm(self, cats):
        if Lead.objects.filter(email__endswith="@example.com").exists():
            return
        now = timezone.now()
        samples = [
            ("Aïssatou", "Exemple", "Entreprise Exemple SARL", Lead.Status.NEW, Inquiry.Kind.QUOTE, "Migration de notre site vers un hébergement administré", "cloud-computing"),
            ("Mamadou", "Démo", "Association Démo", Lead.Status.QUALIFIED, Inquiry.Kind.DEMO, "Démonstration de l'outil RH", "saas"),
            ("Fatoumata", "Test", "Institution Test", Lead.Status.PROPOSAL, Inquiry.Kind.CONTACT, "Projet d'assistant documentaire", "intelligence-artificielle"),
            ("Ibrahima", "Fictif", "Commerce Fictif", Lead.Status.WON, Inquiry.Kind.QUOTE, "Application de gestion des ventes", "developpement-sur-mesure"),
        ]
        for i, (first, last, org, status, kind, subject, cat) in enumerate(samples):
            email = f"{first.lower().replace('ï', 'i')}.{last.lower().replace('é', 'e')}@example.com"
            lead = Lead.objects.create(first_name=first, last_name=last, email=email, organization=org, status=status, interest=cats[cat],
                                       priority=Lead.Priority.HIGH if i == 0 else Lead.Priority.NORMAL, notes="[Démo] Donnée de démonstration.",
                                       next_follow_up_at=now + timedelta(days=i - 1) if status not in (Lead.Status.WON,) else None,
                                       follow_up_note="Rappeler pour qualifier le besoin" if i < 3 else "",
                                       is_client=status == Lead.Status.WON, converted_at=now - timedelta(days=2) if status == Lead.Status.WON else None)
            inquiry = Inquiry.objects.create(kind=kind, first_name=first, last_name=last, organization=org, email=email, subject=subject,
                                             message="[Démo] Message de démonstration généré par seed_demo.", category=cats[cat], consent=True,
                                             consent_at=now, lead=lead, status=Inquiry.Status.NEW if i == 0 else Inquiry.Status.IN_PROGRESS)
            Activity.objects.create(lead=lead, inquiry=inquiry, kind=Activity.Kind.INQUIRY, content=f"Demande reçue : « {subject} » ({inquiry.reference}).", created_at=now - timedelta(days=i))

    def _users(self):
        User = get_user_model()
        for role in [Role.ADMIN, Role.EDITOR, Role.SALES, Role.SUPPORT, Role.VIEWER]:
            email = f"{role}@example.com"
            if User.objects.filter(email=email).exists():
                continue
            password = secrets.token_urlsafe(14)
            User.objects.create_user(email=email, password=password, role=role, is_staff=True, first_name=role.label.split()[0])
            self.stdout.write(f"  {role.label:<22} {email}  mot de passe : {password}")

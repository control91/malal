"""
Institutional base content (safe for production): settings, domains, services,
pages and their sections, navigation, FAQ, legal page skeletons.

Idempotent: existing objects are left untouched unless --force is given, so
editors' changes are never overwritten by accident.

    python manage.py seed_site [--force]
"""

from django.core.management.base import BaseCommand
from django.db import transaction

from apps.blog.models import BlogCategory
from apps.cms.models import FAQ, NavigationItem, NavigationMenu, Page, PageSection, SectionItem
from apps.core.models import Availability, PublishStatus, SiteSettings
from apps.services.models import Service, ServiceCategory

A, B, S = Availability.AVAILABLE, Availability.BETA, Availability.COMING_SOON

DOMAINS = [
    {
        "slug": "cloud-computing", "name": "Cloud Computing", "icon": "cloud", "show_hosting_disclosure": True,
        "tagline": "Hébergez vos applications sur une infrastructure administrée et sécurisée.",
        "summary": "Hébergement d'applications web, conteneurs Docker, bases de données et sauvegardes, administrés par notre équipe.",
        "hero_title": "Un cloud *administré* pour vos applications",
        "hero_subtitle": "Déployez vos sites, API et applications métier sans gérer les serveurs : nous nous occupons de l'installation, des mises à jour, des sauvegardes et de la supervision.",
        "services": [
            ("hebergement-web", "Hébergement d'applications web", "app-window", A, "Sites et applications web hébergés sur des serveurs administrés.", "Certificats TLS automatiques\nNom de domaine personnalisé\nAccompagnement à la mise en ligne"),
            ("hebergement-docker", "Hébergement Docker", "container", A, "Déploiement de vos images et stacks Docker Compose.", "Déploiement depuis Git ou registre\nVariables d'environnement gérées\nJournaux d'exécution"),
            ("frameworks", "Hébergement Laravel, Django et Node.js", "code-xml", A, "Environnements prêts à l'emploi pour les frameworks les plus utilisés.", "Configuration optimisée par framework\nTâches planifiées et files d'attente\nAccès SSH sur demande"),
            ("bases-de-donnees", "Bases de données managées", "database", B, "PostgreSQL et MySQL administrés, avec sauvegardes.", "Mises à jour gérées\nAccès restreint par réseau\nOuverture progressive aux clients"),
            ("sauvegardes", "Sauvegardes", "database-backup", A, "Sauvegardes régulières de vos données et procédures de restauration.", "Fréquence adaptée à votre activité\nRestauration accompagnée"),
            ("serveurs-administres", "Serveurs administrés", "server-cog", A, "Nous administrons vos serveurs : sécurité, mises à jour, supervision.", "Durcissement de la sécurité\nMises à jour planifiées\nRapport d'intervention"),
            ("vps", "Serveurs virtuels (VPS)", "server", S, "Des machines virtuelles à la demande, facturées simplement.", ""),
            ("infrastructure-locale", "Infrastructure en Guinée", "landmark", S, "Une infrastructure physiquement hébergée en Guinée fait partie de notre feuille de route.", ""),
        ],
    },
    {
        "slug": "saas", "name": "Applications SaaS", "icon": "layout-dashboard",
        "tagline": "Des logiciels en ligne prêts à l'emploi, pensés pour les organisations africaines.",
        "summary": "Logiciels accessibles depuis un navigateur, sans installation, avec un accompagnement en français.",
        "hero_title": "Des logiciels *SaaS* conçus pour vos réalités",
        "hero_subtitle": "Des applications en ligne simples à adopter, utilisables sur des connexions modestes, avec un support de proximité.",
        "services": [
            ("abonnements", "Logiciels par abonnement", "app-window", A, "Accès en ligne, mises à jour incluses, sans infrastructure à gérer.", ""),
            ("parametrage", "Paramétrage et formation", "graduation-cap", A, "Mise en route, import de vos données et formation de vos équipes.", ""),
            ("integrations", "Intégrations", "plug", B, "Connexion de nos logiciels à vos outils existants (API, exports).", ""),
        ],
    },
    {
        "slug": "intelligence-artificielle", "name": "Intelligence Artificielle", "icon": "brain-circuit", "demo_space_enabled": True,
        "tagline": "Mettre l'IA au service de vos processus, de façon concrète et maîtrisée.",
        "summary": "Assistants, chatbots métier, analyse de documents et automatisation, intégrés à vos outils.",
        "hero_title": "Une *IA utile*, intégrée à votre activité",
        "hero_subtitle": "Nous concevons des assistants et automatisations qui répondent à des besoins précis, avec une attention particulière à la confidentialité de vos données.",
        "services": [
            ("assistants", "Assistants IA pour entreprises", "bot", B, "Assistants internes qui répondent à partir de vos documents.", ""),
            ("chatbots", "Chatbots métier", "messages-square", B, "Agents conversationnels pour le service client (web, WhatsApp).", ""),
            ("automatisation", "Automatisation des processus", "workflow", A, "Automatisation de tâches répétitives : saisie, tri, notifications.", ""),
            ("documents", "Analyse intelligente de documents", "scan-text", B, "Extraction d'informations de factures, contrats, formulaires.", ""),
            ("ia-erp", "IA intégrée aux ERP", "blocks", S, "Fonctions d'IA directement dans vos outils de gestion.", ""),
            ("predictif", "Analyse prédictive", "chart-line", S, "Prévisions à partir de vos données historiques.", ""),
            ("api-ia", "Services API IA", "webhook", S, "Des API d'IA pour les développeurs africains.", ""),
        ],
    },
    {
        "slug": "developpement-sur-mesure", "name": "Développement sur mesure", "icon": "code",
        "tagline": "Des logiciels construits autour de vos processus.",
        "summary": "Applications web et mobiles, ERP, CRM, API et intégration de systèmes.",
        "hero_title": "Votre logiciel, *sur mesure*",
        "hero_subtitle": "De l'idée à la mise en production : cadrage, conception, développement, déploiement et maintenance.",
        "services": [
            ("applications-web", "Applications web", "monitor", A, "Plateformes, portails et outils métier accessibles en ligne.", ""),
            ("applications-mobiles", "Applications mobiles", "smartphone", A, "Applications Android et iOS, y compris en mode hors-ligne.", ""),
            ("erp-crm", "ERP et CRM", "layout-grid", A, "Outils de gestion adaptés : ventes, stocks, RH, relation client.", ""),
            ("api", "Développement d'API", "plug-zap", A, "API documentées et sécurisées pour connecter vos services.", ""),
            ("integration", "Intégration de systèmes", "network", A, "Connexion de vos logiciels, paiements mobiles et services tiers.", ""),
            ("maintenance", "Maintenance applicative", "life-buoy", A, "Correctifs, évolutions et suivi de vos applications.", ""),
        ],
    },
    {
        "slug": "transformation-numerique", "name": "Transformation numérique", "icon": "rocket",
        "tagline": "Structurer et accélérer la digitalisation de votre organisation.",
        "summary": "Diagnostic, feuille de route et accompagnement au changement pour passer au numérique.",
        "services": [
            ("diagnostic", "Diagnostic numérique", "radar", A, "État des lieux de vos outils, processus et besoins.", ""),
            ("feuille-de-route", "Feuille de route", "map", A, "Priorisation des chantiers et estimation des moyens.", ""),
            ("conduite-changement", "Conduite du changement", "users", A, "Formation et accompagnement des équipes.", ""),
        ],
    },
    {
        "slug": "conseil-integration", "name": "Conseil et intégration", "icon": "compass",
        "tagline": "Des choix technologiques éclairés et durables.",
        "summary": "Architecture, sécurité, choix de solutions et accompagnement de vos projets informatiques.",
        "services": [
            ("architecture", "Conseil en architecture", "layers", A, "Conception d'architectures évolutives et sécurisées.", ""),
            ("securite", "Sécurité des systèmes", "shield-check", A, "Revue de sécurité et recommandations priorisées.", ""),
            ("choix-solutions", "Choix de solutions", "list-checks", A, "Comparaison objective des solutions du marché.", ""),
        ],
    },
]

HOME_SECTIONS = [
    {"kind": "hero", "theme": "dark", "eyebrow": "Cloud · SaaS · Intelligence Artificielle — depuis Conakry",
     "title": "Construisons l'avenir numérique de *l'Afrique*.",
     "subtitle": "Des solutions Cloud, des logiciels SaaS et des technologies d'Intelligence Artificielle conçus pour accélérer la transformation numérique des entreprises africaines.",
     "cta_label": "Découvrir nos solutions", "cta_url": "/solutions/", "cta2_label": "Contacter un expert", "cta2_url": "/contact/",
     "items": [("map-pin", "Équipe basée à Conakry"), ("languages", "Accompagnement en français"), ("shield-check", "Sécurité dès la conception"), ("receipt", "Offres transparentes")]},
    {"kind": "marquee", "limit": 12},
    {"kind": "domains", "eyebrow": "Nos domaines d'expertise", "title": "Une expertise complète, de l'infrastructure à l'IA",
     "subtitle": "Six domaines complémentaires pour accompagner chaque étape de votre transformation numérique."},
    {"kind": "solutions", "theme": "muted", "eyebrow": "Nos solutions", "title": "Des solutions prêtes à déployer",
     "subtitle": "Chaque solution affiche clairement son statut : disponible, en bêta ou à venir.", "cta_label": "Voir le catalogue", "cta_url": "/solutions/"},
    {"kind": "features", "eyebrow": "Pourquoi nous choisir ?", "title": "Un partenaire technologique *de proximité*",
     "subtitle": "Nous construisons une relation durable, fondée sur la transparence et l'exigence technique.", "cta_label": "Découvrir l'entreprise", "cta_url": "/entreprise/",
     "items": [
         ("heart-handshake", "Accompagnement de proximité", "Une équipe joignable, qui comprend votre contexte et parle votre langue."),
         ("code-xml", "Expertise technique", "Des pratiques d'ingénierie modernes : tests, revue de code, déploiements automatisés."),
         ("trending-up", "Solutions évolutives", "Des architectures pensées pour grandir avec votre activité."),
         ("receipt", "Transparence des offres", "Des prix lisibles, des engagements clairs et aucun coût caché."),
         ("shield-check", "Sécurité intégrée", "La sécurité est prise en compte dès la conception, pas après coup."),
         ("globe-2", "Compréhension des besoins africains", "Connexions modestes, paiements mobiles, multilinguisme : nous en tenons compte."),
     ]},
    {"kind": "vision", "theme": "dark", "eyebrow": "Notre vision pour l'Afrique", "title": "De la Guinée au *continent*",
     "subtitle": "Une ambition construite étape par étape, en commençant par servir pleinement nos clients en Guinée.",
     "items": [
         ("map-pin", "Guinée", "Servir les entreprises et institutions guinéennes avec des services cloud, SaaS et IA fiables.", "Aujourd'hui", "in_progress"),
         ("network", "Afrique de l'Ouest", "Étendre nos services aux pays voisins et nouer des partenariats régionaux.", "Prochaine étape", "planned"),
         ("globe", "Continent africain", "Contribuer à une offre numérique africaine, compétitive et souveraine.", "Ambition", "planned"),
     ]},
    {"kind": "news", "eyebrow": "Actualités", "title": "Nos dernières publications", "limit": 3},
    {"kind": "cta", "eyebrow": "Parlons de votre projet", "title": "Prêt à accélérer votre *transformation numérique* ?",
     "subtitle": "Décrivez votre besoin : nous vous proposons une solution adaptée et un devis transparent.",
     "cta_label": "Demander un devis", "cta_url": "/devis/", "cta2_label": "Nous contacter", "cta2_url": "/contact/"},
]

ABOUT_SECTIONS = [
    {"kind": "text", "eyebrow": "Notre histoire", "title": "Une entreprise technologique née en Guinée",
     "body": "<p>[À compléter] Racontez ici la création de l'entreprise : l'année, les fondateurs, le constat de départ et les premières réalisations.</p><p>Nous sommes convaincus que les organisations africaines méritent des services numériques fiables, abordables et conçus pour leurs réalités.</p>"},
    {"kind": "features", "theme": "muted", "eyebrow": "Mission, vision, valeurs", "title": "Ce qui nous guide",
     "items": [
         ("target", "Mission", "Rendre le cloud, le logiciel et l'IA accessibles et utiles aux organisations africaines."),
         ("compass", "Vision", "Devenir un acteur de référence du numérique en Afrique de l'Ouest, puis sur le continent."),
         ("scale", "Intégrité", "Dire ce que nous faisons, faire ce que nous disons — y compris sur nos limites actuelles."),
         ("award", "Exigence", "Viser la qualité internationale dans chaque ligne de code et chaque échange."),
         ("handshake", "Proximité", "Être disponibles, à l'écoute et engagés dans la réussite de nos clients."),
         ("lightbulb", "Innovation utile", "Choisir les technologies pour l'impact qu'elles apportent, pas pour la mode."),
     ]},
    {"kind": "split", "eyebrow": "Notre engagement", "title": "Contribuer à la transformation numérique africaine",
     "body": "<p>Nous formons des talents locaux, privilégions des solutions adaptées aux connexions modestes et documentons nos choix techniques. Nous travaillons à rapprocher progressivement l'hébergement des données de nos clients.</p>"},
    {"kind": "team", "eyebrow": "Équipe", "title": "Les femmes et les hommes derrière nos solutions", "limit": 12},
    {"kind": "roadmap", "theme": "muted", "eyebrow": "Feuille de route publique", "title": "Où nous en sommes, où nous allons",
     "items": [
         ("rocket", "Lancement des services de développement sur mesure", "Premiers projets d'applications web et mobiles.", "Étape 1", "in_progress"),
         ("cloud", "Hébergement administré", "Hébergement d'applications et de conteneurs sur une infrastructure louée auprès de fournisseurs établis.", "Étape 2", "in_progress"),
         ("layout-dashboard", "Premières solutions SaaS", "Ouverture de logiciels en ligne pour les PME.", "Étape 3", "in_progress"),
         ("brain-circuit", "Assistants IA et automatisation", "Prototypes puis offres d'IA appliquée aux entreprises.", "Étape 4", "planned"),
         ("landmark", "Infrastructure locale", "Étude puis déploiement d'une infrastructure hébergée en Guinée.", "Étape 5", "planned"),
     ]},
    {"kind": "partners", "eyebrow": "Partenaires", "title": "Ils nous accompagnent"},
    {"kind": "cta", "title": "Envie de construire avec nous ?", "subtitle": "Clients, partenaires ou talents : parlons-en.",
     "cta_label": "Nous contacter", "cta_url": "/contact/", "cta2_label": "Voir les carrières", "cta2_url": "/carrieres/"},
]

LEGAL_PAGES = {
    "mentions-legales": ("Mentions légales", "<h2>Éditeur du site</h2><p>[Raison sociale], [forme juridique] au capital de [montant] — RCCM [numéro] — NIF [numéro].<br>Siège social : [adresse], Conakry, Guinée.<br>Directeur de la publication : [nom].</p><h2>Hébergement</h2><p>[Nom de l'hébergeur, adresse, pays].</p><h2>Propriété intellectuelle</h2><p>L'ensemble des contenus de ce site est protégé. Toute reproduction sans autorisation est interdite.</p>"),
    "confidentialite": ("Politique de confidentialité", "<h2>Données collectées</h2><p>Lorsque vous utilisez nos formulaires, nous collectons les informations que vous saisissez (nom, organisation, email, téléphone, message) ainsi que l'adresse IP utilisée, à des fins de lutte contre les abus.</p><h2>Finalités</h2><p>Ces données servent uniquement à traiter votre demande et à assurer le suivi commercial correspondant. Elles ne sont ni vendues ni cédées.</p><h2>Mesure d'audience</h2><p>Nous mesurons la fréquentation sans cookie ni service tiers, à l'aide de compteurs agrégés. Les signaux « Do Not Track » et « Global Privacy Control » sont respectés.</p><h2>Durée de conservation</h2><p>[À préciser, par exemple 3 ans après le dernier contact.]</p><h2>Vos droits</h2><p>Vous pouvez demander l'accès, la rectification ou la suppression de vos données en écrivant à [email].</p>"),
    "conditions-generales": ("Conditions générales", "<p>[À compléter avec votre conseil juridique] Conditions générales de vente et d'utilisation des services : objet, commande, prix, paiement, niveaux de service, responsabilité, résiliation, droit applicable.</p>"),
    "ressources": ("Documentation et ressources", "<p>Retrouvez ici nos guides, fiches techniques et documentations. Les documents sont ajoutés progressivement depuis la médiathèque.</p><ul><li><a href=\"/blog/\">Articles et guides</a></li><li><a href=\"/faq/\">Questions fréquentes</a></li><li><a href=\"/statut/\">Statut des services</a></li></ul>"),
}

FAQS = [
    (None, "Où sont hébergées les données de vos clients ?", "<p>Aujourd'hui, nos services d'hébergement reposent sur des infrastructures louées auprès de fournisseurs cloud établis, situées hors de Guinée. Nous indiquons la localisation précise dans chaque proposition commerciale. Une infrastructure locale fait partie de notre feuille de route.</p>"),
    (None, "Travaillez-vous uniquement en Guinée ?", "<p>Nous servons en priorité les organisations basées en Guinée, et accompagnons déjà des projets à distance. L'extension à l'Afrique de l'Ouest est notre prochaine étape.</p>"),
    (None, "Comment obtenir un devis ?", "<p>Remplissez le <a href=\"/devis/\">formulaire de devis</a> en décrivant votre besoin. Nous revenons vers vous pour préciser le périmètre avant d'établir une proposition.</p>"),
    ("cloud-computing", "Que signifie « bêta » pour un service ?", "<p>Un service en bêta est fonctionnel mais ouvert à un nombre limité de clients. Ses caractéristiques peuvent évoluer et il ne bénéficie pas encore des mêmes engagements qu'un service disponible.</p>"),
    ("cloud-computing", "Puis-je migrer mon site existant ?", "<p>Oui, nous accompagnons la migration : analyse, copie des données, tests puis bascule du nom de domaine.</p>"),
    ("intelligence-artificielle", "Mes données servent-elles à entraîner des modèles ?", "<p>Non, sauf accord explicite et écrit. Nous définissons avec vous les données utilisées et leur traitement avant tout projet.</p>"),
]

MENUS = {
    "header": ("", [
        ("Solutions", "/solutions/", "", "", [("Cloud Computing", "/expertises/cloud-computing/", "Hébergement et infrastructure administrés", "cloud"),
                                               ("Applications SaaS", "/expertises/saas/", "Logiciels en ligne prêts à l'emploi", "layout-dashboard"),
                                               ("Intelligence Artificielle", "/expertises/intelligence-artificielle/", "Assistants, chatbots, automatisation", "brain-circuit"),
                                               ("Développement sur mesure", "/expertises/developpement-sur-mesure/", "Web, mobile, ERP, API", "code"),
                                               ("Transformation numérique", "/expertises/transformation-numerique/", "Diagnostic et feuille de route", "rocket"),
                                               ("Catalogue complet", "/solutions/", "Toutes nos solutions", "layout-grid")]),
        ("Tarifs", "/tarifs/", "", "", []),
        ("Réalisations", "/realisations/", "", "", []),
        ("Ressources", "/blog/", "", "", []),
        ("Entreprise", "/entreprise/", "", "", []),
        ("Contacter un expert", "/contact/", "", "button", []),
    ]),
    "footer_solutions": ("Solutions", [("Cloud Computing", "/expertises/cloud-computing/"), ("Applications SaaS", "/expertises/saas/"), ("Intelligence Artificielle", "/expertises/intelligence-artificielle/"), ("Développement sur mesure", "/expertises/developpement-sur-mesure/"), ("Tarifs", "/tarifs/")]),
    "footer_company": ("Entreprise", [("À propos", "/entreprise/"), ("Réalisations", "/realisations/"), ("Partenaires", "/partenaires/"), ("Carrières", "/carrieres/"), ("Contact", "/contact/")]),
    "footer_resources": ("Ressources", [("Blog", "/blog/"), ("Documentation", "/ressources/"), ("FAQ", "/faq/"), ("Statut des services", "/statut/"), ("Demander une démo", "/demo/")]),
    "footer_legal": ("Légal", [("Mentions légales", "/mentions-legales/"), ("Confidentialité", "/confidentialite/"), ("Conditions générales", "/conditions-generales/")]),
}


class Command(BaseCommand):
    help = "Crée le contenu institutionnel de base (idempotent)."

    def add_arguments(self, parser):
        parser.add_argument("--force", action="store_true", help="Réécrit les contenus existants créés par cette commande.")

    @transaction.atomic
    def handle(self, *args, force=False, **options):
        self.force = force
        site = SiteSettings.load()
        if not site.meta_description:
            site.meta_description = "Cloud, logiciels SaaS et Intelligence Artificielle pour les entreprises africaines. Basés à Conakry, Guinée."
            site.footer_text = "Cloud, SaaS et Intelligence Artificielle pour accélérer la transformation numérique des organisations africaines."
            site.save()

        for order, data in enumerate(DOMAINS):
            data = dict(data)
            services = data.pop("services")
            category = self._upsert(ServiceCategory, {"slug": data["slug"]}, {**data, "order": order * 10})
            for s_order, (slug, name, icon, status, summary, details) in enumerate(services):
                self._upsert(Service, {"category": category, "slug": slug},
                             {"name": name, "icon": icon, "status": status, "summary": summary, "details": details, "order": s_order * 10})

        self._page("accueil", "Accueil", HOME_SECTIONS, template=Page.Template.HOME, is_system=True)
        self._page("entreprise", "Entreprise", ABOUT_SECTIONS, is_system=True, eyebrow="À propos",
                   intro="Une entreprise technologique guinéenne qui construit des services Cloud, SaaS et IA pour l'Afrique.")
        for slug, (title, body) in LEGAL_PAGES.items():
            template = Page.Template.STANDARD if slug == "ressources" else Page.Template.LEGAL
            self._upsert(Page, {"slug": slug}, {"title": title, "body": body, "template": template, "status": PublishStatus.PUBLISHED, "is_system": True})

        for order, (cat_slug, question, answer) in enumerate(FAQS):
            category = ServiceCategory.objects.filter(slug=cat_slug).first() if cat_slug else None
            self._upsert(FAQ, {"question": question}, {"answer": answer, "category": category, "order": order * 10})

        for order, (slug, name) in enumerate([("actualites", "Actualités"), ("guides", "Guides"), ("cloud", "Cloud"), ("ia", "Intelligence Artificielle")]):
            self._upsert(BlogCategory, {"slug": slug}, {"name": name, "order": order})

        self._menus()
        self.stdout.write(self.style.SUCCESS("Contenu institutionnel prêt."))

    # -- helpers ------------------------------------------------------------
    def _upsert(self, model, lookup, values):
        values = {k: v for k, v in values.items() if k not in lookup}
        obj = model.objects.filter(**lookup).first()
        if obj is None:
            return model.objects.create(**lookup, **values)
        if self.force:
            for key, value in values.items():
                setattr(obj, key, value)
            obj.save()
        return obj

    def _page(self, slug, title, sections, **extra):
        existed = Page.objects.filter(slug=slug).exists()
        page = self._upsert(Page, {"slug": slug}, {"title": title, "status": PublishStatus.PUBLISHED, **extra})
        if existed and not self.force:
            return
        page.sections.all().delete()
        for order, data in enumerate(sections):
            data = dict(data)
            items = data.pop("items", [])
            section = PageSection.objects.create(page=page, order=order * 10, **data)
            for i_order, item in enumerate(items):
                icon, title_ = item[0], item[1]
                text = item[2] if len(item) > 2 else ""
                badge = item[3] if len(item) > 3 else ""
                progress = item[4] if len(item) > 4 else ""
                SectionItem.objects.create(section=section, icon=icon, title=title_, text=text, badge=badge, progress=progress, order=i_order * 10)

    def _menus(self):
        for key, (title, items) in MENUS.items():
            menu, created = NavigationMenu.objects.get_or_create(key=key, defaults={"title": title})
            if not created and not self.force:
                continue
            menu.title = title
            menu.save()
            menu.items.all().delete()
            for order, entry in enumerate(items):
                if key == "header":
                    label, url, description, style, children = entry
                    parent = NavigationItem.objects.create(menu=menu, label=label, url=url, description=description,
                                                           style=style or NavigationItem.Style.LINK, order=order * 10)
                    for c_order, (c_label, c_url, c_desc, c_icon) in enumerate(children):
                        NavigationItem.objects.create(menu=menu, parent=parent, label=c_label, url=c_url, description=c_desc, icon=c_icon, order=c_order * 10)
                else:
                    label, url = entry
                    NavigationItem.objects.create(menu=menu, label=label, url=url, order=order * 10)

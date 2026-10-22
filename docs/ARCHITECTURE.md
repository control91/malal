# Architecture technique

## Découpage

```
config/            settings (base/dev/test/prod), urls, celery, wsgi
apps/
  core/            SiteSettings (singleton), modèles abstraits, champs (image optimisée, texte riche assaini),
                   validateurs d'upload, anti-spam, SEO/JSON-LD, sitemaps, middlewares, commandes
  accounts/        User (connexion par email) + rôles/capacités (roles.py)
  audit/           AuditLog + signaux de connexion + services.record()
  cms/             Page, PageSection, SectionItem, NavigationMenu/Item, FAQ, TeamMember, Partner, JobOpening, MediaAsset
  services/        ServiceCategory (domaines), Service, StatusComponent
  products/        Product, ProductFeature, ProductScreenshot
  pricing/         PricingGroup, PricingPlan, PricingFeature, PricingOption
  blog/            BlogArticle (publication différée), BlogCategory, BlogTag
  portfolio/       CaseStudy (+ galerie), Testimonial — publication conditionnée au consentement
  crm/             Inquiry (contact/devis/démo/…), Lead, Activity, NewsletterSubscriber + formulaires publics
  notifications/   Notification in-app, tâche Celery d'envoi d'email
  analytics/       compteurs journaliers sans cookie (middleware)
  dashboard/       back-office : moteur CRUD déclaratif (crud.py), ressources (resources.py), écrans CRM
  api/             API REST publique v1 en lecture seule + schéma OpenAPI (/api/schema/)
templates/public | dashboard | accounts | emails | errors
assets/css/main.css   design system Tailwind (tokens de marque = variables CSS)
```

### Simplifications assumées

- `ContactSubmission`, `DemoRequest` et `QuoteRequest` sont un seul modèle **`Inquiry`** avec un
  champ `kind` : une seule boîte de réception, un seul export, un seul workflow.
- `LeadActivity` est **`Activity`**, partagée par les prospects et les demandes (historique unifié).
- Les apps `leads` et `crm` suggérées sont fusionnées dans `crm`.

## Back-office

`apps/dashboard/crud.py` génère liste (recherche, filtres, tri, pagination htmx), création,
édition (avec formulaires imbriqués : fonctionnalités, éléments de section, caractéristiques…),
suppression (modale de confirmation), publication en un clic et export CSV (protégé contre
l'injection de formules) à partir d'une déclaration `Resource`. Toute écriture est enregistrée
dans l'audit avec les champs modifiés. Ajouter un modèle au back-office = déclarer une `Resource`.

## Contrôle d'accès

`apps/accounts/roles.py` associe chaque rôle à des capacités `<zone>.view` / `<zone>.edit`.
Chaque vue déclare sa capacité (`capability_required`) ; les formulaires en lecture seule sont
désactivés côté serveur et tout POST sans `edit` renvoie 403. Le menu latéral n'est qu'un reflet.
Tests : `tests/test_dashboard.py::test_role_access_matrix`.

## Sécurité

| Mesure | Implémentation |
| --- | --- |
| CSRF, XSS | protections Django ; HTML riche assaini par `nh3` à l'enregistrement (`RichTextField`) ; JSON-LD échappé |
| CSP | `django-csp` : scripts locaux + nonce uniquement (`'unsafe-eval'` requis par Alpine.js), `frame-ancestors 'none'` |
| En-têtes | X-Frame-Options DENY, nosniff, Referrer-Policy, COOP, HSTS (prod) |
| Connexion | django-axes (5 échecs / 30 min par identifiant + IP), mots de passe ≥ 12 caractères, cookies `__Host-` sécurisés |
| Uploads | extension + taille + contenu réel (Pillow, signature PDF/Office, SVG filtré), images recompressées en WebP sans métadonnées |
| Formulaires publics | pot de miel, horodatage signé (délai minimum), limitation par IP, consentement horodaté |
| Back-office | URL d'admin de secours configurable, liste blanche d'IP optionnelle, audit |
| Secrets | uniquement via variables d'environnement ; la prod refuse une clé faible |

## SEO et performance

URLs propres en français, slugs, meta title/description et image Open Graph par contenu,
canonical, Twitter Cards, JSON-LD (Organization, WebSite, BreadcrumbList, Service,
SoftwareApplication, BlogPosting, FAQPage, JobPosting), `sitemap.xml`, `robots.txt`.
Polices auto-hébergées et préchargées, CSS unique minifié (~140 Ko, ~20 Ko compressé),
JavaScript minimal différé, images WebP redimensionnées avec `loading="lazy"`, illustrations en SVG,
cache des paramètres et menus. Aucun score Lighthouse n'a été mesuré à ce stade.

## Multilingue

- Interface : i18n Django (`LocaleMiddleware`, `i18n_patterns` avec le français sans préfixe ;
  l'anglais sera servi sous `/en/`). Les gabarits utilisent `{% translate %}` progressivement.
- Contenus éditoriaux : **stratégie retenue** — ajouter `django-modeltranslation` sur les
  modèles éditoriaux (champs `title_fr`, `title_en`, …) et un onglet de langue dans les
  formulaires du back-office. Non activé aujourd'hui : le contenu est en français uniquement.
- Langues africaines (pular, malinké, soussou…) : même mécanisme, en ajoutant le code à
  `LANGUAGES` et aux langues actives dans la configuration.

## Évolution vers l'écosystème Cloud

Séparation prévue :

1. **Site institutionnel** (cette application, apps publiques).
2. **Back-office CMS & commercial** (`apps/dashboard`).
3. **Portail client** : nouvelle application (ou service distinct) — abonnements, facturation,
   tickets. Elle réutilisera la table `accounts.User` (un champ de type de compte suffit),
   ou une authentification centralisée.
4. **Services techniques** (CloudStack, Coolify, Africa AI Gateway) : services séparés,
   jamais appelés directement par le site institutionnel.

Recommandations :

- Authentification centralisée : exposer un fournisseur **OpenID Connect**
  (`django-oauth-toolkit` sur ce projet, ou Keycloak/Authentik dédié) pour que le portail et
  les futures applications partagent les identités.
- API : étendre `apps/api` sous `/api/v1/` (versionnée, documentée par le schéma OpenAPI),
  avec des permissions DRF par portée.
- Statut des services : remplacer la saisie manuelle (`StatusComponent`) par une alimentation
  automatique depuis la supervision.

## Limites connues

- Pas de traduction des contenus éditoriaux (voir stratégie ci-dessus) ; seule l'interface est prête.
- Pas de mode sombre : l'identité combine déjà sections claires et sombres ; un thème sombre
  complet n'a pas été implémenté plutôt que livré à moitié.
- Pas de CAPTCHA tiers (choix de confidentialité) ; un service comme Cloudflare Turnstile peut
  être ajouté si le spam devient significatif.
- Les candidatures passent par le formulaire de contact (lien vers CV) : pas d'upload de CV,
  pour éviter de stocker des données personnelles sensibles sans procédure dédiée.
- L'éditeur riche n'accepte pas l'insertion d'images dans le corps des articles.
- La page Statut est mise à jour manuellement.
- Les notifications in-app s'affichent au chargement des pages (pas de temps réel).
- La carte « Vision » est une représentation stylisée, pas une carte géographique précise.

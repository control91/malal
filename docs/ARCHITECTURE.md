# Architecture technique

## Vue d'ensemble

Laravel 12 sert à la fois le site public et le back-office. Les pages sont des composants Vue 3
rendus via Inertia : pas d'API à maintenir entre le front et le back, la navigation est instantanée,
et le site public est rendu côté serveur (SSR) pour le référencement et les connexions modestes.

```
app/
  Admin/            moteur du back-office : Field (schéma + validation), Resource, Repeater, Registry
    Resources/      une classe par contenu administrable (pages, solutions, articles, prospects…)
  Console/Commands/ app:create-admin, app:seed-site, app:seed-demo
  Enums/            statuts et rôles (Availability, PublishStatus, LeadStatus, Role…)
  Http/
    Controllers/Site/   site public (pages, domaines, catalogue, blog, formulaires…)
    Controllers/Admin/  écrans spécifiques (tableau de bord, demandes, CRM, paramètres) + ResourceController générique
    Controllers/Api/V1/ API publique en lecture seule
    Middleware/         Inertia (données partagées), en-têtes de sécurité, statistiques, liste blanche IP
    Requests/           validation des formulaires publics (anti-spam inclus)
  Models/           Eloquent (+ traits Ordered, SanitizesRichText)
  Services/         InquiryService (demande → prospect → notifications), Notifier, SectionLoader, Stats
  Support/          Capabilities (rôles), Audit, Images (WebP), AntiSpam, Seo, Present, Csp/AppPolicy
database/
  migrations/       schéma complet
  seeders/          SiteContentSeeder, DemoSeeder (+ data/*.json)
resources/
  css/app.css       design system Tailwind (thème sombre public scopé sur body.site)
  js/
    Pages/Site|Admin|Auth   pages Inertia
    Components/Sections     un composant par type de section du CMS
    Components/Site|Admin|Ui
    Layouts/                SiteLayout, AdminLayout (chargé à la demande), AuthLayout
    ssr.js / app.js         points d'entrée SSR et navigateur
  views/app.blade.php        gabarit racine : métadonnées SEO et JSON-LD rendus par le serveur
routes/web.php, api.php
tests/Feature/      Pest
```

## Back-office déclaratif

Chaque contenu est décrit par une classe `App\Admin\Resources\*Resource` : colonnes de la liste,
filtres, recherche, champs groupés (`Field::text()->required()->max(200)` produit à la fois le
schéma du formulaire Vue et les règles de validation Laravel), relations éditables en ligne
(`Repeater`), champ de publication, export CSV. `ResourceController` fournit liste, création,
édition, suppression, publication et export pour toutes les ressources, avec audit automatique des
champs modifiés. Ajouter un contenu au back-office = créer une Resource et l'inscrire dans
`App\Admin\Registry`.

## Contrôle d'accès

`App\Support\Capabilities` associe chaque rôle à des capacités `<zone>.view` / `<zone>.edit`.
Un `Gate::before` les résout ; les routes déclarent `can:<capacité>` et le contrôleur générique
vérifie la capacité de la ressource à chaque action. Le menu n'est qu'un reflet de ces droits.

| Rôle | Peut modifier | Peut consulter |
| --- | --- | --- |
| Super administrateur / Administrateur | tout (l'admin ne peut pas toucher aux super administrateurs) | tout |
| Éditeur | contenus, catalogue, articles, réalisations, médias | statistiques |
| Commercial | demandes, prospects, newsletter | catalogue, statistiques |
| Support | demandes | prospects, catalogue |
| Lecteur | rien | tout sauf utilisateurs, configuration, audit |

## Sécurité

| Mesure | Implémentation |
| --- | --- |
| CSRF, XSS | protections Laravel/Inertia ; HTML riche nettoyé par HTMLPurifier à chaque enregistrement ; JSON-LD échappé |
| CSP | `App\Support\Csp\AppPolicy` : scripts locaux + nonce, **sans `unsafe-eval`** (templates Vue compilés au build), `frame-ancestors 'none'` |
| En-têtes | X-Frame-Options, nosniff, Referrer-Policy, COOP, Permissions-Policy, HSTS en HTTPS, X-Request-ID |
| Connexion | 5 échecs par (email, IP) → blocage 30 min ; mots de passe ≥ 12 caractères ; comptes désactivés déconnectés |
| Uploads | type réel vérifié (MIME), taille, SVG filtré ; images redimensionnées et recompressées en WebP |
| Formulaires publics | pot de miel, horodatage chiffré (délai minimum), limitation par IP, consentement horodaté |
| Back-office | liste blanche d'IP optionnelle, audit des opérations sensibles, export CSV protégé |
| Données exposées | les pages ne reçoivent que des tableaux préparés (`Support\Present`), la liste des routes publiques est restreinte |

## SEO et performance

Titre, description, canonical, Open Graph, Twitter Cards et JSON-LD (Organization, WebSite,
BreadcrumbList, Service, SoftwareApplication, BlogPosting, FAQPage, JobPosting) sont rendus par
`app.blade.php`, même sans SSR. `sitemap.xml` et `robots.txt` sont générés. Le rendu serveur
fournit le HTML complet. Le code du back-office et l'éditeur ne sont jamais téléchargés par les
visiteurs (découpage par page, layouts chargés à la demande). Polices auto-hébergées et préchargées,
images WebP avec `loading="lazy"`. Aucun score Lighthouse n'a été mesuré.

## Multilingue

Interface et contenus en français. L'anglais et les langues africaines s'ajouteront par la
traduction des chaînes Vue (vue-i18n ou fichiers `lang/`) et, pour les contenus éditoriaux, par des
colonnes traduites (`spatie/laravel-translatable`) avec un onglet de langue dans les formulaires.

## Évolution vers l'écosystème Cloud

1. **Site institutionnel** — ce dépôt (routes publiques).
2. **Back-office CMS & commercial** — `/admin`.
3. **Portail client** — futur module ou application distincte réutilisant la table `users`
   (un type de compte suffit) ou une authentification centralisée (OIDC via Laravel Passport ou un
   fournisseur dédié comme Keycloak).
4. **Services techniques** (CloudStack, Coolify, Africa AI Gateway) — services séparés, consommés par
   API, jamais par le site institutionnel directement.

L'API publique `/api/v1` (lecture seule, limitée en débit) est le point de départ des intégrations.

## Limites connues

- Contenus en français uniquement (stratégie de traduction ci-dessus).
- Pas de mode sombre pour le back-office (le site public est sombre par conception).
- Pas de CAPTCHA tiers (choix de confidentialité) ; Cloudflare Turnstile peut être ajouté si besoin.
- Candidatures via le formulaire de contact (lien vers CV), sans upload de CV.
- Page Statut mise à jour manuellement ; notifications in-app non temps réel.
- Pas d'administration de secours type Django Admin : tout passe par le back-office.
- La carte « Vision » est une représentation stylisée, pas une carte géographique précise.

# MALAL — Plateforme institutionnelle Cloud, SaaS & IA

Site institutionnel, CMS, catalogue de solutions, tarifs, blog, réalisations, mini-CRM et
back-office sur mesure, pour une entreprise technologique basée à Conakry.

> Le nom « MALAL », le logo et les couleurs se changent dans **Back-office → Configuration**.

## Stack

| Couche | Choix |
| --- | --- |
| Backend | PHP 8.4+, **Laravel 12**, PostgreSQL 17, Redis (cache, sessions, files d'attente) |
| Frontend | **Vue 3** + **Inertia 2** (rendu serveur SSR pour le site public), Vite 7, Tailwind CSS 4, Tiptap (éditeur) |
| Sécurité | CSP stricte (spatie/laravel-csp), HTMLPurifier, limitation des connexions et des formulaires, audit |
| Livraison | Docker multi-stage (PHP-FPM, Nginx, serveur SSR Node), Docker Compose |
| Tests | Pest 3 (75 tests) |

Aucune ressource tierce n'est chargée par le navigateur (pas de CDN, pas de Google Fonts, pas de traceur).

## Démarrage rapide avec Docker

```bash
make up                            # site :8000, back-office :8000/admin, emails :8025
make seed                          # (2e terminal) contenu + données d'exemple + un compte par rôle
make admin email=vous@example.com  # super administrateur (mot de passe demandé)
```

## Démarrage rapide sans Docker

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # puis adapter DB_*, APP_ENV=local, APP_DEBUG=true
createdb malal
php artisan migrate
php artisan app:seed-demo --with-users
php artisan app:create-admin --email=vous@entreprise.gn
composer run dev        # serveur PHP + Vite + file d'attente
```

- Site public : http://localhost:8000
- Back-office : http://localhost:8000/admin

## Tests et qualité

```bash
createdb malal_laravel_test
./vendor/bin/pest           # pages, formulaires, anti-spam, permissions par rôle, audit, uploads, commandes
./vendor/bin/pint --test    # style de code
```

## Documentation

- [docs/INSTALLATION.md](docs/INSTALLATION.md) — installation détaillée, Docker, variables d'environnement
- [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md) — mise en production, HTTPS, sauvegardes, SSR
- [docs/ADMINISTRATION.md](docs/ADMINISTRATION.md) — guide du back-office pour les équipes
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — modules, sécurité, évolutions prévues et limites connues

# Installation

## Option A — Tout dans Docker (recommandé)

Seul **Docker Desktop** est nécessaire.

```bash
make up                                   # = docker compose -f docker-compose.dev.yml up --build
```

La première fois, dans un second terminal :

```bash
make seed                                 # contenu de base + données d'exemple + un compte par rôle
make admin email=vous@example.com         # super administrateur (mot de passe demandé, masqué)
```

| Adresse | Service |
| --- | --- |
| http://localhost:8000 | site public |
| http://localhost:8000/admin | back-office |
| http://localhost:8025 | **Mailpit** : tous les emails envoyés par l'application (aucun ne part réellement) |

Services : `app` (serveur PHP intégré, migrations au démarrage), `worker` (file d'attente des
emails, rechargée à chaque modification), `vite` (rechargement à chaud du front-end), `db`
(PostgreSQL 17), `redis`, `mailpit`. Le code est monté dans les conteneurs : modifiez vos fichiers
normalement. En développement, le rendu serveur (SSR) et la CSP sont désactivés pour permettre le
rechargement à chaud.

| Commande | Effet |
| --- | --- |
| `make up` / Ctrl+C | démarrer / arrêter |
| `make down` | supprimer les conteneurs (les données restent) |
| `make logs` | suivre les journaux |
| `make artisan cmd="route:list"` | n'importe quelle commande artisan |
| `make shell` | Tinker |
| `make test` / `make lint` | tests Pest / style de code |
| `make reset` | **tout effacer**, base comprise |

Ports (modifiables) : `DEV_PORT=8000`, `DEV_MAIL_PORT=8025`, `DEV_DB_PORT=55432` (utilisateur et mot
de passe `malal`), `DEV_REDIS_PORT=56379`, Vite sur `5173`.

## Option B — Installation locale

Prérequis : PHP 8.4+ (extensions pdo_pgsql, redis, gd, intl, zip, bcmath), Composer, Node.js 20+,
PostgreSQL 15+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Dans `.env`, pour le développement : `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:8000`,
`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync` (emails envoyés
immédiatement), `MAIL_MAILER=log`, `INERTIA_SSR_ENABLED=false`, et les accès `DB_*` à PostgreSQL.

```bash
createdb malal
php artisan migrate
php artisan storage:link
php artisan app:seed-demo --with-users
php artisan app:create-admin --email=vous@entreprise.gn
composer run dev
```

## Commandes de l'application

| Commande | Usage |
| --- | --- |
| `php artisan app:seed-site` | Contenu institutionnel de base (domaines, services avec statut, accueil, entreprise, pages légales à compléter, menus, FAQ). Idempotent : ne réécrit jamais vos modifications (sauf `--force`). Utilisable en production. Données : `database/seeders/data/site-content.json`. |
| `php artisan app:seed-demo [--with-users]` | Lance `app:seed-site` puis ajoute des **données d'exemple** clairement marquées. Refusé en production. `--with-users` crée un compte par rôle et affiche des mots de passe aléatoires. |
| `php artisan app:create-admin --email=…` | Crée le super administrateur. Mot de passe demandé de façon masquée, ou lu dans `ADMIN_PASSWORD` avec `--no-interaction` ; jamais passé en argument. 12 caractères minimum, lettres et chiffres. |

## Front-end

| Commande | Effet |
| --- | --- |
| `npm run dev` | Vite en mode développement (HMR) |
| `npm run build` | bundles client **et** SSR (`bootstrap/ssr/ssr.js`) |
| `npm run icons` | régénère `resources/js/icons.js` depuis `resources/icons/*.svg` (Lucide + logos maison) |
| `npm run africa-map` | régénère la carte d'Afrique en points (`resources/js/Components/Site/AfricaMap.vue`) |

## Tests

```bash
createdb malal_laravel_test
./vendor/bin/pest
```

Les tests utilisent PostgreSQL (`phpunit.xml`), comme la production.

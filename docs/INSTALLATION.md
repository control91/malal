# Installation

## Option A — Tout dans Docker (recommandé pour démarrer)

Seul **Docker Desktop** est nécessaire (ni Python, ni Node, ni PostgreSQL sur la machine).

```bash
make up                                   # = docker compose -f docker-compose.dev.yml up --build
```

Dans un second terminal, la première fois :

```bash
make seed                                 # contenu de base + données d'exemple + un compte par rôle
make admin email=vous@example.com         # super administrateur (mot de passe demandé)
```

| Adresse | Service |
| --- | --- |
| http://localhost:8000 | site public |
| http://localhost:8000/dashboard/ | back-office |
| http://localhost:8025 | **Mailpit** : tous les emails envoyés par l'application (aucun ne part réellement) |

Services lancés : `web` (Django `runserver`, rechargement automatique), `worker` (Celery,
redémarré à chaque modification Python), `assets` (Tailwind en mode watch), `db` (PostgreSQL 17),
`redis`, `mailpit`. Le code est monté dans les conteneurs : modifiez vos fichiers normalement,
les changements sont pris en compte immédiatement. Les migrations sont appliquées au démarrage de `web`.

| Commande | Effet |
| --- | --- |
| `make up` / Ctrl+C | démarrer / arrêter |
| `make down` | supprimer les conteneurs (les données restent) |
| `make logs` | suivre les journaux |
| `make manage cmd="makemigrations"` | n'importe quelle commande `manage.py` |
| `make shell` | shell Django |
| `make test` / `make lint` | tests / lint dans le conteneur |
| `make reset` | **tout effacer**, base comprise |

Ports exposés sur la machine (modifiables par variables d'environnement) : `DEV_PORT=8000`,
`DEV_MAIL_PORT=8025`, `DEV_DB_PORT=55432` (PostgreSQL, utilisateur/mot de passe `malal`),
`DEV_REDIS_PORT=56379`. Exemple : `DEV_PORT=8080 make up`.

Les bibliothèques JS, polices et icônes de `static/` sont versionnées : le conteneur `assets` ne
fait que recompiler le CSS. Après une mise à jour d'une dépendance JavaScript, lancez
`npm run vendor` sur la machine hôte.

## Option B — Installation locale

### Prérequis

- Python 3.12 ou plus récent (testé avec 3.13 dans Docker et 3.14 en local)
- Node.js 20+ (uniquement pour compiler les assets)
- PostgreSQL 15+ (17 recommandé)
- Redis 7 (facultatif en développement)

### Étapes

```bash
git clone <dépôt> && cd <dépôt>
python3 -m venv .venv
.venv/bin/pip install -r requirements/dev.txt
npm install
npm run build
```

`npm run build` exécute :

1. `scripts/vendor.mjs` : copie Alpine.js (+ plugins focus/collapse), htmx, Trix, les polices
   Inter/Manrope et les icônes Lucide listées dans `apps/core/icons.py` vers `static/`, et génère
   `static/icons/sprite.svg` (sélecteur d'icônes du back-office) ;
2. Tailwind : `assets/css/main.css` → `static/css/main.css`.

Pendant le développement du design : `npm run watch:css`.

### Base de données

Par défaut, `DATABASE_URL=postgres:///malal` (socket local). Alternatives :

```bash
createdb malal
# ou, PostgreSQL et Redis seuls dans Docker :
docker compose -f docker-compose.dev.yml up -d db redis
export DATABASE_URL=postgres://malal:malal@localhost:55432/malal
export REDIS_URL=redis://localhost:56379/0
export CELERY_TASK_ALWAYS_EAGER=true   # emails envoyés sans worker
```

Puis :

```bash
.venv/bin/python manage.py migrate
```

## Contenus initiaux

| Commande | Usage |
| --- | --- |
| `manage.py seed_site` | Contenu institutionnel de base (domaines, services avec statuts, accueil, entreprise, pages légales à compléter, menus, FAQ). Idempotent : ne réécrit pas vos modifications (sauf `--force`). Utilisable en production. |
| `manage.py seed_demo [--with-users]` | Lance `seed_site` puis ajoute des **données d'exemple** clairement marquées (solutions « Exemple », tarifs fictifs, articles d'exemple, réalisation « exemple illustratif », prospects `@example.com`). Refusé si `DEBUG=False`. `--with-users` crée un compte par rôle et affiche des mots de passe aléatoires. |
| `manage.py create_admin --email …` | Crée le super administrateur. Le mot de passe est demandé de manière masquée (ou lu dans `DJANGO_ADMIN_PASSWORD` avec `--noinput`) et validé (12 caractères minimum). Il n'est jamais passé en argument. |

## Variables d'environnement

Toutes sont décrites dans [`.env.example`](../.env.example). En développement, un fichier `.env`
est lu automatiquement s'il existe ; sans fichier, les valeurs par défaut de
`config/settings/dev.py` suffisent (emails affichés dans la console, tâches exécutées
immédiatement, cache mémoire).

| Fichier de settings | Usage |
| --- | --- |
| `config.settings.dev` | développement (défaut de `manage.py`) |
| `config.settings.test` | tests (`pytest.ini`) |
| `config.settings.prod` | production (défaut de WSGI/Docker) — refuse de démarrer sans `DJANGO_SECRET_KEY` robuste |

## Lancer les tests

```bash
.venv/bin/python -m pytest
```

Les tests utilisent PostgreSQL (base `test_malal` créée automatiquement).

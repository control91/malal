# Installation

## Prérequis

- Python 3.12 ou plus récent (testé avec 3.13 dans Docker et 3.14 en local)
- Node.js 20+ (uniquement pour compiler les assets)
- PostgreSQL 15+ (17 recommandé)
- Redis 7 (facultatif en développement)

## Étapes

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

## Base de données

Par défaut, `DATABASE_URL=postgres:///malal` (socket local). Alternatives :

```bash
createdb malal
# ou, avec Docker :
docker compose -f docker-compose.dev.yml up -d
export DATABASE_URL=postgres://malal:malal@localhost:5433/malal
export REDIS_URL=redis://localhost:6380/0
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

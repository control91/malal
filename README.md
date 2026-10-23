# MALAL — Plateforme institutionnelle Cloud, SaaS & IA

Site institutionnel premium, CMS, catalogue de solutions, tarifs, blog, réalisations,
mini-CRM et back-office sur mesure, pour une entreprise technologique basée à Conakry.

> Le nom « MALAL » est la valeur par défaut : il se change dans
> **Tableau de bord → Configuration** (comme le logo, les couleurs et les coordonnées).

## Stack

| Couche | Choix |
| --- | --- |
| Backend | Python 3.12+, Django 5.2 LTS, PostgreSQL 17, Django REST Framework (API publique en lecture seule) |
| Tâches asynchrones | Celery + Redis (emails) — exécution synchrone automatique si Redis est absent |
| Frontend | Templates Django, Tailwind CSS v4, Alpine.js, htmx, icônes Lucide (inline), polices auto-hébergées : Jost & Russo One (site public, thème sombre), Inter & Manrope (back-office) |
| Sécurité | django-axes (limitation des connexions), django-csp, anti-spam maison, nh3 (assainissement HTML) |
| Livraison | Docker multi-stage, Gunicorn, WhiteNoise, Nginx, Docker Compose |

Aucune ressource tierce n'est chargée par le navigateur (pas de CDN, pas de Google Fonts, pas de traceur).

## Démarrage rapide avec Docker

```bash
make up                            # site :8000, back-office :8000/dashboard/, emails :8025
make seed                          # (2e terminal) contenu + données d'exemple
make admin email=vous@example.com
```

## Démarrage rapide sans Docker

```bash
python3 -m venv .venv && .venv/bin/pip install -r requirements/dev.txt
npm install && npm run build          # Tailwind + bibliothèques JS/polices/icônes
createdb malal                        # ou: docker compose -f docker-compose.dev.yml up -d db redis
.venv/bin/python manage.py migrate
.venv/bin/python manage.py seed_demo --with-users   # contenu de base + données d'EXEMPLE
.venv/bin/python manage.py create_admin --email vous@entreprise.gn
.venv/bin/python manage.py runserver
```

- Site public : http://localhost:8000
- Back-office : http://localhost:8000/dashboard/
- Administration de secours : http://localhost:8000/django-admin/ (chemin configurable)

## Tests et qualité

```bash
.venv/bin/python -m pytest      # 79 tests : pages, formulaires, anti-spam, permissions, audit, uploads, commandes
.venv/bin/ruff check .
```

## Documentation

- [docs/INSTALLATION.md](docs/INSTALLATION.md) — installation détaillée et variables d'environnement
- [docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md) — mise en production Docker, HTTPS, sauvegardes
- [docs/ADMINISTRATION.md](docs/ADMINISTRATION.md) — guide du back-office pour les équipes
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — modules, modèles, sécurité, évolutions prévues et limites connues

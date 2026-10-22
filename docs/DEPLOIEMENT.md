# Déploiement en production

## Architecture

```
Internet ──HTTPS──> Nginx (TLS, /media) ──> Gunicorn/Django (web) ──> PostgreSQL
                                                 │                  └─> Redis (cache, broker)
                                                 └── Celery worker (emails)
```

- Les fichiers statiques sont servis par WhiteNoise (noms hachés, compression, cache immuable).
- Les médias envoyés par l'équipe sont servis par Nginx depuis le volume `media`
  (aucune exécution possible), ou par S3 si `USE_S3=true`.

## Procédure Docker Compose

```bash
cp .env.example .env        # puis renseigner TOUTES les valeurs « change-me »
python3 -c "import secrets; print(secrets.token_urlsafe(64))"   # → DJANGO_SECRET_KEY
docker compose up -d --build
docker compose exec web python manage.py seed_site            # contenu institutionnel
docker compose exec web python manage.py create_admin --email admin@votre-domaine.gn
```

Les migrations sont appliquées au démarrage du conteneur `web` (`RUN_MIGRATIONS=1`).
Ne lancez **jamais** `seed_demo` en production (la commande le refuse).

## HTTPS

`config.settings.prod` impose HTTPS (redirection, HSTS, cookies `Secure` préfixés `__Host-`).
Deux options :

1. **Proxy amont** (Cloudflare, load balancer, Caddy/Traefik) qui termine TLS et transmet
   `X-Forwarded-Proto: https` à Nginx (déjà relayé à Django).
2. **TLS dans Nginx** : ajoutez un bloc `listen 443 ssl` dans `docker/nginx/default.conf`
   avec vos certificats (Let's Encrypt via certbot) et montez-les dans le conteneur.

Réglez `PROXY_COUNT` selon le nombre de proxys de confiance (1 = Nginx seul), pour que
l'adresse IP réelle soit utilisée par la limitation des connexions et l'anti-spam.

## Durcissement recommandé

- `DJANGO_ADMIN_URL` : un chemin difficile à deviner pour l'admin de secours.
- `ADMIN_ALLOWED_IPS` : restreindre `/dashboard/` et l'admin aux IP du bureau/VPN.
- Activer `SECURE_HSTS_INCLUDE_SUBDOMAINS` seulement si tous les sous-domaines sont en HTTPS.
- Configurer `DJANGO_ADMINS_EMAILS` pour recevoir les erreurs 500.

## Sauvegardes

À automatiser (cron sur l'hôte) et à tester régulièrement :

```bash
# Base de données (quotidien)
docker compose exec -T db pg_dump -U malal -Fc malal > backups/malal-$(date +%F).dump
# Médias
docker run --rm -v malal_media:/data -v "$PWD/backups":/b alpine tar czf /b/media-$(date +%F).tgz -C /data .
# Restauration
docker compose exec -T db pg_restore -U malal -d malal --clean < backups/malal-AAAA-MM-JJ.dump
```

Conservez une copie hors du serveur (règle 3-2-1).

## Journalisation et supervision

- Tous les logs (Django, Gunicorn, Celery) sortent sur stdout : `docker compose logs -f web worker`.
  Chaque ligne porte un identifiant de requête (`req:…`), renvoyé dans l'en-tête `X-Request-ID`.
- Sonde de santé : `GET /health/` (vérifie la base) — utilisée par le `HEALTHCHECK` Docker.
- Les opérations sensibles du back-office sont dans **Journal d'audit**.

## Mise à jour

```bash
git pull
docker compose up -d --build     # migrations appliquées automatiquement
```

## Stockage S3 (optionnel)

Renseignez `USE_S3=true` et les variables `S3_*` (AWS, Scaleway, OVH, MinIO…). Ajoutez le domaine
des fichiers à `CSP_IMG_EXTRA` pour que la politique de sécurité autorise les images.

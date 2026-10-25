# Déploiement en production

## Architecture

```
Internet ─HTTPS─> web (Nginx : /build, /storage, proxy) ─FastCGI─> app (PHP-FPM / Laravel) ─> db (PostgreSQL)
                                                                      │   └──────────────> redis (cache, sessions, files)
                                                                      ├─> ssr (Node : rendu serveur Inertia)
                                                   worker (queue:work : emails) · scheduler (schedule:work)
```

## Procédure Docker Compose

```bash
cp .env.example .env              # renseigner TOUTES les valeurs (DB_PASSWORD, MAIL_*, APP_URL…)
docker compose run --rm app php artisan key:generate --show   # copier la clé dans APP_KEY
docker compose up -d --build
docker compose exec app php artisan app:seed-site
docker compose exec -it app php artisan app:create-admin --email=admin@votre-domaine.gn
```

Au démarrage, le conteneur `app` applique les migrations et met en cache la configuration, les routes
et les vues. Ne lancez **jamais** `app:seed-demo` en production (la commande le refuse).

## HTTPS

Les cookies de session sont `Secure` (`SESSION_SECURE_COOKIE=true`), HSTS est envoyé dès que la
requête arrive en HTTPS. Deux options :

1. **Proxy amont** (Cloudflare, load balancer, Caddy/Traefik) qui termine TLS et transmet
   `X-Forwarded-Proto: https`. Réglez `TRUSTED_PROXIES` sur son adresse.
2. **TLS dans Nginx** : ajoutez un bloc `listen 443 ssl` dans `docker/nginx/default.conf` avec vos
   certificats (Let's Encrypt).

## Rendu serveur (SSR)

Le service `ssr` exécute `bootstrap/ssr/ssr.js` : moteurs de recherche et connexions lentes reçoivent
le HTML complet. S'il est indisponible, Laravel bascule automatiquement sur le rendu côté navigateur
(le site reste fonctionnel, les métadonnées SEO restent rendues par Laravel).

## Durcissement recommandé

- `ADMIN_ALLOWED_IPS` : restreindre `/admin` aux IP du bureau ou du VPN.
- `APP_DEBUG=false` (obligatoire), `LOG_LEVEL=info`.
- Surveillez le journal d'audit (back-office) et les logs (`docker compose logs -f app worker`).

## Sauvegardes

À automatiser (cron sur l'hôte) et à tester régulièrement :

```bash
docker compose exec -T db pg_dump -U malal -Fc malal > backups/malal-$(date +%F).dump
docker run --rm -v malal_storage:/data -v "$PWD/backups":/b alpine tar czf /b/media-$(date +%F).tgz -C /data .
# Restauration
docker compose exec -T db pg_restore -U malal -d malal --clean < backups/malal-AAAA-MM-JJ.dump
```

Conservez une copie hors du serveur (règle 3-2-1).

## Mise à jour

```bash
git pull
docker compose up -d --build
```

## Stockage S3 (optionnel)

`MEDIA_DISK_DRIVER=s3` et les variables `AWS_*` (AWS, Scaleway, OVH, MinIO…). Ajoutez le domaine des
fichiers à `CSP_IMG_EXTRA`.

# syntax=docker/dockerfile:1.7

# ---- 1. PHP dependencies ----------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---- 2. Front-end build (client + SSR bundles) ------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# ---- 3. SSR server (Node) ---------------------------------------------------
FROM node:22-alpine AS ssr
WORKDIR /app
ENV NODE_ENV=production
COPY package.json package-lock.json ./
RUN npm ci --omit=dev --no-audit --no-fund
COPY --from=assets /app/bootstrap/ssr ./bootstrap/ssr
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
USER node
EXPOSE 13714
CMD ["node", "bootstrap/ssr/ssr.js"]

# ---- 4. PHP-FPM runtime -----------------------------------------------------
FROM php:8.4-fpm-alpine AS app
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis gd intl zip bcmath opcache pcntl
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
RUN rm -rf node_modules tests .env && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD php-fpm -t >/dev/null 2>&1 || exit 1
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["php-fpm"]

# ---- 5. Nginx with the public assets ------------------------------------------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

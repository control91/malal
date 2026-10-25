# Development image: PHP CLI + Composer. Source code is bind-mounted.
FROM php:8.4-cli-alpine
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis gd intl zip bcmath pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
EXPOSE 8000
ENTRYPOINT ["/app/docker/dev-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

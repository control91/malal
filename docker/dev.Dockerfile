# Development image: PHP CLI + Composer. Source code is bind-mounted.
FROM php:8.4-cli-alpine
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis gd intl zip bcmath pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
EXPOSE 8000
ENTRYPOINT ["/app/docker/dev-entrypoint.sh"]
# PHP's built-in server directly, not `artisan serve`: the latter lets the bind-mounted
# .env override the container environment (DB_HOST=db, …).
ENV PHP_CLI_SERVER_WORKERS=4
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]

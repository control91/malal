# Development image: PHP CLI + Composer. Source code is bind-mounted.
FROM php:8.4-fpm

WORKDIR /var/www

# Dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev libbz2-dev \
    && docker-php-ext-configure gd \
            --with-freetype \
            --with-jpeg \
            --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        pcntl \
        mbstring \
        bcmath \
        gd \
        bz2 \
        exif \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Fichiers Composer d'abord pour profiter du cache Docker
COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts

COPY . .

# Recréer les dossiers nécessaires à Laravel
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Permissions Laravel
RUN chown -R www-data:www-data \
    /var/www/storage \
    /var/www/bootstrap/cache \
    && chmod -R 775 \
    /var/www/bootstrap/cache \
    /var/www/storage

# Scripts Composer Laravel maintenant que le code existe
RUN composer dump-autoload --optimize

CMD ["php-fpm"]

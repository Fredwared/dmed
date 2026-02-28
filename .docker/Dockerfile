# ── Stage 1: Composer dependencies ──────────────────────────────
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

WORKDIR /app

# ── Stage 2: PHP-FPM application ────────────────────────────────
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    imagemagick-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
        bcmath \
        exif

RUN pecl install redis imagick \
    && docker-php-ext-enable redis imagick

RUN apk del $PHPIZE_DEPS linux-headers

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=composer /app/vendor vendor/
COPY . .

RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi

COPY .docker/php/php.ini $PHP_INI_DIR/conf.d/99-production.ini
COPY .docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]

# ── Stage 3: Nginx ──────────────────────────────────────────────
FROM nginx:1.27-alpine AS nginx

COPY --from=app /var/www/html/public /var/www/html/public
COPY .docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

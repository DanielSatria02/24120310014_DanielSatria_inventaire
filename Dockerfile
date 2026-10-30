# syntax=docker/dockerfile:1

#
# 1. PHP dependencies (Composer)
#
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-scripts


#
# 2. Frontend assets (Vite)
#
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

# Tailwind scans the framework's pagination views (see resources/css/app.css).
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build


#
# 3. Runtime image
#
FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1

# PHP extensions required by Laravel, MySQL and PhpSpreadsheet-less apps.
RUN apk add --no-cache libzip oniguruma curl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libzip-dev oniguruma-dev curl-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip bcmath opcache \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Remap www-data to the host user so bind-mounted files keep sane ownership
# (Linux). On Windows/macOS this is harmless.
RUN set -eux; \
    if getent passwd www-data >/dev/null 2>&1; then deluser www-data; fi; \
    if getent group www-data >/dev/null 2>&1; then delgroup www-data; fi; \
    addgroup -g "${GID}" -S www-data; \
    adduser -u "${UID}" -D -S -G www-data -h /var/www www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-inventaire.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Make sure Laravel's writable directories exist even without a bind mount.
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER root

ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
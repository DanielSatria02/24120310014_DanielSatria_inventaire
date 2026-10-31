# syntax=docker/dockerfile:1

# One stage, one base image. Composer and Node only exist during the build;
# only Nginx (compose) and this PHP image are pulled.
FROM php:8.4-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

# PHP extensions + Composer + Node in a single cached layer.
# Node is only used to compile the frontend assets.
RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache curl unzip oniguruma nodejs npm \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring opcache \
    && apk del .build-deps \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /tmp/*

WORKDIR /var/www/html

# Dependency layers first, so source edits do not reinstall Composer packages.
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-scripts

# Compile Vite/Tailwind assets and drop node_modules from the final image.
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-inventaire.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Laravel's writable directories. World-writable because the container user
# does not always match the host user (rootless Podman/Docker, UID remaps).
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R a+rwX storage bootstrap/cache

ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
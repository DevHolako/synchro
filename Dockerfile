# syntax=docker/dockerfile:1

# Synchro production image (ADR 0012). One PHP image runs the `app` (php-fpm),
# `horizon`, and `scheduler` services; the `web` target serves static assets
# and proxies PHP requests to `app`.

ARG PHP_VERSION=8.5

# ---------------------------------------------------------------------------
# base: PHP runtime + extensions shared by every PHP service
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis pcntl zip intl bcmath opcache \
    && apk add --no-cache fcgi

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# vendor: production Composer dependencies
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi

# ---------------------------------------------------------------------------
# assets: Vite build (the Wayfinder plugin needs PHP + vendor to generate routes)
# ---------------------------------------------------------------------------
FROM vendor AS assets

RUN apk add --no-cache nodejs npm \
    && npm ci --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
# app: php-fpm image used by app, horizon, and scheduler services
# ---------------------------------------------------------------------------
FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/synchro.ini "$PHP_INI_DIR/conf.d/zz-synchro.ini"
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-synchro.conf
COPY docker/entrypoint.sh /usr/local/bin/synchro-entrypoint

COPY --from=assets --chown=www-data:www-data /var/www/html /var/www/html

RUN chmod +x /usr/local/bin/synchro-entrypoint \
    && rm -f public/hot public/fonts-manifest.dev.json \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["synchro-entrypoint"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# web: Nginx serving /public and proxying PHP to the app service
# ---------------------------------------------------------------------------
FROM nginx:1.29-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

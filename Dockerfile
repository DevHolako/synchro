# syntax=docker/dockerfile:1
#
# Synchro production image (ADR 0012). FrankenPHP (Caddy with PHP built in) serves
# the app; the same image runs the horizon and scheduler services.

############################################
# 1. Base PHP + FrankenPHP stage
############################################
FROM dunglas/frankenphp:1-php8.5 AS base

# Required PHP extensions: MySQL, Redis (queues/cache/sessions), Horizon (pcntl/posix),
# and zip for reading .xlsx spreadsheet imports.
RUN install-php-extensions \
    pdo_mysql \
    redis \
    pcntl \
    posix \
    bcmath \
    intl \
    opcache \
    zip

# The host's web server owns the domain and HTTPS, so Caddy listens on plain HTTP.
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    SERVER_NAME=":80" \
    CADDY_GLOBAL_OPTIONS="auto_https off"

WORKDIR /app

COPY docker/Caddyfile /etc/caddy/Caddyfile

############################################
# 2. Composer dependencies builder stage
############################################
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

############################################
# 3. Frontend builder stage (Inertia React + Wayfinder)
############################################
FROM node:22-alpine AS frontend-builder

RUN apk add --no-cache \
    php \
    php-cli \
    php-phar \
    php-mbstring \
    php-openssl \
    php-tokenizer \
    php-xml \
    php-dom \
    php-curl \
    php-fileinfo \
    php-ctype \
    php-json \
    php-session \
    php-pdo \
    php-pdo_mysql \
    php-pcntl \
    php-posix

WORKDIR /app

# Vite bakes this into the bundle, so it comes from the server's .env at build time
ARG VITE_APP_NAME

COPY package.json package-lock.json ./
RUN npm ci

# Include composer vendor dependencies so php artisan wayfinder:generate succeeds
COPY --from=composer-builder /app/vendor /app/vendor
COPY . .
RUN npm run build

############################################
# 4. Production stage
############################################
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

# Use PHP's recommended production settings (errors hidden, OPcache tuned)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# The production default is 128M: a queued import holding a large spreadsheet kills its worker
RUN echo 'memory_limit=-1' > "$PHP_INI_DIR/conf.d/99-memory.ini"

# Spreadsheet imports accept files up to 5 MB (ImportSpreadsheetRequest::MAX_KILOBYTES)
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\n' > "$PHP_INI_DIR/conf.d/99-uploads.ini"

# Copy application source. Host bootstrap caches are excluded by .dockerignore; remove any
# that slipped through so a dev config cache can't override the runtime env.
COPY . /app
RUN rm -f /app/bootstrap/cache/*.php

# Copy production PHP dependencies
COPY --from=composer-builder /app/vendor /app/vendor

# Copy compiled frontend assets
COPY --from=frontend-builder /app/public/build /app/public/build

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Run composer autoloader dump and package discovery
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs && rm /usr/bin/composer

# Set permissions for storage and bootstrap/cache
RUN rm -f public/hot public/fonts-manifest.dev.json \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage \
    # Writable by anyone: the containers may run as the host's user (APP_USER), who caches config here
    && chmod -R 777 /app/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

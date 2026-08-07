# =================================================================
# Religious Affairs Management System (RAMS)
# Multi-stage Dockerfile
#
# Stages:
#   1. node-builder     — Vite + Bootstrap asset compilation
#   2. composer-builder — PHP dependency installation
#   3. production       — PHP 8.4-FPM (final image)
#
# Build:  docker build -t rams-app .
# Run:    docker compose up -d
# =================================================================

# -----------------------------------------------------------------
# Stage 1: Node 22 — Build frontend assets (Vite / Bootstrap 5)
# -----------------------------------------------------------------
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy dependency manifests first for layer caching
COPY package.json package-lock.json ./

RUN npm ci --frozen-lockfile

# Copy remaining source and compile
COPY . .

RUN npm run build

# -----------------------------------------------------------------
# Stage 2: Composer 2 — Install PHP production dependencies
# -----------------------------------------------------------------
FROM composer:2.8 AS composer-builder

WORKDIR /app

# Copy manifests for layer caching
COPY composer.json composer.lock ./

# --no-scripts: skip post-autoload-dump (artisan not runnable here)
# --ignore-platform-reqs: composer:2.8 image lacks ext-gd (installed in production stage)
# scripts are executed in the entrypoint after env is available
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# -----------------------------------------------------------------
# Stage 3: PHP 8.4-FPM Alpine — Production image
# -----------------------------------------------------------------
FROM php:8.4-fpm-alpine AS production

LABEL maintainer="RAMS Development Team"
LABEL org.opencontainers.image.title="Religious Affairs Management System"
LABEL org.opencontainers.image.description="Enterprise Multi-Tenant SaaS — Religious Affairs"

# -----------------------------------------------------------------
# System dependencies
# -----------------------------------------------------------------
RUN apk add --no-cache \
    bash \
    curl \
    su-exec \
    netcat-openbsd \
    # GD / image processing
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    # Zip / intl / XML
    libzip-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    # Required by pcntl (Horizon)
    linux-headers \
    # Misc
    mariadb-client \
    unzip \
    git

# -----------------------------------------------------------------
# PHP extensions
# -----------------------------------------------------------------
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        bcmath \
        intl \
        zip \
        gd \
        exif \
        pcntl \
        opcache

# phpredis — required by REDIS_CLIENT=phpredis in .env
#
# pecl compiles from source, so it needs autoconf and a toolchain. The
# docker-php-ext-install above appears to prove they are present, but it
# installs $PHPIZE_DEPS as its own virtual package and removes them again on the
# way out — so by the time this line runs, phpize cannot find autoconf. They are
# added here explicitly and dropped again, which keeps the image just as small
# without depending on what the previous step happened to leave behind.
RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network .phpize-deps \
    && rm -rf /tmp/pear

# -----------------------------------------------------------------
# PHP runtime configuration
# -----------------------------------------------------------------
COPY docker/php.ini $PHP_INI_DIR/conf.d/rams.ini

# -----------------------------------------------------------------
# Application user (UID 1000, matches common host user)
# -----------------------------------------------------------------
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www

WORKDIR /var/www/html

# -----------------------------------------------------------------
# Copy dependencies from previous stages
# -----------------------------------------------------------------

# PHP vendor
COPY --from=composer-builder --chown=www:www /app/vendor ./vendor

# Application source
COPY --chown=www:www . .

# Compiled frontend assets (public/build from Vite)
COPY --from=node-builder --chown=www:www /app/public/build ./public/build

# -----------------------------------------------------------------
# Public-file init copy
#
# Named volume mounts hide the image's /public directory.
# We store a second copy at /var/www/public-init so the entrypoint
# can populate the empty volume on first start.
# -----------------------------------------------------------------
RUN cp -r /var/www/html/public /var/www/public-init \
    && chown -R www:www /var/www/public-init

# -----------------------------------------------------------------
# Directory permissions
# -----------------------------------------------------------------
RUN mkdir -p \
        storage/logs \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/app/public \
        bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# -----------------------------------------------------------------
# Entrypoint
# -----------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

# Run as root so entrypoint can write to mounted volumes,
# then su-exec switches to www before exec'ing the final process.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

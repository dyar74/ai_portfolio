# syntax=docker/dockerfile:1.7
ARG PHP_VERSION=8.3
ARG NODE_VERSION=22
ARG NGINX_VERSION=1.27

############################################################
# base: PHP-FPM + extensions + shared config
############################################################
FROM php:${PHP_VERSION}-fpm-alpine AS base

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk add --no-cache bash git unzip \
 && install-php-extensions pdo_mysql intl opcache zip bcmath redis \
 && rm -rf /tmp/* /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    COMPOSER_ALLOW_SUPERUSER=1

COPY docker/php/conf.d/ /usr/local/etc/php/conf.d/
COPY docker/php/php-fpm.d/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY --chmod=0755 docker/php/entrypoint.sh /usr/local/bin/app-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

############################################################
# dev: bind-mounted source, host UID/GID, optional xdebug
############################################################
FROM base AS dev
ARG UID=1000
ARG GID=1000

RUN apk add --no-cache shadow \
 && usermod -o -u ${UID} www-data \
 && groupmod -o -g ${GID} www-data \
 && install-php-extensions xdebug \
 && printf 'xdebug.client_host=host.docker.internal\nxdebug.start_with_request=yes\n' \
      > /usr/local/etc/php/conf.d/zz-xdebug.ini \
 && mkdir -p /tmp/composer /tmp/composer-cache \
 && chown -R www-data:www-data /tmp/composer /tmp/composer-cache /var/www

USER www-data

############################################################
# vendor: production composer dependencies (cached layer)
############################################################
FROM base AS vendor
# Match the uid/gid of the final non-root user so the build is stable whether
# it runs as root or www-data, and so the build cache (uid-owned) is consistent.
ARG UID=1000
ARG GID=1000
RUN apk add --no-cache shadow \
 && usermod -o -u ${UID} www-data \
 && groupmod -o -g ${GID} www-data
USER www-data
WORKDIR /var/www/html
COPY composer.json composer.lock symfony.lock* ./
# cache ownership: make sure the mount target is writable by the build user.
RUN --mount=type=cache,target=/tmp/composer-cache,uid=${UID},gid=${GID},mode=0755 \
    composer install --no-dev --no-interaction --no-progress \
      --no-scripts --no-autoloader --prefer-dist

############################################################
# frontend: Vite build -> /app/public/build
# (frontend/vite.config.ts: build.outDir = '../public/build',
#  emptyOutDir = true, manifest = true)
############################################################
FROM node:${NODE_VERSION}-alpine AS frontend
WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund
COPY frontend/ ./
RUN npm run build

############################################################
# prod: immutable PHP-FPM image, non-root
############################################################
FROM base AS prod
ENV APP_ENV=prod APP_DEBUG=0

# Normalise ownership to the final non-root user, so the image is correct no
# matter which uid the build ran as (root on some prod VMs, 1000 locally).
COPY docker/php/conf.d-prod/ /usr/local/etc/php/conf.d/
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --classmap-authoritative --no-dev --no-scripts \
 && mkdir -p var/cache var/log \
 && chown -R www-data:www-data /var/www/html \
 && chmod 2775 /var/www/html/public

USER www-data

############################################################
# nginx_prod: nginx image with the built public/ baked in
############################################################
FROM nginx:${NGINX_VERSION}-alpine AS nginx_prod
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/security-headers.conf /etc/nginx/snippets/security-headers.conf
COPY --from=prod /var/www/html/public /var/www/html/public

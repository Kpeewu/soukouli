# syntax=docker/dockerfile:1

############################
# Stage 1 - Composer deps
############################
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --ignore-platform-reqs: this stage doesn't carry the runtime PHP extensions
# (pdo_pgsql, gd, ...) that composer.json's dependencies declare; those are
# installed in the final runtime stage. --no-scripts here because artisan
# needs the full source tree (copied below) before package:discover can run.
RUN composer install \
      --no-dev \
      --no-interaction \
      --no-scripts \
      --no-progress \
      --prefer-dist \
      --optimize-autoloader \
      --ignore-platform-reqs
COPY . .
# storage/framework/* is excluded by .dockerignore (it holds the developer's
# local view/session cache), so the directories package:discover's boot
# needs (Blade's compiler requires storage/framework/views to exist) must be
# recreated here before the post-autoload-dump script runs.
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
      storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative

############################
# Stage 2 - Frontend assets
############################
FROM node:20-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources/ ./resources/
RUN npm run build

############################
# Stage 3 - Runtime image
############################
FROM php:8.4-fpm-bookworm AS runtime

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION} \
    COMPOSER_ALLOW_SUPERUSER=1

# --- System packages -------------------------------------------------
# Minimal TeX Live set (not texlive-full) derived from every \usepackage{}
# actually used in resources/views/latex/*.blade.php.
RUN apt-get update && apt-get install -y --no-install-recommends \
      libpq-dev \
      libzip-dev \
      libicu-dev \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libwebp-dev \
      libonig-dev \
      libcurl4-openssl-dev \
      rsync \
      postgresql-client \
      ca-certificates \
      texlive-latex-base \
      texlive-latex-recommended \
      texlive-latex-extra \
      texlive-pictures \
      texlive-plain-generic \
      texlive-lang-french \
      lmodern \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
      pdo_pgsql pgsql mbstring gd zip bcmath intl exif pcntl curl opcache \
    && apt-get purge -y \
      libpq-dev libzip-dev libicu-dev libfreetype6-dev libjpeg62-turbo-dev \
      libpng-dev libwebp-dev libonig-dev libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Bake the storage/public symlink at build time (deterministic, no .env
# needed) and keep a pristine copy of public/ for the entrypoint to
# re-sync into the shared volume on every start (see docker/entrypoint.sh).
RUN mkdir -p storage/app/public storage/app/recus storage/framework/cache/data \
      storage/framework/sessions storage/framework/testing storage/framework/views \
      storage/logs bootstrap/cache \
    && php artisan storage:link \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && cp -a /var/www/html/public /opt/public-dist

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

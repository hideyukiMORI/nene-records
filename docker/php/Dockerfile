# CVE-2026-12184 / CVE-2026-14355 対応で patch level を固定（8.4.23 以上を維持・#744）
FROM php:8.4.23-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libsqlite3-dev \
        libjpeg-dev libpng-dev libwebp-dev libavif-dev libfreetype-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-avif --with-freetype \
    # zip: ZipArchive for the org-transfer CLIs (#798/#932) — parity with prod.
    && docker-php-ext-install pdo_mysql pdo_sqlite intl gd zip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && printf '<Directory /var/www/html/public_html>\n    AllowOverride All\n    Require all granted\n</Directory>\n\n# Single-origin: serve the built SPA assets (Vite /assets/*) directly from the\n# frontend build so the PHP-rendered pages can mount the production SPA.\n# Vite copies frontend/public/* to the dist ROOT (not dist/assets), so every\n# public/ passthrough directory the app references needs its own Alias here\n# (theme-thumbnails = built-in theme picker images, #705).\nAlias /assets /var/www/html/frontend/dist/assets\n<Directory /var/www/html/frontend/dist/assets>\n    Require all granted\n</Directory>\nAlias /theme-thumbnails /var/www/html/frontend/dist/theme-thumbnails\n<Directory /var/www/html/frontend/dist/theme-thumbnails>\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/nene-records.conf \
    && a2enconf nene-records \
    && printf 'ServerTokens Prod\nServerSignature Off\nHeader always unset X-Powered-By\n' > /etc/apache2/conf-available/zz-hardening.conf \
    && a2enconf zz-hardening \
    && printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/zz-security.ini \
    && sed -ri -e 's!/var/www/html!/var/www/html/public_html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public_html!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

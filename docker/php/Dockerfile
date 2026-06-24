FROM php:8.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libsqlite3-dev \
        libjpeg-dev libpng-dev libwebp-dev libavif-dev libfreetype-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-avif --with-freetype \
    && docker-php-ext-install pdo_mysql pdo_sqlite intl gd \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && printf '<Directory /var/www/html/public_html>\n    AllowOverride All\n    Require all granted\n</Directory>\n\n# Single-origin: serve the built SPA assets (Vite /assets/*) directly from the\n# frontend build so the PHP-rendered pages can mount the production SPA.\nAlias /assets /var/www/html/frontend/dist/assets\n<Directory /var/www/html/frontend/dist/assets>\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/nene-records.conf \
    && a2enconf nene-records \
    && sed -ri -e 's!/var/www/html!/var/www/html/public_html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public_html!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

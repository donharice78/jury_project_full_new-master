# Stage 1: install Composer dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# Stage 2: build PHP extensions
FROM php:8.4-apache AS ext-builder
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql intl opcache zip \
    && rm -rf /var/lib/apt/lists/*

# Stage 3: final runtime image
FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libicu76 \
    libzip5 \
    default-mysql-client \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=ext-builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=ext-builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --no-dev 2>/dev/null || true

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80

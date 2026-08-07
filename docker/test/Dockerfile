FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-interaction
RUN APP_ENV=test php bin/console sass:build

ENV APP_ENV=test

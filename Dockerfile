# Dockerfile - SIA MI Alghivari (Multi-Stage Production / Demo Build)
# Stage 1: Frontend Asset Compilation
FROM node:22-alpine AS frontend-builder
WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: Application Runtime
# FrankenPHP Classic (Debian-based) + PHP 8.4 Baseline (D-16)
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

# Install system dependencies and required PHP extensions for MySQL, GD, Zip, Intl, Bcmath, Dompdf
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Set production/classic FrankenPHP environment defaults
ENV SERVER_NAME=":80"
ENV CADDY_GLOBAL_OPTIONS="auto_https off"

# Copy application files
COPY . /app

# Copy compiled frontend assets from frontend-builder
COPY --from=frontend-builder /build/public/build /app/public/build

# Install production PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

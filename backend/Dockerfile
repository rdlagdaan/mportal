# --- Build stage: install PHP deps with Composer ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# --- Runtime stage: PHP + Swoole + extensions ---
FROM php:8.2-cli

# OS deps
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zlib1g-dev \
    libbrotli-dev procps \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl

# Swoole
RUN pecl install swoole \
 && docker-php-ext-enable swoole

# App workdir
WORKDIR /var/www/html

# Copy app code (Dockerfile is inside /backend, so this copies backend only)
COPY . .

# Bring in vendor from build stage
COPY --from=vendor /app/vendor ./vendor

# Ensure storage & cache are writable
RUN mkdir -p storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Start script (added in BE-1)
COPY docker/start.sh /usr/local/bin/start.sh
# Normalize line endings in case file was edited on Windows
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["start.sh"]

# Single-stage build to avoid pulling composer:2
FROM php:8.2-cli

# OS deps + build tools for PECL
RUN apt-get update && apt-get install -y \
    git unzip curl procps \
    libpq-dev libzip-dev zlib1g-dev libbrotli-dev \
    build-essential autoconf pkg-config libssl-dev \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl

# Install Composer (no composer:2 image pull)
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# Swoole
RUN pecl install swoole \
 && docker-php-ext-enable swoole

# App
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Permissions
RUN mkdir -p storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Start script
COPY docker/start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["start.sh"]

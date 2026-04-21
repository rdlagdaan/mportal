FROM php:8.3-cli

WORKDIR /var/www/html

# System deps incl. GD + ZIP
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    libzip-dev zlib1g-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql gd zip pcntl

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App code for Reverb
COPY ./backend ./

# Ensure storage is present and writable for logs/cache
RUN mkdir -p storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Prod deps only
RUN composer install --no-dev --prefer-dist --optimize-autoloader

EXPOSE 8082
CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8082"]

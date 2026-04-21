FROM php:8.3-cli

# System deps (+ netcat for wait checks) and PHP extensions incl. pcntl
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zlib1g-dev libicu-dev libonig-dev netcat-openbsd \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql intl zip gd pcntl 

# Swoole for Octane
RUN pecl install swoole && docker-php-ext-enable swoole

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app source
COPY . .

# Install PHP deps (no dev)
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Link storage; ignore if already linked
RUN mkdir -p storage bootstrap/cache \
 && php artisan storage:link || true

# Start script
COPY ./ops/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]
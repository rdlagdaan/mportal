# backend/Dockerfile.railway  — production image for Railway
FROM php:8.2-cli

# System deps
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zlib1g-dev libbrotli-dev pkg-config build-essential \
 && docker-php-ext-install pdo pdo_pgsql zip pcntl \
 && pecl install swoole \
 && docker-php-ext-enable swoole

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App
WORKDIR /var/www/html
COPY . .

# Install PHP deps for production
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Ensure writable dirs
RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Railway will set PORT; Octane will bind to it (start script will use $PORT)
ENV OCTANE_SERVER=swoole
ENV OCTANE_PORT=${PORT}
EXPOSE 8080

# Make the start script executable inside the image
RUN chmod +x docker/start.sh


# We'll add the start script next
CMD ["bash", "-lc", "echo Add start.sh next"]

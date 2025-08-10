# backend/Dockerfile
# Laravel 12 + Octane (Swoole) — single-stage build

FROM php:8.2-cli

# ------------------------------------------------------------------------------
# System deps (with build tools for Swoole) + clean up
# ------------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates procps \
    libpq-dev libzip-dev zlib1g-dev libbrotli-dev \
    build-essential autoconf pkg-config libssl-dev \
 && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------------------------
# PHP extensions
# ------------------------------------------------------------------------------
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl

# ------------------------------------------------------------------------------
# Composer (no composer:2 image pull)
# ------------------------------------------------------------------------------
RUN php -r "copy('https://getcomposer.org/installer','/tmp/composer-setup.php');" \
 && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm /tmp/composer-setup.php

# ------------------------------------------------------------------------------
# Swoole (non-interactive)
# ------------------------------------------------------------------------------
RUN pecl channel-update pecl.php.net \
 && printf "\n" | pecl install swoole \
 && docker-php-ext-enable swoole

# ------------------------------------------------------------------------------
# App code
# ------------------------------------------------------------------------------
WORKDIR /var/www/html
# (Docker context must be the backend/ folder)
COPY . .

# Install PHP deps
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Writable dirs
RUN mkdir -p storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# ------------------------------------------------------------------------------
# Inline start script (no COPY issues)
# ------------------------------------------------------------------------------
RUN cat > /usr/local/bin/start.sh <<'BASH'
#!/usr/bin/env bash
set -euo pipefail

# Require APP_KEY for prod boot
if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Generate one locally: php artisan key:generate --show"
  exit 1
fi

# Clear caches (ok if first run)
php artisan optimize:clear || true

# Wait for DB
ATTEMPTS=30
SLEEP=2
for i in $(seq 1 $ATTEMPTS); do
  php -r '
    try {
      $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
        getenv("DB_HOST"), getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE"));
      new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
      exit(0);
    } catch (Throwable $e) { exit(1); }
  ' && { echo "DB ready"; break; }
  echo "DB not ready... ('$i'/'$ATTEMPTS')"; sleep $SLEEP
done

# Migrate (idempotent)
php artisan migrate --force

# Cache for prod
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Octane (Swoole) on 8080; low workers for Railway
exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --workers=1 --task-workers=0
BASH
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["start.sh"]

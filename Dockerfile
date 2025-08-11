# ---- Stage 0: build the frontend (Vite) ----
FROM node:18-alpine AS fe
WORKDIR /fe
COPY frontend-web/package*.json ./
RUN npm ci
COPY frontend-web .
# These envs match your .env.production; kept here so you can override if needed
ARG VITE_BASE=/app/
ARG VITE_API_URL=/api
ENV VITE_BASE=$VITE_BASE
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build    # outputs /fe/dist

# ---- Stage 1: PHP 8.2 + Octane (Swoole) ----
FROM php:8.2-cli

# System deps (incl. build tools for Swoole)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates procps \
    libpq-dev libzip-dev zlib1g-dev libbrotli-dev \
    build-essential autoconf pkg-config libssl-dev \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl

# Composer
RUN php -r "copy('https://getcomposer.org/installer','/tmp/composer-setup.php');" \
 && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm /tmp/composer-setup.php

# Swoole via PECL
RUN pecl channel-update pecl.php.net \
 && printf "\n" | pecl install swoole \
 && docker-php-ext-enable swoole

# App code (from backend/)
WORKDIR /var/www/html
COPY backend/ ./

# Copy built frontend into Laravel public/app
COPY --from=fe /fe/dist /var/www/html/public/app

# PHP deps
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Writable dirs
RUN mkdir -p storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chown -R www-data:www-data storage/bootstrap/cache || true

# Inline start script (wait DB → migrate → cache → start Octane)
RUN cat > /usr/local/bin/start.sh <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Generate one: php artisan key:generate --show"
  exit 1
fi
php artisan optimize:clear || true

# Wait for DB
ATTEMPTS=30; SLEEP=2
for i in $(seq 1 $ATTEMPTS); do
  php -r '
    try {
      $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
        getenv("DB_HOST"), getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE"));
      new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
      exit(0);
    } catch (Throwable $e) { exit(1); }
  ' && { echo "DB ready"; break; }
  echo "DB not ready... ('$i'/'$ATTEMPTS')"; sleep $SLEEP
done

php artisan migrate --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --workers=1 --task-workers=0
BASH
RUN chmod +x /usr/local/bin/start.sh

# NEW: ensure any stray .env is removed so runtime uses Railway env
RUN rm -f .env

EXPOSE 8080
CMD ["start.sh"]

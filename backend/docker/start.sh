#!/usr/bin/env bash
set -euo pipefail

# Require APP_KEY
if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Generate one locally: php artisan key:generate --show"
  exit 1
fi

# Clear any old caches
php artisan optimize:clear || true

# Wait for DB (simple loop)
ATTEMPTS=20
SLEEP=3
for i in $(seq 1 $ATTEMPTS); do
  php -r '
    try {
      $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
        getenv("DB_HOST"), getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE"));
      new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
      exit(0);
    } catch (Throwable $e) { exit(1); }
  ' && { echo "DB ready"; break; }
  echo "DB not ready... ($i/$ATTEMPTS)"; sleep $SLEEP
done

# Migrate (idempotent)
php artisan migrate --force

# Cache for prod
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Octane on 8080 with conservative workers for Railway
exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --workers=1 --task-workers=0

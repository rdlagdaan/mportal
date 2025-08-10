#!/usr/bin/env bash
set -e
cd /var/www/html

# Clear any stale caches (safe if first run)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Require APP_KEY to be provided via env (recommended for Railway)
if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Set APP_KEY in Railway environment variables."
  exit 1
fi

# Run migrations with a small retry loop in case DB isn't ready yet
attempt=0
until php artisan migrate --force; do
  attempt=$((attempt+1))
  if [ "$attempt" -ge 30 ]; then
    echo "ERROR: Database not ready after multiple attempts."
    exit 1
  fi
  echo "Database not ready yet. Retrying in 3s... (attempt $attempt/30)"
  sleep 3
done

# Cache for performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start Octane bound to Railway's PORT
php artisan octane:start \
  --server=swoole \
  --host=0.0.0.0 \
  --port="${PORT:-8080}" \
  --workers=1 \
  --max-requests=200 \
  --task-workers=0

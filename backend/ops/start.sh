#!/usr/bin/env bash
set -e

# Wait for Redis
if [ -n "${REDIS_HOST}" ]; then
  echo "Waiting for Redis at ${REDIS_HOST}:${REDIS_PORT:-6379}..."
  until nc -z ${REDIS_HOST} ${REDIS_PORT:-6379}; do sleep 1; done
fi

# Wait for Postgres
if [ -n "${DB_HOST}" ]; then
  echo "Waiting for Postgres at ${DB_HOST}:${DB_PORT:-5432}..."
  until nc -z ${DB_HOST} ${DB_PORT:-5432}; do sleep 1; done
fi

php artisan key:generate --force || true
php artisan optimize:clear || true
php artisan migrate --force || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting Octane (Swoole) on :8080..."
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8080 --max-requests=2000

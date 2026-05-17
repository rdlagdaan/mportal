#!/bin/bash
set -e

echo "🔧 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

echo "🚀 Starting Laravel HTTP server on :8000..."
php artisan serve --host=0.0.0.0 --port=8000 &

echo "🌐 Starting Reverb WebSocket server on :8082..."
php artisan reverb:start --host=0.0.0.0 --port=8082 --debug &


echo "⏰ Running scheduler loop..."

while true; do
  php artisan schedule:run --verbose --no-interaction
  sleep 60
done
# echo "⏰ Starting Laravel Scheduler..."
# php artisan schedule:work 

# #!/bin/bash
# set -e

# echo "🔧 Clearing caches..."
# php artisan config:clear
# php artisan cache:clear
# php artisan route:clear
# php artisan view:clear
# php artisan optimize:clear

# echo "🚀 Starting Laravel HTTP server on :8000..."
# php artisan serve --host=0.0.0.0 --port=8000 &

# echo "🌐 Starting Reverb WebSocket server on :8082..."
# php artisan reverb:start --host=0.0.0.0 --port=8082 --debug &

# echo "⏰ Starting Laravel Scheduler..."
# php artisan schedule:work &

# # 🔥 KEEP CONTAINER ALIVE
# wait -n

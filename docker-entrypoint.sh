#!/bin/sh
set -e

# Create storage subdirectories if they do not exist
mkdir -p /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/storage/app/reports \
         /app/bootstrap/cache

# Fix permissions for www-data if running as root
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true
fi

# Optional automated migration and seeding for isolated demo container
if [ "$DEMO_AUTO_SEED" = "true" ]; then
    echo "Waiting for database connection..."
    php artisan db:monitor --max=10 2>/dev/null || sleep 3
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "Seeding demo portfolio data..."
    php artisan db:seed --class=DemoPortfolioSeeder --force --no-interaction
fi

# Optional automated database migration for production
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running production database migrations..."
    php artisan migrate --force --no-interaction
fi

exec "$@"

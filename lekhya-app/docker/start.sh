#!/bin/sh
set -e

cd /var/www/html

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force

# Seed if SEED_ON_BOOT is set (first deploy only)
if [ "$SEED_ON_BOOT" = "true" ]; then
    php artisan db:seed --class=HsnSacSeeder --force
    php artisan db:seed --class=PlanSeeder --force
    php artisan db:seed --class=PermissionSeeder --force
fi

# Cache config/routes/views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Start all services via supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

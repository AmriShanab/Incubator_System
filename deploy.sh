#!/bin/bash
set -e

echo "🚀 Deploying application..."

# 1. Enter maintenance mode
php artisan down || true

# 2. Pull the latest code
sudo git pull origin main

# 3. Install optimized dependencies
sudo composer install --optimize-autoloader --no-dev

# 4. Run database migrations safely
php artisan migrate --force

# 5. Clear and rebuild all Laravel caches
php artisan optimize:clear
php artisan optimize

# 6. Rebuild Filament-specific caches (CRITICAL FOR SPEED)
php artisan filament:cache-components
php artisan icons:cache

# 7. Exit maintenance mode
php artisan up

echo "✅ Deployment finished instantly!"
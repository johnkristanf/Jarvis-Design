#!/bin/bash

set -e

echo "🚀 Starting Laravel Deployment Tasks..."

echo "Composer Version:"
composer --version

echo "Clearing Composer cache..."
composer clear-cache

echo "Installing Composer dependencies..."
composer install --no-interaction --no-dev --optimize-autoloader

# Run database migrations
php artisan migrate --seed --force

# Run artisan commands
php artisan optimize:clear
php artisan queue:work sqs --tries=3

echo "✅ Laravel deployment complete. Starting PHP-FPM..."
exec php-fpm -D -R

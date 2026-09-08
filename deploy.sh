#!/bin/bash

set -e

echo "🚀 Starting deployment..."

if [ ! -f .env ]; then
    echo "📋 Creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate || true
    echo "⚠️ NOTE: Please edit .env with your production database & Google API credentials!"
fi

echo "📥 Fetching latest code..."
git pull origin master || true

echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🎨 Verifying static frontend assets & storage link..."
php artisan storage:link --force || true

echo "🔒 Creating required directories and setting permissions..."
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache uploads
chmod -R 775 storage bootstrap/cache uploads
chown -R www-data:www-data storage bootstrap/cache uploads || true

echo "🗄️ Running database migrations..."
php artisan migrate --force

echo "⚡ Optimizing application cache..."
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "🔄 Restarting queue workers & supervisor..."
php artisan queue:restart || true
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart all || true
fi

echo "✅ Deployment completed successfully!"

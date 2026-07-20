#!/bin/bash

# ========================================================
#   ARSIP SURAT BPSDMP - Automated Production Deployment Script
# ========================================================

set -e

echo "🚀 Starting deployment..."

# 0. Ensure .env exists
if [ ! -f .env ]; then
    echo "📋 Creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate || true
    echo "⚠️ NOTE: Please edit .env with your production database & Google API credentials!"
fi

# 1. Pull latest changes
echo "📥 Fetching latest code..."
git pull origin master || true

# 2. Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install & Build frontend assets
echo "🎨 Building frontend assets..."
npm install --ignore-scripts
npm run build

# 4. Ensure directory permissions & folder creation
echo "🔒 Creating required directories and setting permissions..."
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache uploads
chmod -R 775 storage bootstrap/cache uploads
chown -R www-data:www-data storage bootstrap/cache uploads || true

# 5. Database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 6. Cache optimization & Clear
echo "⚡ Optimizing application cache..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:clear
php artisan cache:clear

# 7. Restart background workers (Queue & Scheduler)
echo "🔄 Restarting queue workers & supervisor..."
php artisan queue:restart || true
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart all || true
fi

echo "✅ Deployment completed successfully!"

#!/bin/bash

# ========================================================
#   ARSIP SURAT BPSDMP - Automated Production Deployment Script
# ========================================================

set -e

echo "🚀 Starting deployment..."

# 1. Pull latest changes
echo "📥 Fetching latest code..."
git pull origin master

# 2. Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Install & Build frontend assets
echo "🎨 Building frontend assets..."
npm install --ignore-scripts
npm run build

# 4. Ensure directory permissions
echo "🔒 Setting directory permissions..."
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

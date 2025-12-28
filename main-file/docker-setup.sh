#!/bin/bash
set -e

echo "🚀 Starting BookingGo SAAS setup..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until docker exec bookinggo-mysql mysqladmin ping -h"localhost" --silent; do
    echo "Waiting for database connection..."
    sleep 2
done
echo "✅ MySQL is ready!"

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
docker exec -it bookinggo-app composer install --no-interaction --optimize-autoloader

# Generate application key if not exists
echo "🔑 Generating application key..."
docker exec -it bookinggo-app php artisan key:generate --force

# Set permissions
echo "🔒 Setting permissions..."
docker exec -it bookinggo-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker exec -it bookinggo-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Clear and cache config
echo "🧹 Clearing caches..."
docker exec -it bookinggo-app php artisan config:clear
docker exec -it bookinggo-app php artisan cache:clear
docker exec -it bookinggo-app php artisan view:clear
docker exec -it bookinggo-app php artisan route:clear

# Run migrations
echo "🗄️  Running database migrations..."
docker exec -it bookinggo-app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker exec -it bookinggo-app php artisan db:seed --force

# Cache configurations
echo "⚡ Caching configurations..."
docker exec -it bookinggo-app php artisan config:cache
docker exec -it bookinggo-app php artisan route:cache
docker exec -it bookinggo-app php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
docker exec -it bookinggo-app php artisan storage:link

# Install NPM dependencies and build assets
echo "📦 Installing NPM dependencies..."
docker exec -it bookinggo-app npm install

echo "🎨 Building frontend assets..."
docker exec -it bookinggo-app npm run build

echo ""
echo "✅ =========================================="
echo "✅ BookingGo SAAS is ready!"
echo "✅ =========================================="
echo ""
echo "🌐 Access URLs:"
echo "   - HTTP:  http://localhost:8085"
echo "   - HTTPS: https://localhost:9443"
echo "   - HTTPS: https://bookinggo.local:9443"
echo ""
echo "📊 Database:"
echo "   - Host: localhost"
echo "   - Port: 3307"
echo "   - Database: bookinggo_saas"
echo "   - Username: bookinggo_user"
echo "   - Password: bookinggo_pass_2024"
echo ""
echo "🔴 Redis:"
echo "   - Host: localhost"
echo "   - Port: 6380"
echo ""
echo "📝 Add to your hosts file (C:\Windows\System32\drivers\etc\hosts):"
echo "   127.0.0.1 bookinggo.local"
echo ""

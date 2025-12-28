# BookingGo SAAS - Docker Setup Script
# This script will set up the entire application automatically

Write-Host "🚀 Starting BookingGo SAAS Docker Setup..." -ForegroundColor Cyan
Write-Host ""

# Check if Docker is running
Write-Host "🐳 Checking Docker..." -ForegroundColor Yellow
try {
    docker info | Out-Null
    Write-Host "✅ Docker is running!" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not running. Please start Docker Desktop." -ForegroundColor Red
    exit 1
}

# Add bookinggo.local to hosts file
Write-Host ""
Write-Host "🌐 Configuring hosts file..." -ForegroundColor Yellow
$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsPath -Raw
if ($hostsContent -notmatch "bookinggo.local") {
    try {
        Add-Content -Path $hostsPath -Value "`n127.0.0.1 bookinggo.local" -Force
        Write-Host "✅ Added bookinggo.local to hosts file" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  Please add '127.0.0.1 bookinggo.local' to $hostsPath manually (as Administrator)" -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ bookinggo.local already in hosts file" -ForegroundColor Green
}

# Navigate to main-file directory
Set-Location main-file

# Update .env file with Docker configuration
Write-Host ""
Write-Host "⚙️  Configuring environment..." -ForegroundColor Yellow
if (Test-Path ".env") {
    $envContent = Get-Content ".env" -Raw
    $envContent = $envContent -replace "DB_CONNECTION=.*", "DB_CONNECTION=mysql"
    $envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=bookinggo-mysql"
    $envContent = $envContent -replace "DB_PORT=.*", "DB_PORT=3306"
    $envContent = $envContent -replace "DB_DATABASE=.*", "DB_DATABASE=bookinggo_saas"
    $envContent = $envContent -replace "DB_USERNAME=.*", "DB_USERNAME=bookinggo_user"
    $envContent = $envContent -replace "DB_PASSWORD=.*", "DB_PASSWORD=bookinggo_pass_2024"
    $envContent = $envContent -replace "REDIS_HOST=.*", "REDIS_HOST=bookinggo-redis"
    $envContent = $envContent -replace "REDIS_PORT=.*", "REDIS_PORT=6379"
    $envContent = $envContent -replace "CACHE_DRIVER=.*", "CACHE_DRIVER=redis"
    $envContent = $envContent -replace "SESSION_DRIVER=.*", "SESSION_DRIVER=redis"
    $envContent = $envContent -replace "QUEUE_CONNECTION=.*", "QUEUE_CONNECTION=redis"
    Set-Content ".env" $envContent
    Write-Host "✅ Environment configured!" -ForegroundColor Green
}

# Stop any existing containers with same name (graceful cleanup)
Write-Host ""
Write-Host "🧹 Cleaning up old containers (if any)..." -ForegroundColor Yellow
docker-compose down 2>$null
Write-Host "✅ Cleanup complete!" -ForegroundColor Green

# Build and start containers
Write-Host ""
Write-Host "🏗️  Building Docker containers..." -ForegroundColor Yellow
docker-compose build --no-cache
Write-Host "✅ Build complete!" -ForegroundColor Green

Write-Host ""
Write-Host "🚀 Starting containers..." -ForegroundColor Yellow
docker-compose up -d
Write-Host "✅ Containers started!" -ForegroundColor Green

# Wait for services to be ready
Write-Host ""
Write-Host "⏳ Waiting for services to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

# Wait for MySQL
Write-Host "⏳ Waiting for MySQL to be ready..." -ForegroundColor Yellow
$maxAttempts = 30
$attempt = 0
while ($attempt -lt $maxAttempts) {
    $result = docker exec bookinggo-mysql mysqladmin ping -h"localhost" --silent 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ MySQL is ready!" -ForegroundColor Green
        break
    }
    $attempt++
    Start-Sleep -Seconds 2
}

# Install Composer dependencies
Write-Host ""
Write-Host "📦 Installing Composer dependencies..." -ForegroundColor Yellow
docker exec bookinggo-app composer install --no-interaction --optimize-autoloader
Write-Host "✅ Composer dependencies installed!" -ForegroundColor Green

# Generate application key
Write-Host ""
Write-Host "🔑 Generating application key..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan key:generate --force
Write-Host "✅ Application key generated!" -ForegroundColor Green

# Set permissions
Write-Host ""
Write-Host "🔒 Setting permissions..." -ForegroundColor Yellow
docker exec bookinggo-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker exec bookinggo-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
Write-Host "✅ Permissions set!" -ForegroundColor Green

# Clear caches
Write-Host ""
Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan config:clear 2>$null
docker exec bookinggo-app php artisan cache:clear 2>$null
docker exec bookinggo-app php artisan view:clear 2>$null
docker exec bookinggo-app php artisan route:clear 2>$null
Write-Host "✅ Caches cleared!" -ForegroundColor Green

# Run migrations
Write-Host ""
Write-Host "🗄️  Running database migrations..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan migrate --force
Write-Host "✅ Migrations complete!" -ForegroundColor Green

# Seed database
Write-Host ""
Write-Host "🌱 Seeding database..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan db:seed --force
Write-Host "✅ Database seeded!" -ForegroundColor Green

# Create storage link
Write-Host ""
Write-Host "🔗 Creating storage link..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan storage:link
Write-Host "✅ Storage link created!" -ForegroundColor Green

# Cache configurations
Write-Host ""
Write-Host "⚡ Caching configurations..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan config:cache
docker exec bookinggo-app php artisan route:cache 2>$null
docker exec bookinggo-app php artisan view:cache
Write-Host "✅ Configurations cached!" -ForegroundColor Green

# Install NPM dependencies and build
Write-Host ""
Write-Host "📦 Installing NPM dependencies..." -ForegroundColor Yellow
docker exec bookinggo-app npm install
Write-Host "✅ NPM dependencies installed!" -ForegroundColor Green

Write-Host ""
Write-Host "🎨 Building frontend assets..." -ForegroundColor Yellow
docker exec bookinggo-app npm run build
Write-Host "✅ Assets built!" -ForegroundColor Green

# Final status
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ BookingGo SAAS is ready!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Access URLs:" -ForegroundColor Cyan
Write-Host "   HTTP:  http://localhost:8085" -ForegroundColor White
Write-Host "   HTTPS: https://localhost:9443" -ForegroundColor White
Write-Host "   HTTPS: https://bookinggo.local:9443" -ForegroundColor White
Write-Host ""
Write-Host "📊 Database Connection:" -ForegroundColor Cyan
Write-Host "   Host:     localhost" -ForegroundColor White
Write-Host "   Port:     3307" -ForegroundColor White
Write-Host "   Database: bookinggo_saas" -ForegroundColor White
Write-Host "   Username: bookinggo_user" -ForegroundColor White
Write-Host "   Password: bookinggo_pass_2024" -ForegroundColor White
Write-Host ""
Write-Host "🔴 Redis:" -ForegroundColor Cyan
Write-Host "   Host: localhost" -ForegroundColor White
Write-Host "   Port: 6380" -ForegroundColor White
Write-Host ""
Write-Host "🐳 Docker Commands:" -ForegroundColor Cyan
Write-Host "   Stop:    docker-compose down" -ForegroundColor White
Write-Host "   Start:   docker-compose up -d" -ForegroundColor White
Write-Host "   Logs:    docker-compose logs -f" -ForegroundColor White
Write-Host "   Restart: docker-compose restart" -ForegroundColor White
Write-Host ""
Write-Host "🎉 Happy coding!" -ForegroundColor Magenta
Write-Host ""

Set-Location ..

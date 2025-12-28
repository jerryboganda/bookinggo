# BookingGo SAAS - Complete Docker Deployment
# Run as Administrator for hosts file modification

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  BookingGo SAAS - Docker Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "⚠️  This script requires Administrator privileges for hosts file modification." -ForegroundColor Yellow
    Write-Host "   Attempting to restart as Administrator..." -ForegroundColor Yellow
    Write-Host ""
    Start-Process powershell.exe "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
    exit
}

# Check Docker
Write-Host "🐳 Checking Docker..." -ForegroundColor Yellow
try {
    docker info | Out-Null
    Write-Host "✅ Docker is running!" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    pause
    exit 1
}

# Add to hosts file
Write-Host ""
Write-Host "🌐 Configuring hosts file..." -ForegroundColor Yellow
$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
try {
    $hostsContent = Get-Content $hostsPath -Raw
    if ($hostsContent -notmatch "bookinggo.local") {
        Add-Content -Path $hostsPath -Value "`n127.0.0.1 bookinggo.local" -Force
        Write-Host "✅ Added bookinggo.local to hosts file" -ForegroundColor Green
    } else {
        Write-Host "✅ bookinggo.local already in hosts file" -ForegroundColor Green
    }
} catch {
    Write-Host "⚠️  Could not modify hosts file automatically" -ForegroundColor Yellow
}

Set-Location -Path $PSScriptRoot

# Configure .env
Write-Host ""
Write-Host "⚙️  Configuring environment..." -ForegroundColor Yellow
if (Test-Path ".env") {
    $env = @"
APP_NAME=BookingGoSAAS
APP_ENV=local
APP_DEBUG=true
APP_URL=https://bookinggo.local:9443

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=bookinggo-mysql
DB_PORT=3306
DB_DATABASE=bookinggo_saas
DB_USERNAME=bookinggo_user
DB_PASSWORD=bookinggo_pass_2024

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=bookinggo-redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@bookinggo.local
MAIL_FROM_NAME=BookingGoSAAS

VITE_APP_NAME=BookingGoSAAS
"@
    
    # Read existing .env to preserve APP_KEY if exists
    $existingEnv = Get-Content ".env" -Raw
    if ($existingEnv -match "APP_KEY=(.+)") {
        $appKey = $matches[1]
        $env += "`nAPP_KEY=$appKey"
    } else {
        $env += "`nAPP_KEY="
    }
    
    Set-Content ".env" $env
    Write-Host "✅ Environment configured!" -ForegroundColor Green
}

# Clean up old containers
Write-Host ""
Write-Host "🧹 Cleaning up..." -ForegroundColor Yellow
docker-compose down -v 2>$null | Out-Null
Write-Host "✅ Cleanup complete!" -ForegroundColor Green

# Build containers
Write-Host ""
Write-Host "🏗️  Building Docker containers (this may take a few minutes)..." -ForegroundColor Yellow
docker-compose build --no-cache 2>&1 | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Build complete!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Build completed with warnings" -ForegroundColor Yellow
}

# Start containers
Write-Host ""
Write-Host "🚀 Starting containers..." -ForegroundColor Yellow
docker-compose up -d
Start-Sleep -Seconds 10
Write-Host "✅ Containers started!" -ForegroundColor Green

# Wait for MySQL
Write-Host ""
Write-Host "⏳ Waiting for MySQL..." -ForegroundColor Yellow
$attempt = 0
while ($attempt -lt 30) {
    $result = docker exec bookinggo-mysql mysqladmin ping -h"localhost" --silent 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ MySQL is ready!" -ForegroundColor Green
        break
    }
    $attempt++
    Start-Sleep -Seconds 2
}

# Install dependencies
Write-Host ""
Write-Host "📦 Installing Composer dependencies..." -ForegroundColor Yellow
docker exec bookinggo-app composer install --no-interaction --optimize-autoloader --no-dev 2>&1 | Out-Null
Write-Host "✅ Dependencies installed!" -ForegroundColor Green

# Generate key
Write-Host ""
Write-Host "🔑 Generating application key..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan key:generate --force 2>&1 | Out-Null
Write-Host "✅ Key generated!" -ForegroundColor Green

# Permissions
Write-Host ""
Write-Host "🔒 Setting permissions..." -ForegroundColor Yellow
docker exec bookinggo-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>&1 | Out-Null
docker exec bookinggo-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>&1 | Out-Null
Write-Host "✅ Permissions set!" -ForegroundColor Green

# Clear caches
Write-Host ""
Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan config:clear 2>&1 | Out-Null
docker exec bookinggo-app php artisan cache:clear 2>&1 | Out-Null
docker exec bookinggo-app php artisan view:clear 2>&1 | Out-Null
docker exec bookinggo-app php artisan route:clear 2>&1 | Out-Null
Write-Host "✅ Caches cleared!" -ForegroundColor Green

# Migrations
Write-Host ""
Write-Host "🗄️  Running migrations..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan migrate --force 2>&1 | Out-Null
Write-Host "✅ Migrations complete!" -ForegroundColor Green

# Seed database
Write-Host ""
Write-Host "🌱 Seeding database..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan db:seed --force 2>&1 | Out-Null
Write-Host "✅ Database seeded!" -ForegroundColor Green

# Storage link
Write-Host ""
Write-Host "🔗 Creating storage link..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan storage:link 2>&1 | Out-Null
Write-Host "✅ Storage link created!" -ForegroundColor Green

# Cache config
Write-Host ""
Write-Host "⚡ Caching configurations..." -ForegroundColor Yellow
docker exec bookinggo-app php artisan config:cache 2>&1 | Out-Null
docker exec bookinggo-app php artisan route:cache 2>&1 | Out-Null
docker exec bookinggo-app php artisan view:cache 2>&1 | Out-Null
Write-Host "✅ Configurations cached!" -ForegroundColor Green

# NPM
Write-Host ""
Write-Host "📦 Installing NPM dependencies..." -ForegroundColor Yellow
docker exec bookinggo-app npm install 2>&1 | Out-Null
Write-Host "✅ NPM installed!" -ForegroundColor Green

Write-Host ""
Write-Host "🎨 Building assets..." -ForegroundColor Yellow
docker exec bookinggo-app npm run build 2>&1 | Out-Null
Write-Host "✅ Assets built!" -ForegroundColor Green

# Success message
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✅ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green  
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Access Your Application:" -ForegroundColor Cyan
Write-Host ""
Write-Host "   🔓 HTTP:  http://localhost:8085" -ForegroundColor White
Write-Host "   🔒 HTTPS: https://localhost:9443" -ForegroundColor White
Write-Host "   🔒 HTTPS: https://bookinggo.local:9443" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Database (MySQL):" -ForegroundColor Cyan
Write-Host "   Host:     localhost:3307" -ForegroundColor White
Write-Host "   Database: bookinggo_saas" -ForegroundColor White
Write-Host "   Username: bookinggo_user" -ForegroundColor White
Write-Host "   Password: bookinggo_pass_2024" -ForegroundColor White
Write-Host ""
Write-Host "🔴 Redis:" -ForegroundColor Cyan
Write-Host "   Host: localhost:6380" -ForegroundColor White
Write-Host ""
Write-Host "🐳 Useful Commands:" -ForegroundColor Cyan
Write-Host "   View logs:     docker-compose logs -f" -ForegroundColor White
Write-Host "   Stop:          docker-compose down" -ForegroundColor White
Write-Host "   Restart:       docker-compose restart" -ForegroundColor White
Write-Host "   Shell (app):   docker exec -it bookinggo-app bash" -ForegroundColor White
Write-Host "   Shell (mysql): docker exec -it bookinggo-mysql mysql -u bookinggo_user -p" -ForegroundColor White
Write-Host ""
Write-Host "🎉 Happy coding!" -ForegroundColor Magenta
Write-Host ""

pause

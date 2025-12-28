@echo off
REM Simple deployment script for BookingGo SAAS
echo.
echo ========================================
echo BookingGo SAAS - Docker Setup
echo ========================================
echo.

REM Check if SSL certs exist
if not exist "docker\nginx\ssl\cert.pem" (
    echo Creating SSL certificates...
    mkcert -install >nul 2>&1
    mkcert bookinggo.local localhost 127.0.0.1 ::1 >nul 2>&1
    if exist "bookinggo.local+3.pem" (
        move /Y "bookinggo.local+3.pem" "docker\nginx\ssl\cert.pem" >nul
        move /Y "bookinggo.local+3-key.pem" "docker\nginx\ssl\key.pem" >nul
        echo SSL certificates created!
    ) else (
        echo Error: Failed to generate SSL certificates
        pause
        exit /b 1
    )
)

echo Building containers...
docker-compose build
if errorlevel 1 (
    echo Build failed!
    pause
    exit /b 1
)
echo.
echo Starting containers...
docker-compose up -d
echo.
echo Waiting for MySQL...
timeout /t 20 /nobreak >nul
echo.
echo Setting up Laravel...
docker exec bookinggo-app php artisan key:generate --force
docker exec bookinggo-app php artisan config:clear
docker exec bookinggo-app php artisan cache:clear
docker exec bookinggo-app php artisan migrate --force
docker exec bookinggo-app php artisan db:seed --force
docker exec bookinggo-app php artisan storage:link
docker exec bookinggo-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker exec bookinggo-app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
echo.
echo ========================================
echo Deployment Complete!
echo ========================================
echo.
echo Access URLs:
echo   HTTP:  http://localhost:8085
echo   HTTPS: https://localhost:9443
echo   HTTPS: https://bookinggo.local:9443
echo.
echo Database:
echo   Host:     localhost:3307
echo   Database: bookinggo_saas
echo   Username: bookinggo_user
echo   Password: bookinggo_pass_2024
echo.
echo Redis:
echo   Port: 6380
echo.
pause
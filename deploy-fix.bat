@echo off
REM GrabBaskets - Production Deployment Fix Script (Windows)
REM Run this on your Windows production server after git pull

echo ================================================
echo GrabBaskets - Emergency Fix Deployment
echo ================================================
echo.

cd /d "%~dp0"

echo Step 1: Clearing all Laravel caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo [OK] Caches cleared
echo.

echo Step 2: Rebuilding optimized caches...
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo [OK] Caches rebuilt
echo.

echo Step 3: Running optimization...
php artisan optimize
echo [OK] Optimization complete
echo.

echo ================================================
echo Deployment Complete!
echo ================================================
echo.
echo Next steps:
echo 1. Visit https://grabbaskets.com/health-check to verify app is running
echo 2. Visit https://grabbaskets.com/test-index-debug for diagnostics
echo 3. Visit https://grabbaskets.com/ to test homepage
echo.
echo If still showing errors:
echo - Check storage/logs/laravel.log for recent errors
echo - Ensure APP_DEBUG=false in production .env
echo - Verify database credentials in .env
echo.
pause

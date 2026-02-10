@echo off
echo Starting deployment...

echo Creating storage directories...
if not exist "storage\framework\views" (
    mkdir "storage\framework\views" >nul 2>&1
    echo Created: storage\framework\views
) else (
    echo Exists: storage\framework\views
)
if not exist "storage\framework\cache\data" (
    mkdir "storage\framework\cache\data" >nul 2>&1
    echo Created: storage\framework\cache\data
) else (
    echo Exists: storage\framework\cache\data
)
if not exist "storage\framework\sessions" (
    mkdir "storage\framework\sessions" >nul 2>&1
    echo Created: storage\framework\sessions
) else (
    echo Exists: storage\framework\sessions
)
if not exist "storage\logs" (
    mkdir "storage\logs" >nul 2>&1
    echo Created: storage\logs
) else (
    echo Exists: storage\logs
)
if not exist "bootstrap\cache" (
    mkdir "bootstrap\cache" >nul 2>&1
    echo Created: bootstrap\cache
) else (
    echo Exists: bootstrap\cache
)

echo Clearing and caching configurations...
php artisan config:clear
php artisan config:cache

echo Clearing routes...
php artisan route:clear

echo Clearing application cache...
php artisan cache:clear

echo Clearing view cache...
if exist "storage\framework\views" (
    php artisan view:clear
) else (
    echo View cache directory doesn't exist, skipping view:clear
)

echo Installing dependencies...
composer install --optimize-autoloader --no-dev

echo Deployment completed successfully!
pause
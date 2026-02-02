# Fix Product Page 500 Error - Clear compiled views on production
Write-Host "=====================================" -ForegroundColor Green
Write-Host "  Product Page 500 Error Fix" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""

# Step 1: Clear all optimizations
Write-Host "[Step 1/4] Clearing all Laravel caches..." -ForegroundColor Yellow
php artisan optimize:clear

# Step 2: Clear compiled views specifically
Write-Host "[Step 2/4] Clearing compiled views..." -ForegroundColor Yellow
php artisan view:clear

# Step 3: Clear config cache
Write-Host "[Step 3/4] Clearing configuration cache..." -ForegroundColor Yellow
php artisan config:clear

# Step 4: Re-cache configuration for production
Write-Host "[Step 4/4] Caching configuration..." -ForegroundColor Yellow
php artisan config:cache

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host "  Cache cleared successfully!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "Test the product page: https://grabbaskets.laravel.cloud/product/1619" -ForegroundColor Cyan
Write-Host ""

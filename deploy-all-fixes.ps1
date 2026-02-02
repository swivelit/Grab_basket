# =============================================================================
# Windows PowerShell Deployment Script for GrabBaskets - All Recent Fixes
# =============================================================================
# This script deploys:
# - PDF export fixes with image support
# - Database column alignment fixes  
# - Category page grid alignment fix
# - Server configuration updates
# =============================================================================

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🚀 GrabBaskets Deployment Script (Windows)" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Error handling
$ErrorActionPreference = "Stop"

try {
    Write-Host "📦 Step 1: Pulling latest code from GitHub..." -ForegroundColor Yellow
    git pull origin main
    Write-Host "✅ Code pulled successfully" -ForegroundColor Green
    Write-Host ""

    Write-Host "🧹 Step 2: Clearing all caches..." -ForegroundColor Yellow
    php artisan optimize:clear
    php artisan view:clear
    php artisan config:clear
    php artisan route:clear
    php artisan cache:clear
    Write-Host "✅ Caches cleared" -ForegroundColor Green
    Write-Host ""

    Write-Host "📝 Step 3: Optimizing for production..." -ForegroundColor Yellow
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    Write-Host "✅ Optimization complete" -ForegroundColor Green
    Write-Host ""

    Write-Host "🔧 Step 4: Checking PHP configuration..." -ForegroundColor Yellow
    Write-Host "Current PHP version:"
    php -v | Select-Object -First 1
    Write-Host ""
    Write-Host "⚠️  For PDF export, ensure these PHP settings:" -ForegroundColor Yellow
    Write-Host "   - max_execution_time = 900"
    Write-Host "   - memory_limit = 2G"
    Write-Host ""

    Write-Host "🔍 Step 5: Running post-deployment checks..." -ForegroundColor Yellow

    # Check critical files exist
    $criticalFiles = @(
        "app\Http\Controllers\ProductImportExportController.php",
        "resources\views\buyer\products.blade.php",
        "resources\views\seller\exports\products-pdf-with-images.blade.php"
    )

    foreach ($file in $criticalFiles) {
        if (Test-Path $file) {
            Write-Host "✅ $file exists" -ForegroundColor Green
        } else {
            Write-Host "❌ $file not found" -ForegroundColor Red
        }
    }

    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "✅ Deployment completed successfully!" -ForegroundColor Green
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📋 Next steps:" -ForegroundColor Yellow
    Write-Host "1. Test category pages: https://grabbaskets.laravel.cloud/buyer/category/5"
    Write-Host "2. Test PDF export in seller dashboard"
    Write-Host "3. Monitor server logs for any errors"
    Write-Host ""
    Write-Host "🔧 If PDF export has timeout issues, update web server config:" -ForegroundColor Yellow
    Write-Host "   Nginx: fastcgi_read_timeout 900;"
    Write-Host "   Apache: FcgidIOTimeout 900"
    Write-Host "   PHP-FPM: request_terminate_timeout = 900"
    Write-Host ""
    Write-Host "📊 To check logs:" -ForegroundColor Yellow
    Write-Host "   Get-Content storage\logs\laravel.log -Tail 50 -Wait"
    Write-Host ""

} catch {
    Write-Host ""
    Write-Host "❌ Deployment failed!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}

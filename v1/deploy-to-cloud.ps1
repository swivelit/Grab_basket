# =============================================================================
# Laravel Cloud Deployment Script (PowerShell)
# =============================================================================
# This script deploys the latest code changes to your Laravel Cloud server
# Including: Category alignment fix, PDF export fixes, and optimization
# =============================================================================

Write-Host "🚀 Starting deployment to Laravel Cloud..." -ForegroundColor Cyan
Write-Host ""

function Print-Status {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Print-Warning {
    param([string]$Message)
    Write-Host "⚠ $Message" -ForegroundColor Yellow
}

function Print-Error {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

# Step 1: Pull latest code
Write-Host "📥 Step 1: Pulling latest code from GitHub..." -ForegroundColor Cyan
git pull origin main
if ($LASTEXITCODE -eq 0) {
    Print-Status "Code pulled successfully"
} else {
    Print-Error "Failed to pull code"
    exit 1
}
Write-Host ""

# Step 2: Install/Update dependencies
Write-Host "📦 Step 2: Installing/Updating Composer dependencies..." -ForegroundColor Cyan
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -eq 0) {
    Print-Status "Dependencies updated"
} else {
    Print-Warning "Composer install had issues (continuing...)"
}
Write-Host ""

# Step 3: Clear all caches
Write-Host "🧹 Step 3: Clearing application caches..." -ForegroundColor Cyan
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
Print-Status "All caches cleared"
Write-Host ""

# Step 4: Optimize for production
Write-Host "⚡ Step 4: Optimizing for production..." -ForegroundColor Cyan
php artisan config:cache
php artisan route:cache
php artisan view:cache
Print-Status "Application optimized"
Write-Host ""

# Step 5: Run migrations (if any)
Write-Host "🗄️  Step 5: Running database migrations..." -ForegroundColor Cyan
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Print-Status "Migrations completed"
} else {
    Print-Warning "No new migrations or migration issues"
}
Write-Host ""

# Step 6: Restart queue workers (if applicable)
Write-Host "🔄 Step 6: Restarting queue workers..." -ForegroundColor Cyan
php artisan queue:restart
Print-Status "Queue workers signaled to restart"
Write-Host ""

Write-Host "==============================================" -ForegroundColor Green
Write-Host "✅ Deployment completed successfully!" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 What was deployed:" -ForegroundColor Cyan
Write-Host "  • Category page alignment fix"
Write-Host "  • PDF export with images fix"
Write-Host "  • Database column fixes"
Write-Host "  • Performance optimizations"
Write-Host ""
Write-Host "🧪 Next steps:" -ForegroundColor Cyan
Write-Host "  1. Test category pages (e.g., /buyer/category/5)"
Write-Host "  2. Test PDF exports from seller dashboard"
Write-Host "  3. Check browser console for any errors"
Write-Host "  4. Monitor server logs for issues"
Write-Host ""
Write-Host "📊 To view logs: php artisan tail" -ForegroundColor Yellow
Write-Host ""

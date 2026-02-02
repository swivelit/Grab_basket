# ============================================
# Clear Production Cache - Windows PowerShell
# ============================================
# Purpose: Clear all Laravel caches for login/logout fix
# Date: October 23, 2025
# ============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Clearing Production Caches" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Clear compiled views
Write-Host "1. Clearing compiled Blade views..." -ForegroundColor Yellow
php artisan view:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Compiled views cleared" -ForegroundColor Green
} else {
    Write-Host "   ✗ Failed to clear views" -ForegroundColor Red
}
Write-Host ""

# Clear application cache
Write-Host "2. Clearing application cache..." -ForegroundColor Yellow
php artisan cache:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Application cache cleared" -ForegroundColor Green
} else {
    Write-Host "   ✗ Failed to clear cache" -ForegroundColor Red
}
Write-Host ""

# Clear config cache
Write-Host "3. Clearing configuration cache..." -ForegroundColor Yellow
php artisan config:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Configuration cache cleared" -ForegroundColor Green
} else {
    Write-Host "   ✗ Failed to clear config" -ForegroundColor Red
}
Write-Host ""

# Clear route cache
Write-Host "4. Clearing route cache..." -ForegroundColor Yellow
php artisan route:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Route cache cleared" -ForegroundColor Green
} else {
    Write-Host "   ✗ Failed to clear routes" -ForegroundColor Red
}
Write-Host ""

# Optimize application
Write-Host "5. Optimizing application..." -ForegroundColor Yellow
php artisan optimize:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Application optimized" -ForegroundColor Green
} else {
    Write-Host "   ✗ Failed to optimize" -ForegroundColor Red
}
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Cache Clearing Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Test login at: https://grabbaskets.laravel.cloud/login" -ForegroundColor White
Write-Host "2. Test logout functionality" -ForegroundColor White
Write-Host "3. Verify search page loads correctly" -ForegroundColor White
Write-Host "4. Check for any 500 errors" -ForegroundColor White
Write-Host ""

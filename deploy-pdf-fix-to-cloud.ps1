# Deploy PDF Export Fix to Cloud Server

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PDF Export Fix - Cloud Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: Not in Laravel root directory" -ForegroundColor Red
    Write-Host "Please run this script from the project root" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Current directory verified" -ForegroundColor Green
Write-Host ""

# Step 2: Check git status
Write-Host "Step 1: Checking Git Status..." -ForegroundColor Yellow
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "⚠️  Uncommitted changes found:" -ForegroundColor Yellow
    git status --short
    Write-Host ""
    $commit = Read-Host "Commit changes? (y/n)"
    if ($commit -eq "y") {
        git add -A
        $message = Read-Host "Commit message"
        git commit -m $message
        git push origin main
        Write-Host "✅ Changes committed and pushed" -ForegroundColor Green
    }
} else {
    Write-Host "✅ No uncommitted changes" -ForegroundColor Green
}
Write-Host ""

# Step 3: Deploy to cloud
Write-Host "Step 2: Deploying to Cloud..." -ForegroundColor Yellow
Write-Host "Running: git push origin main" -ForegroundColor Cyan

try {
    git push origin main
    Write-Host "✅ Code pushed to GitHub" -ForegroundColor Green
} catch {
    Write-Host "✅ Already up to date" -ForegroundColor Green
}
Write-Host ""

# Step 4: Instructions for cloud server
Write-Host "Step 3: Cloud Server Commands" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "SSH into your cloud server and run these commands:" -ForegroundColor White
Write-Host ""
Write-Host "# Navigate to project directory" -ForegroundColor Gray
Write-Host "cd /path/to/your/project" -ForegroundColor Cyan
Write-Host ""
Write-Host "# Pull latest changes" -ForegroundColor Gray
Write-Host "git pull origin main" -ForegroundColor Cyan
Write-Host ""
Write-Host "# Install/update dependencies (if needed)" -ForegroundColor Gray
Write-Host "composer install --no-dev --optimize-autoloader" -ForegroundColor Cyan
Write-Host ""
Write-Host "# Clear all caches" -ForegroundColor Gray
Write-Host "php artisan optimize:clear" -ForegroundColor Cyan
Write-Host "php artisan config:clear" -ForegroundColor Cyan
Write-Host "php artisan cache:clear" -ForegroundColor Cyan
Write-Host "php artisan view:clear" -ForegroundColor Cyan
Write-Host ""
Write-Host "# Set permissions" -ForegroundColor Gray
Write-Host "chmod -R 775 storage bootstrap/cache" -ForegroundColor Cyan
Write-Host "chown -R www-data:www-data storage bootstrap/cache" -ForegroundColor Cyan
Write-Host ""
Write-Host "# Restart web server" -ForegroundColor Gray
Write-Host "# For Apache:" -ForegroundColor Gray
Write-Host "sudo systemctl restart apache2" -ForegroundColor Cyan
Write-Host "# OR for Nginx:" -ForegroundColor Gray
Write-Host "sudo systemctl restart nginx" -ForegroundColor Cyan
Write-Host "sudo systemctl restart php8.2-fpm" -ForegroundColor Cyan
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 5: Cloud-specific configuration check
Write-Host "Step 4: Cloud Configuration Checklist" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "On your cloud server, ensure these files are configured:" -ForegroundColor White
Write-Host ""

Write-Host "1. PHP Configuration (php.ini):" -ForegroundColor Yellow
Write-Host "   Location: /etc/php/8.2/fpm/php.ini or /etc/php/8.2/apache2/php.ini"
Write-Host "   memory_limit = 2G" -ForegroundColor Cyan
Write-Host "   max_execution_time = 900" -ForegroundColor Cyan
Write-Host "   max_input_time = 900" -ForegroundColor Cyan
Write-Host "   default_socket_timeout = 900" -ForegroundColor Cyan
Write-Host ""

Write-Host "2. If Using Nginx (/etc/nginx/nginx.conf or site config):" -ForegroundColor Yellow
Write-Host "   fastcgi_read_timeout 900;" -ForegroundColor Cyan
Write-Host "   fastcgi_send_timeout 900;" -ForegroundColor Cyan
Write-Host "   proxy_read_timeout 900;" -ForegroundColor Cyan
Write-Host "   proxy_send_timeout 900;" -ForegroundColor Cyan
Write-Host ""

Write-Host "3. If Using Apache (/etc/apache2/apache2.conf):" -ForegroundColor Yellow
Write-Host "   Timeout 900" -ForegroundColor Cyan
Write-Host "   ProxyTimeout 900" -ForegroundColor Cyan
Write-Host ""

Write-Host "4. If Using PHP-FPM (/etc/php/8.2/fpm/pool.d/www.conf):" -ForegroundColor Yellow
Write-Host "   request_terminate_timeout = 900" -ForegroundColor Cyan
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 6: Create quick test URL
Write-Host "Step 5: Testing on Cloud" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "After deployment, test the PDF export:" -ForegroundColor White
Write-Host ""
Write-Host "1. Login to your cloud site as seller" -ForegroundColor Cyan
Write-Host "2. Go to: https://your-domain.com/seller/import-export" -ForegroundColor Cyan
Write-Host "3. Click 'Export PDF (Simple)' - should work in 3-5 seconds" -ForegroundColor Cyan
Write-Host "4. Click 'Export Catalog PDF with Images' - wait 60-120 seconds" -ForegroundColor Cyan
Write-Host ""
Write-Host "If you get 502 error:" -ForegroundColor Yellow
Write-Host "- Check cloud server logs: tail -f /var/log/nginx/error.log" -ForegroundColor Cyan
Write-Host "- Check Laravel logs: tail -f storage/logs/laravel.log" -ForegroundColor Cyan
Write-Host "- Verify timeouts are set (see Step 4 above)" -ForegroundColor Cyan
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Generate deployment checklist
Write-Host "Step 6: Deployment Checklist" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[ ] Code pushed to GitHub" -ForegroundColor White
Write-Host "[ ] SSH into cloud server" -ForegroundColor White
Write-Host "[ ] git pull origin main" -ForegroundColor White
Write-Host "[ ] composer install (if needed)" -ForegroundColor White
Write-Host "[ ] php artisan optimize:clear" -ForegroundColor White
Write-Host "[ ] chmod 775 storage bootstrap/cache" -ForegroundColor White
Write-Host "[ ] Update php.ini with timeout settings" -ForegroundColor White
Write-Host "[ ] Update web server config (nginx/apache)" -ForegroundColor White
Write-Host "[ ] Restart web server" -ForegroundColor White
Write-Host "[ ] Restart PHP-FPM (if applicable)" -ForegroundColor White
Write-Host "[ ] Test simple PDF export" -ForegroundColor White
Write-Host "[ ] Test PDF with images export" -ForegroundColor White
Write-Host "[ ] Check for errors in logs" -ForegroundColor White
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Deployment preparation complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Your cloud URL: " -NoNewline
Write-Host "https://your-cloud-domain.com" -ForegroundColor Cyan
Write-Host ""
Write-Host "Need help? Check these files:" -ForegroundColor Yellow
Write-Host "- 502_ERROR_FIX_SUMMARY.md" -ForegroundColor Cyan
Write-Host "- FIX_502_ERROR.md" -ForegroundColor Cyan
Write-Host "- PDF_DOWNLOAD_FIX_COMPLETE.md" -ForegroundColor Cyan
Write-Host ""

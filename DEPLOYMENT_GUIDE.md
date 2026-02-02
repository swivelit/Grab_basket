# Laravel Cloud Deployment Guide

## Quick Deploy

### For Laravel Cloud (Linux/Bash)
```bash
cd /path/to/your/app
chmod +x deploy-to-cloud.sh
./deploy-to-cloud.sh
```

### For Local Testing (Windows/PowerShell)
```powershell
cd e:\e-com_updated_final\e-com_updated
.\deploy-to-cloud.ps1
```

## Manual Deployment Steps

If you prefer to run commands manually on your Laravel Cloud server:

### 1. Connect to Your Server
```bash
# Via SSH (if available)
ssh user@your-server-ip

# Or use Laravel Cloud dashboard terminal
```

### 2. Navigate to App Directory
```bash
cd /path/to/your/laravel/app
```

### 3. Pull Latest Code
```bash
git pull origin main
```

### 4. Clear Caches
```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 5. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Run Migrations (if needed)
```bash
php artisan migrate --force
```

### 7. Set Permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### 8. Restart Services
```bash
# Restart queue workers
php artisan queue:restart

# If using PHP-FPM, restart it
sudo systemctl restart php8.2-fpm

# If using Nginx
sudo systemctl restart nginx
```

## What This Deployment Includes

### Recent Fixes
- ✅ **Category page alignment fix** - Fixed duplicate div tag breaking grid layout
- ✅ **PDF export with images** - Base64 embedding and proper download headers
- ✅ **Database column fixes** - Removed references to non-existent columns
- ✅ **Performance optimizations** - Cache clearing and route optimization

### Files Changed
- `resources/views/buyer/products.blade.php` - Grid alignment fix
- `app/Http/Controllers/ProductImportExportController.php` - PDF generation
- `resources/views/seller/exports/products-pdf.blade.php` - Simple PDF template
- `resources/views/seller/exports/products-pdf-with-images.blade.php` - Image PDF template
- `public/.htaccess` - Timeout and memory configurations

## Testing After Deployment

### 1. Test Category Pages
```
Visit: https://grabbaskets.laravel.cloud/buyer/category/5
✓ Check product grid alignment
✓ Verify 4 columns on desktop
✓ Test mobile responsiveness
```

### 2. Test PDF Export
```
1. Login as seller
2. Go to Products > Export
3. Try "Export PDF" (simple)
4. Try "Export PDF with Images"
✓ Should download automatically
✓ Check file opens correctly
✓ Verify images appear in PDF
```

### 3. Monitor Logs
```bash
# View real-time logs
php artisan tail

# Or check Laravel log files
tail -f storage/logs/laravel.log
```

## Server Configuration (If Needed)

### PHP Configuration
Edit `php.ini` or PHP-FPM pool config:
```ini
max_execution_time = 900
memory_limit = 2G
upload_max_filesize = 50M
post_max_size = 50M
```

### Nginx Configuration
Edit your site config:
```nginx
location ~ \.php$ {
    fastcgi_read_timeout 900;
    fastcgi_send_timeout 900;
    # ... other configs
}
```

### Restart After Config Changes
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

## Rollback (If Needed)

If something goes wrong:
```bash
# View commit history
git log --oneline

# Rollback to previous commit
git reset --hard <commit-hash>

# Clear caches
php artisan optimize:clear
```

## Support

### Check Application Status
```bash
# Check PHP version
php -v

# Check Laravel version
php artisan --version

# List all routes
php artisan route:list

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Common Issues

**Issue: 502 Bad Gateway on PDF Export**
- Solution: Increase PHP timeout and memory in php.ini
- Restart PHP-FPM after changes

**Issue: Category page still misaligned**
- Solution: Clear browser cache (Ctrl+Shift+R)
- Run: `php artisan view:clear` on server

**Issue: Changes not showing**
- Solution: Clear all caches with `php artisan optimize:clear`
- Check if using CDN/proxy (Cloudflare) - purge cache there too

## Laravel Cloud Specific

If you're using Laravel Cloud (cloud.laravel.com):

1. **Via Dashboard**
   - Go to your project dashboard
   - Click "Deploy" button
   - Or use the built-in terminal

2. **Auto-Deploy**
   - Set up GitHub auto-deploy in Laravel Cloud settings
   - Each push to `main` will auto-deploy

3. **Environment Variables**
   - Ensure `.env` settings are correct in Laravel Cloud dashboard
   - Check `APP_ENV=production` and `APP_DEBUG=false`

## Contact & Resources

- Repository: https://github.com/grabbaskets-hash/grabbaskets
- Branch: main
- Latest Commit: 24f0fd8a (Category alignment fix)

---
**Last Updated:** October 15, 2025

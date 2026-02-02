# Cloud Deployment - PDF Export Fix

## Quick Deploy to Cloud ☁️

### Your Cloud URL
Check your Laravel Cloud dashboard or server for the actual URL.

---

## Step 1: Push Code (✅ Done)

All PDF fixes have been pushed to GitHub:
```
d186394e - docs: Add 502 error fix summary
ec650719 - fix: Resolve 502 error - increase timeouts
7c8e03d7 - fix: Force PDF download with proper headers
```

---

## Step 2: Deploy on Cloud Server

### SSH into Your Cloud Server:

```bash
ssh your-username@your-server-ip
```

### Pull Latest Changes:

```bash
cd /home/forge/your-domain.com  # Adjust path
git pull origin main
```

### Clear Caches:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
```

### Set Permissions:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage
```

---

## Step 3: Configure Timeouts (CRITICAL ⚠️)

### A. Update PHP Settings

```bash
# Find php.ini location
php --ini

# Edit php.ini
sudo nano /etc/php/8.2/fpm/php.ini
```

Add/Update:
```ini
memory_limit = 2G
max_execution_time = 900
max_input_time = 900
default_socket_timeout = 900
```

### B. Update Nginx (if using Nginx)

```bash
sudo nano /etc/nginx/sites-available/your-domain.com
```

Add inside `location ~ \.php$` block:
```nginx
fastcgi_read_timeout 900;
fastcgi_send_timeout 900;
fastcgi_buffering off;
```

### C. Restart Services

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## Step 4: Test PDF Export

1. **Login:** `https://your-domain.com`
2. **Go to:** Seller Dashboard → Import/Export
3. **Test Simple PDF:** Click "Export PDF (Simple)" → Should download in 3-5 seconds
4. **Test With Images:** Click "Export Catalog PDF with Images" → Wait 60-120 seconds

---

## Step 5: Check Logs (If Issues)

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

---

## Common Issues & Fixes

### Still Getting 502?

**Check 1:** Services restarted?
```bash
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
```

**Check 2:** Cloudflare timeout?
- Cloudflare Free has 100-second timeout limit
- **Workaround:** Temporarily disable proxy (DNS only)

**Check 3:** Check actual timeout value:
```bash
# Create temp test file
echo "<?php phpinfo(); ?>" > public/test.php
# Visit: https://your-domain.com/test.php
# Check max_execution_time
# Delete after: rm public/test.php
```

---

## Quick Commands Summary

```bash
# Deploy
git pull origin main
php artisan optimize:clear

# Configure (edit these files)
sudo nano /etc/php/8.2/fpm/php.ini
sudo nano /etc/nginx/sites-available/your-domain.com

# Restart
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Monitor
tail -f storage/logs/laravel.log
```

---

## What's Fixed

✅ **Backend:**
- Memory increased: 512M → 2G
- Timeout increased: 300s → 900s
- Query optimized (only primary images)
- Better error handling

✅ **Frontend:**
- Loading indicators
- Proper download headers
- Console logging

✅ **Server:**
- `.htaccess` timeout settings
- PHP configuration
- Web server timeouts

---

## Expected Results

### Simple PDF:
- Time: 3-5 seconds ✅
- Size: ~40 KB
- Works: YES

### PDF with Images (136 products):
- Time: 60-90 seconds ✅
- Size: 5-10 MB
- Works: YES (after timeout config)

---

## Need Help?

Check these documents:
- `502_ERROR_FIX_SUMMARY.md` - Complete fix overview
- `FIX_502_ERROR.md` - Detailed troubleshooting
- `PDF_DOWNLOAD_FIX_COMPLETE.md` - Download fix details

---

**Status:** ✅ Ready to deploy and test on cloud
**Action:** SSH into server, run commands above, restart services, test

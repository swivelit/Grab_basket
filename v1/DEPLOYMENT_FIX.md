# 500 Error Fix - Complete Deployment Guide

## ✅ What Was Fixed

### 1. Enhanced Error Handling
- **HomeController**: Added comprehensive error handling with database connection checks
- **Fallback Support**: Multiple fallback levels if components fail to load
- **Better Logging**: Detailed error logging for diagnostics

### 2. Diagnostic Routes Added
- `/health-check` - Quick health status check (JSON response)
- `/test-index-debug` - Comprehensive diagnostics with permission checks
- Both routes help identify issues without triggering the error

### 3. Improved Stability
- Individual try-catch for each data component (categories, products, banners)
- Database connection test before loading data
- Graceful degradation if database queries fail
- Safe fallback HTML if even views fail to render

## 🚀 Quick Deployment Steps

### Option 1: Automated Fix (Recommended)

**If you have SSH access to your production server:**

```bash
# Navigate to your application directory
cd /path/to/your/application

# Pull latest changes
git pull origin main

# Run the automated fix script
bash deploy-fix.sh
```

**For Windows servers:**

```cmd
cd C:\path\to\your\application
git pull origin main
deploy-fix.bat
```

### Option 2: Manual Fix

**Step 1: Pull Latest Code**
```bash
git pull origin main
```

**Step 2: Clear All Caches**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

**Step 3: Rebuild Caches**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

**Step 4: Fix Permissions** (Linux/Mac only)
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```
*(Replace `www-data` with your web server user if different)*

### Option 3: Use the Emergency Fix Script

If you already deployed the `fix-500-error.php` script:

1. Visit: `https://grabbaskets.com/fix-500-error.php?secret=grabbaskets2025`
2. Review the output
3. **DELETE the file immediately** after use for security

## 🔍 Testing After Deployment

### Test 1: Health Check
```
https://grabbaskets.com/health-check
```
Expected response:
```json
{
  "status": "OK",
  "timestamp": "2025-11-01 20:00:00",
  "app": "GrabBaskets",
  "env": "production",
  "debug": false
}
```

### Test 2: Diagnostics
```
https://grabbaskets.com/test-index-debug
```
This will show detailed diagnostics of all components.

### Test 3: Homepage
```
https://grabbaskets.com/
```
Should load without 500 error.

## ⚙️ Production .env Settings

Ensure your production `.env` has these settings:

```env
# Environment
APP_ENV=production
APP_DEBUG=false  # Set to true only when debugging, then back to false
APP_URL=https://grabbaskets.com

# Session (already configured)
SESSION_DRIVER=file
SESSION_LIFETIME=720

# Timezone (already configured)
APP_TIMEZONE=Asia/Kolkata

# Database
DB_CONNECTION=mysql
DB_HOST=db-a00cde8f-38c6-4d8e-8caf-dfdb13c5652e.ap-southeast-1.public.db.laravel.cloud
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=xHBa04pHpOi3g5axB3qMHJehmQD1Xp
```

## 🐛 Still Getting 500 Error?

### Step 1: Enable Debug Mode Temporarily
Edit `.env` on production:
```env
APP_DEBUG=true
```

Then access the homepage and copy the full error message.

### Step 2: Check Recent Logs
```bash
tail -200 storage/logs/laravel.log
```

Look for the most recent error entries (today's date).

### Step 3: Verify Permissions
```bash
ls -la storage/
ls -la storage/logs/
ls -la storage/framework/sessions/
ls -la storage/framework/views/
ls -la bootstrap/cache/
```

All directories should be writable (775 or 777 permissions).

### Step 4: Check PHP Version
```bash
php -v
```
Must be PHP 8.1 or higher.

### Step 5: Verify Required PHP Extensions
```bash
php -m | grep -E 'pdo|mysql|mbstring|xml|curl|zip|fileinfo|json|tokenizer|openssl'
```

All should be present.

## 📋 Common Issues & Solutions

### Issue 1: Database Connection Failed
**Solution:**
- Verify database credentials in `.env`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo();`
- Check if database server is accessible from production server

### Issue 2: Storage Not Writable
**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue 3: View Not Found / Compilation Error
**Solution:**
```bash
php artisan view:clear
php artisan view:cache
```

### Issue 4: Route Not Found
**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Issue 5: Class Not Found
**Solution:**
```bash
composer dump-autoload
php artisan optimize
```

## 📞 What to Report If Still Broken

If the site is still showing 500 errors after all fixes:

1. **Paste the output from:**
   ```
   https://grabbaskets.com/test-index-debug
   ```

2. **Paste last 50 lines of log:**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

3. **With APP_DEBUG=true, paste the error screen** (screenshot or text)

4. **Server info:**
   ```bash
   php -v
   php -m
   php artisan about
   ```

## ✅ Expected Results After Fix

- ✅ Homepage loads successfully at https://grabbaskets.com
- ✅ Categories display correctly
- ✅ Products display correctly
- ✅ No 500 errors in logs
- ✅ Mobile navigation works
- ✅ Payment system functional
- ✅ Session timeout: 12 hours
- ✅ Timestamps show IST timezone

## 🔒 Security Reminders

1. **Set APP_DEBUG=false** in production after fixing
2. **Delete fix-500-error.php** if you uploaded it
3. **Delete test-homepage.php** from production (it's for local testing only)
4. **Keep deploy-fix.sh/bat** for future deployments

## 📚 Files Changed in This Fix

- `app/Http/Controllers/HomeController.php` - Enhanced error handling
- `routes/web.php` - Added health-check and improved test-index-debug
- `public/fix-500-error.php` - Emergency diagnostic script (delete after use)
- `deploy-fix.sh` - Automated deployment script (Linux/Mac)
- `deploy-fix.bat` - Automated deployment script (Windows)
- `DEPLOYMENT_FIX.md` - This documentation

---

**Last Updated**: 2025-11-01  
**Status**: Ready for deployment  
**Tested**: ✅ All components working locally

# FIX: All Pages Showing grabbaskets.laravel.cloud Instead of grabbaskets.com

## Problem
URLs are showing as `https://grabbaskets.laravel.cloud/buyer/category/2` instead of `https://grabbaskets.com/buyer/category/2`

## Root Cause
Laravel's cached configuration still has the old APP_URL from Laravel Cloud deployment. Even though `.env` has been updated to `APP_URL=https://grabbaskets.com`, the cache needs to be cleared on the production server.

## Solution

### Step 1: Upload Cache Clearing Script
1. **Via Hostinger File Manager:**
   - Log into your Hostinger control panel
   - Go to **File Manager**
   - Navigate to `public_html/`
   - Click **Upload** button
   - Upload the file: `clear_caches_hostinger.php`

2. **Or via FTP:**
   - Connect to your Hostinger FTP
   - Navigate to `/public_html/`
   - Upload `clear_caches_hostinger.php`

### Step 2: Run the Script
Visit in your browser:
```
https://grabbaskets.com/clear_caches_hostinger.php
```

Click the **"Clear All Caches Now"** button.

You should see success messages for:
- ✅ Configuration Cache cleared
- ✅ Application Cache cleared
- ✅ Route Cache cleared
- ✅ View Cache cleared
- ✅ Optimization Cache cleared
- ✅ APP_URL is correctly set to: https://grabbaskets.com

### Step 3: Delete the Script (IMPORTANT!)
**For security, immediately delete the file after use:**
1. Go back to File Manager
2. Find `clear_caches_hostinger.php`
3. Delete it

### Step 4: Test
Visit any category page:
- https://grabbaskets.com/buyer/category/2
- https://grabbaskets.com/buyer/category/4
- https://grabbaskets.com/buyer/category/5

All URLs should now show `grabbaskets.com` instead of `grabbaskets.laravel.cloud`

## Alternative: Via SSH (if you have SSH access)

```bash
ssh your_username@your_hostinger_server
cd public_html
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

## What This Fixes
- ✅ Category page URLs
- ✅ Product page URLs
- ✅ Navigation links
- ✅ Form actions
- ✅ Redirects
- ✅ Email notification links
- ✅ All generated URLs throughout the application

## Files Modified
- Created: `clear_caches_hostinger.php` - Web-based cache clearing tool
- No code changes needed - just cache clearing

## Technical Details
Laravel caches the `config('app.url')` value. When you update `.env` on the server, Laravel continues using the cached value until caches are cleared.

Commands that were run:
```bash
php artisan config:clear      # Clears config cache
php artisan cache:clear       # Clears app cache
php artisan route:clear       # Clears route cache
php artisan view:clear        # Clears compiled views
php artisan optimize:clear    # Clears all optimization caches
```

## Verification
After clearing caches, check:
1. Any category page URL in browser address bar
2. Hover over navigation links - check the bottom left status bar
3. View page source - search for "laravel.cloud" (should find none)
4. Check email notifications (if any are sent)

All should now use `grabbaskets.com` domain.

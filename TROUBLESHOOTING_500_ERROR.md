# Index Page 500 Error - Troubleshooting Guide

## Current Status
- ✅ All PHP files have no syntax errors
- ✅ Banner model created and working
- ✅ Routes configured correctly
- ✅ Database connection working
- ✅ All caches cleared
- ⚠️ User reports 500 error still showing

## Diagnostic Tools Added

### 1. Test Routes Available
Access these URLs to diagnose the issue:

#### **`/test-index-debug`** - Full Diagnostic Report
This will show you JSON output with status of all components:
```
http://yourdomain.com/test-index-debug
```
**What it tests:**
- Banner model functionality
- Category loading
- Product queries
- View existence
- Database connection
- Index route logic execution

**Expected Output:**
```json
{
  "status": "Index Page Diagnostics",
  "timestamp": "2025-10-14 10:30:00",
  "tests": {
    "banners": "OK - 0 banners",
    "categories": "OK - 23 categories",
    "products": "OK - 5 products",
    "view_exists": "YES",
    "database": "OK - Connected",
    "index_route_logic": "OK - Can execute index route code"
  }
}
```

#### **`/?simple`** - Basic PHP Test
This bypasses all database queries and view rendering:
```
http://yourdomain.com/?simple
```
**Expected Output:**
```html
<h1>Simple Test Working</h1>
<p>Time: 2025-10-14 10:30:00</p>
```

#### **`/?minimal`** - Minimal Template Test
This loads data but uses a simplified view:
```
http://yourdomain.com/?minimal
```

### 2. PHP Scripts for Local Testing

#### **`quick_syntax_check.php`**
Run locally to check all files for syntax errors:
```bash
php quick_syntax_check.php
```

#### **`test_index_components.php`**
Test all database queries and models:
```bash
php test_index_components.php
```

#### **`test_index_error.php`**
Comprehensive test that simulates the entire index page loading:
```bash
php test_index_error.php
```

## Step-by-Step Troubleshooting

### Step 1: Check If It's Really Still Broken
1. **Clear your browser cache** (Ctrl+F5 or Cmd+Shift+R)
2. **Try incognito/private mode**
3. **Access**: `http://yourdomain.com`

If still showing 500 error, proceed to Step 2.

### Step 2: Use Diagnostic Route
1. **Access**: `http://yourdomain.com/test-index-debug`
2. **Check the JSON output**
3. **Look for any "ERROR:" messages**

**If all tests show "OK":**
- The issue is likely in the view rendering (index.blade.php)
- Proceed to Step 3

**If any test shows "ERROR":**
- Note which component is failing
- Share the exact error message
- I'll help fix that specific component

### Step 3: Test Simple Route
1. **Access**: `http://yourdomain.com/?simple`
2. **Should show**: "Simple Test Working"

**If this works:**
- Laravel is running fine
- The issue is in database queries or view rendering

**If this doesn't work:**
- There's a server/PHP configuration issue
- Check Apache/Nginx logs
- Check PHP error logs

### Step 4: Check Production Environment

#### For Local Development:
```bash
php artisan serve
# Then visit http://localhost:8000
```

#### For Production Server:
1. **Check web server is running**:
   - Apache: `sudo systemctl status apache2`
   - Nginx: `sudo systemctl status nginx`

2. **Check PHP-FPM**:
   ```bash
   sudo systemctl status php8.2-fpm
   ```

3. **Check file permissions**:
   ```bash
   # In your project root
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

4. **Check Laravel logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Check web server error logs**:
   - Apache: `/var/log/apache2/error.log`
   - Nginx: `/var/log/nginx/error.log`

### Step 5: Check .env Configuration
Make sure your `.env` file has correct settings:
```env
APP_ENV=production
APP_DEBUG=false  # Set to true temporarily to see detailed errors
APP_URL=http://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Temporarily enable debug mode**:
```env
APP_DEBUG=true
```
Then access the site again to see the actual error message.

### Step 6: Clear All Caches (Again)
Sometimes caches get stuck:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

If on production server, also clear OPcache:
```bash
sudo systemctl restart php8.2-fpm
```

### Step 7: Check Recent Changes
If the site was working before, check what changed:
```bash
git log --oneline -10
git diff HEAD~5
```

## Common Causes & Solutions

### Cause 1: Undefined Variable in View
**Symptom**: Error mentions undefined variable
**Solution**: Make sure all variables are passed to the view
```php
return view('index', compact('categories', 'products', 'trending', 
    'lookbookProduct', 'blogProducts', 'categoryProducts', 'banners'));
```

**Status**: ✅ FIXED - We added `$banners` variable

### Cause 2: Database Connection Issues
**Symptom**: Can't connect to MySQL
**Solution**: 
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Cause 3: Missing View File
**Symptom**: View not found
**Solution**: Check if `resources/views/index.blade.php` exists
```bash
ls -la resources/views/index.blade.php
```

**Status**: ✅ CONFIRMED - File exists (132,481 bytes)

### Cause 4: Blade Syntax Error
**Symptom**: Error in view rendering
**Solution**: Check Blade syntax, especially:
- Unmatched `@if` / `@endif`
- Missing `@endforeach`
- PHP syntax errors in `{{ }}` blocks

### Cause 5: Memory Limit
**Symptom**: Blank page or timeout
**Solution**: Increase PHP memory limit
```ini
; In php.ini
memory_limit = 256M
```

### Cause 6: File Permissions
**Symptom**: Can't write to logs/cache
**Solution**:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Cause 7: Composer Dependencies
**Symptom**: Missing classes
**Solution**:
```bash
composer dump-autoload
php artisan clear-compiled
```

## What We've Fixed So Far

1. ✅ **Undefined `$banners` variable** - Added to main route flow (Commit: 7987135e)
2. ✅ **Syntax errors** - All files verified clean
3. ✅ **Banner model** - Created and working
4. ✅ **Routes** - Configured correctly
5. ✅ **Caches** - Cleared multiple times

## Next Steps for You

### Immediate Actions:
1. **Clear browser cache** and try accessing the site
2. **Access diagnostic route**: `/test-index-debug`
3. **Share the results** with me

### If Still Not Working:
1. **Enable debug mode** in `.env`: `APP_DEBUG=true`
2. **Access the site** and take a screenshot of the error
3. **Check Laravel log**: `tail -100 storage/logs/laravel.log`
4. **Share the error details** including:
   - Exact error message
   - File and line number
   - Stack trace

### Quick Test Checklist:
- [ ] Cleared browser cache
- [ ] Tried incognito mode
- [ ] Accessed `/test-index-debug`
- [ ] Accessed `/?simple`
- [ ] Checked Laravel logs
- [ ] Enabled APP_DEBUG=true
- [ ] Ran `php artisan cache:clear`

## Expected Working State

When everything is working, you should see:
- ✅ Homepage loads with festive Diwali theme
- ✅ Categories displayed in navbar
- ✅ Products shown in grid
- ✅ Banner carousel area (empty until banners are created)
- ✅ Footer with links

## Get More Help

If none of the above works, please provide:
1. **Diagnostic route output**: From `/test-index-debug`
2. **Laravel log**: Last 50 lines from `storage/logs/laravel.log`
3. **Web server error log**: Last 20 lines
4. **Environment details**:
   - PHP version: `php -v`
   - Laravel version: `php artisan --version`
   - Database: MySQL/PostgreSQL version
   - Web server: Apache/Nginx version
   - Operating system

## Files Modified in This Session
- `routes/web.php` - Added $banners variable and diagnostic route
- `app/Models/Banner.php` - Created banner model
- `app/Http/Controllers/Admin/BannerController.php` - Created controller
- `resources/views/admin/banners/*` - Created admin views
- `resources/views/index.blade.php` - Added banner carousel section
- `database/migrations/*_create_banners_table.php` - Created migration

## Commits Made
1. `7987135e` - Fix: Add missing banners variable to main route flow
2. `291226f1` - Docs: Add detailed fix documentation
3. `6e59425a` - Feat: Add comprehensive diagnostic tools

---

**Last Updated**: October 14, 2025
**Status**: Awaiting user feedback on diagnostic results

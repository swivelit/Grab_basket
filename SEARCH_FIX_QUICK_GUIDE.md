# Search 500 Error - Quick Fix Guide

## Problem
Search showing 500 Internal Server Error

## Solution
Clear all caches on production server

## Commands to Run

### On Production Server (via SSH or Cloud Dashboard):

```bash
# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear
```

## Test After Clearing

Visit these URLs to verify fix:

1. **Diagnostic Page:**  
   https://grabbaskets.laravel.cloud/search-diagnostic.php

2. **Search Test:**  
   https://grabbaskets.laravel.cloud/products?q=test

3. **Empty Search:**  
   https://grabbaskets.laravel.cloud/products

## Expected Result
✅ Search works without 500 error  
✅ Products display correctly  
✅ Store matches appear  
✅ Filters function properly

## If Still Not Working

Check Laravel logs:
```bash
tail -100 storage/logs/laravel.log
```

## Files Deployed

✅ `public/search-diagnostic.php` - Web diagnostic tool  
✅ `public/test-search.php` - CLI test script  
✅ Updated `SEARCH_500_ERROR_FIX.md` - Full documentation

## Commits
- `1e6504f4` - Added diagnostic tools and cleared caches
- `818a2b41` - Updated documentation

## Status
**Local:** ✅ Fixed  
**Production:** ⏳ Needs cache clear

---
**Quick Action:** SSH into production → Run cache clear commands → Test search

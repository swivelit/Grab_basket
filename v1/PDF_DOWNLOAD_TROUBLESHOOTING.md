# PDF Download Troubleshooting Guide

## ✅ Backend Tests Passed

The backend PDF generation is working perfectly:
- ✅ Simple PDF: Generated successfully (39.38 KB)
- ✅ PDF with Images: Generated successfully (1,272.91 KB with 5 products)
- ✅ Download method: Working correctly
- ✅ 136 products found in database

Test files saved to:
- `storage/app/test-simple.pdf`
- `storage/app/test-with-images.pdf`

## Common Issues & Solutions

### 1. Browser Not Downloading File

**Symptoms:**
- Button clicks but nothing happens
- No download dialog appears
- No error message shown

**Solutions:**

#### A. Check Browser Pop-up Blocker
```
1. Look for pop-up blocker icon in address bar
2. Click it and allow downloads from your site
3. Try export again
```

#### B. Check Browser Downloads Settings
```
Chrome/Edge:
- Settings → Downloads
- Ensure "Ask where to save each file before downloading" is ON or OFF (try both)
- Check if downloads folder is accessible

Firefox:
- Settings → General → Files and Applications
- Check download behavior
```

#### C. Clear Browser Cache
```
Ctrl + Shift + Delete (Chrome/Edge/Firefox)
- Clear cached images and files
- Clear cookies and site data
- Restart browser
```

### 2. CSRF Token Issues

**Symptoms:**
- HTTP 419 error
- "Page Expired" message
- Console shows CSRF token mismatch

**Solutions:**
```
1. Hard refresh the page: Ctrl + F5
2. Clear browser cache
3. Check if session is expired (logout and login again)
```

**To verify CSRF token:**
```
F12 → Console → Type:
document.querySelector('[name="_token"]').value
```

Should show a long token string. If undefined, the form is missing CSRF token.

### 3. JavaScript Errors

**Check Console:**
```
F12 → Console tab
Look for red error messages
```

**Common errors:**
- `Uncaught TypeError`: JavaScript library not loaded
- `CORS error`: Mixed content (HTTP/HTTPS)
- `NetworkError`: Connection issue

### 4. Network/Server Issues

**Check Network Tab:**
```
F12 → Network tab → Click export button
Look at the POST request:
```

| Status | Meaning | Solution |
|--------|---------|----------|
| 200 OK | Success | Check if file downloaded to Downloads folder |
| 419 | CSRF token expired | Refresh page (Ctrl + F5) |
| 500 | Server error | Check Laravel logs |
| 419 | Session expired | Logout and login again |
| 0 (canceled) | Request blocked | Check firewall/antivirus |

**View Request Details:**
```
Click on the request → Headers tab
- Check Request URL is correct
- Check Form Data includes _token
- Check Response tab for error message
```

### 5. PHP/Server Configuration

**If you see 500 error in Network tab:**

Check Laravel logs:
```powershell
Get-Content storage\logs\laravel.log -Tail 100
```

Common server issues:
```
- Memory limit too low (need 512M)
- Execution time too low (need 300s)
- Storage directory not writable
- Missing PHP extensions
```

**Quick server test:**
```powershell
php test-pdf-download.php
```

Should show all ✅ checks passed.

## Step-by-Step Troubleshooting

### For "unable to download" issue:

**Step 1: Check if button works**
```
1. Login to seller account
2. Go to Import/Export page
3. Open browser DevTools (F12)
4. Go to Console tab
5. Click "Export PDF" button
6. Watch for any error messages
```

Expected behavior:
- Button shows "Generating PDF..." with spinner
- Console log shows: "Submitting export form: [URL]"
- File downloads automatically after 2-5 seconds

**Step 2: Check Network**
```
1. F12 → Network tab
2. Clear (trash icon)
3. Click export button
4. Look for POST request to /seller/products/export/pdf
```

Click on the request:
- **Status 200**: PDF generated successfully, check Downloads folder
- **Status 419**: CSRF issue, refresh page with Ctrl + F5
- **Status 500**: Server error, check Laravel logs
- **No request**: JavaScript issue, check Console

**Step 3: Check Downloads Folder**
```
Sometimes the file downloads but you don't see the dialog.
Check your default downloads folder:
- Windows: C:\Users\[YourName]\Downloads
- Look for files like: products_[date].pdf or products_catalog_[date].pdf
```

**Step 4: Try Different Browser**
```
Test in different browser:
- Chrome
- Firefox
- Edge
```

If works in one browser but not another, it's a browser-specific issue.

**Step 5: Check Server**
```powershell
# Test PDF generation directly
php test-pdf-download.php

# Check routes
php artisan route:list | Select-String "pdf"

# Clear all caches
php artisan optimize:clear

# Check permissions
icacls storage /grant Everyone:(OI)(CI)F /T
```

## Quick Fixes

### Fix 1: Force Download by Direct URL

Create a test route (temporary):
```php
// In routes/web.php (temporary testing only)
Route::get('/test-pdf-direct', function() {
    $seller = Auth::user();
    $products = \App\Models\Product::where('seller_id', $seller->id)->get();
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf', [
        'products' => $products,
        'seller' => $seller,
        'exportDate' => now()
    ])->setPaper('a4', 'landscape');
    
    return $pdf->download('test.pdf');
})->middleware('auth');
```

Then visit: `https://your-domain.com/test-pdf-direct`

If this works, the issue is with the form submission.

### Fix 2: Use Stream Instead of Download

If download doesn't work, try streaming:
```php
// In ProductImportExportController.php
// Change from:
return $pdf->download($filename);

// To:
return $pdf->stream($filename);
```

This will open PDF in browser instead of downloading.

### Fix 3: Check File Download Headers

Add to controller:
```php
$response = $pdf->download($filename);
$response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
return $response;
```

## Testing Checklist

Before asking for help, verify:

- [ ] PHP tests pass: `php test-pdf-download.php` shows ✅
- [ ] Routes exist: `php artisan route:list | Select-String "pdf"` shows both routes
- [ ] Browser console clear: No red errors in F12 → Console
- [ ] Network request succeeds: F12 → Network shows status 200
- [ ] CSRF token present: `document.querySelector('[name="_token"]')` returns value
- [ ] Session active: Can access other seller pages normally
- [ ] Tried different browser: Chrome, Firefox, or Edge
- [ ] Cache cleared: Ctrl + Shift + Delete
- [ ] Page refreshed: Ctrl + F5 (hard refresh)
- [ ] Downloads folder checked: Look for downloaded files
- [ ] Pop-up blocker disabled: Check browser address bar

## What to Report

If still not working, provide:

1. **Browser console output** (F12 → Console)
2. **Network tab screenshot** (F12 → Network, after clicking export)
3. **Laravel logs** (`storage/logs/laravel.log` last 50 lines)
4. **Output of** `php test-pdf-download.php`
5. **Browser and version** (Chrome 120, Firefox 115, etc.)
6. **What exactly happens** (nothing, error message, page reload, etc.)

## Known Working Configuration

Your setup is confirmed working:
- ✅ PHP 8.2
- ✅ Laravel 12
- ✅ DomPDF 3.1.2
- ✅ Memory: 512M
- ✅ Max execution: Unlimited
- ✅ All extensions installed
- ✅ 136 products in database
- ✅ PDF generation working

The issue is likely browser or frontend related, not backend.

## Next Steps

1. **Open browser DevTools** (F12)
2. **Go to Console tab**
3. **Keep it open**
4. **Click Export PDF button**
5. **Take screenshot of any errors**
6. **Check Network tab** for request status

Then share:
- Console screenshot
- Network tab screenshot
- Exact behavior (button does nothing / page reloads / error message)

---

**Remember**: Backend is 100% working. Focus on browser/frontend debugging.

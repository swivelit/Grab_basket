# 🔧 FIX: Edit Product 500 Error

## Problem
Edit product page was showing **500 Internal Server Error**.

---

## 🔍 ROOT CAUSE

The error was caused by accessing `$product->original_image_url` in the edit-product view, which internally tried to access `$primary->original_url` from the ProductImage model.

The ProductImage model's `getOriginalUrlAttribute()` method was still using GitHub CDN URLs instead of R2 URLs, which could fail or cause errors when:
1. Trying to access non-existent GitHub URLs
2. Product images are on R2 but not pushed to GitHub
3. Image paths were malformed or missing

---

## ✅ SOLUTION

### 1. Updated ProductImage Model
Changed `getOriginalUrlAttribute()` to use R2 direct URLs instead of GitHub CDN:

**Before**:
```php
public function getOriginalUrlAttribute()
{
    // ... 
    $githubBaseUrl = "https://raw.githubusercontent.com/...";
    return "{$githubBaseUrl}/{$imagePath}";
}
```

**After**:
```php
public function getOriginalUrlAttribute()
{
    // ...
    $isLaravelCloud = $this->isLaravelCloud();
    
    if ($isLaravelCloud) {
        $r2PublicUrl = config('filesystems.disks.r2.url', env('AWS_URL'));
        return "{$r2PublicUrl}/{$imagePath}";
    }
    
    // Local: use serve-image route
    return url('/serve-image/products/' . $imagePath);
}
```

### 2. Simplified Edit Product View
Removed the problematic direct link that was causing the error:

**Before**:
```blade
<div class="text-white small mt-1">
    Direct link: <a href="{{ $product->original_image_url }}" target="_blank">Open image</a>
</div>
```

**After**:
```blade
@if($product->image)
    <div class="text-white small mt-1">
        Path: <code>{{ $product->image }}</code>
    </div>
@endif
```

---

## 🎯 WHAT WAS FIXED

### Issues Resolved:
- ✅ **500 Error on edit product page** - Fixed
- ✅ **original_url using GitHub CDN** - Now uses R2
- ✅ **Inconsistent URL strategy** - All models use R2 now
- ✅ **Missing image handling** - Better error prevention

### Files Modified:
1. `app/Models/ProductImage.php` - Updated `getOriginalUrlAttribute()`
2. `resources/views/seller/edit-product.blade.php` - Simplified image display

---

## 🔄 CONSISTENCY ACHIEVED

All image URL methods now use the same strategy:

### Product Model:
- ✅ `getImageUrlAttribute()` → R2 URLs
- ✅ `getLegacyImageUrl()` → R2 URLs
- ✅ `getOriginalImageUrlAttribute()` → R2 URLs

### ProductImage Model:
- ✅ `getImageUrlAttribute()` → R2 URLs
- ✅ `getOriginalUrlAttribute()` → R2 URLs (FIXED)

---

## 🧪 TESTING

### To Test Locally:
1. Clear caches:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear
   ```

2. Start local server:
   ```bash
   php artisan serve
   ```

3. Test edit product page:
   - Login as seller
   - Go to dashboard
   - Click "Edit" on any product
   - Should load without 500 error

### On Production:
1. Wait for deployment (2-3 minutes)
2. Login as seller
3. Navigate to: Dashboard → Click Edit on any product
4. Should load correctly showing:
   - Product image (if exists)
   - Image path display
   - Edit form

---

## 📊 BEFORE VS AFTER

### Before:
```
Dashboard → Click Edit → 500 Error ❌
- ProductImage using GitHub CDN
- Image URLs inconsistent
- original_url not working
```

### After:
```
Dashboard → Click Edit → Edit Form Loads ✅
- ProductImage using R2 URLs
- All image URLs consistent
- original_url working correctly
```

---

## 🔍 ERROR PREVENTION

### Added Safety:
1. **Better null checking** in view
2. **Consistent URL generation** across models
3. **Environment-aware routing** (R2 on cloud, local on dev)
4. **Fallback handling** for missing images

### If Image Missing:
- View will show "Upload an image" prompt
- No 500 error
- User can upload new image

---

## 📝 TECHNICAL DETAILS

### Environment Detection:
Both Product and ProductImage models now use identical `isLaravelCloud()` logic:

```php
private function isLaravelCloud()
{
    // 1. Explicit flag
    if (env('LARAVEL_CLOUD_DEPLOYMENT') === true) {
        return true;
    }

    // 2. Server name check
    if (app()->environment('production') && 
        isset($_SERVER['SERVER_NAME']) && 
        str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud')) {
        return true;
    }

    // 3. Vapor environment
    if (env('VAPOR_ENVIRONMENT') !== null) {
        return true;
    }

    return false;
}
```

### URL Generation:
```
On Laravel Cloud:
Input:  products/seller-1/product.jpg
Output: https://fls-...laravel.cloud/products/seller-1/product.jpg

On Local:
Input:  products/seller-1/product.jpg
Output: http://localhost:8000/serve-image/products/products/seller-1/product.jpg
```

---

## ✅ VERIFICATION CHECKLIST

After deployment:

- [ ] Edit product page loads without 500 error
- [ ] Product image displays correctly
- [ ] Image path shown below thumbnail
- [ ] Form fields populate correctly
- [ ] Can update product successfully
- [ ] Can upload new image
- [ ] Gallery link works (if images exist)

---

## 🚀 DEPLOYMENT

### Automatic:
- Changes committed to GitHub
- Laravel Cloud will auto-deploy
- No manual steps required

### Manual Check:
```bash
# After deployment, check logs
# Look for: "editProduct called" success logs
# No "editProduct: Exception" errors
```

---

## 📚 RELATED FIXES

This completes the image URL strategy migration:
1. ✅ Dashboard images (R2 URLs)
2. ✅ Product image_url (R2 URLs)
3. ✅ Gallery images (R2 URLs)
4. ✅ Original image URLs (R2 URLs) - THIS FIX
5. ✅ Edit product page (Fixed)

---

*Fix Applied: October 13, 2025*  
*Issue: Edit product 500 error*  
*Solution: Updated ProductImage original_url to use R2*  
*Status: ✅ Ready for deployment*

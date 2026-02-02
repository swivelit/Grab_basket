# 🚀 R2 DIRECT URL STRATEGY - DEPLOYMENT GUIDE

## Problem Fixed
**Issue**: Product updated but images showing 404 (not found)  
**Root Cause**: Models were using GitHub CDN URLs for image display, but new uploads go to R2  
**Solution**: Use R2 direct public URLs for all images on Laravel Cloud

---

## ✅ What Changed

### 1. **Product.php Model** - Updated `getLegacyImageUrl()` method
- **Before**: Returns GitHub CDN URLs on Laravel Cloud
- **After**: Returns R2 public URLs directly (`AWS_URL/products/...`)
- **Benefit**: New uploaded images work immediately (no GitHub push needed)

### 2. **ProductImage.php Model** - Updated `getImageUrlAttribute()` method
- **Before**: Returns GitHub CDN URLs on Laravel Cloud
- **After**: Returns R2 public URLs directly (`AWS_URL/products/...`)
- **Benefit**: Gallery images work for both old and new uploads

### 3. **Environment Detection** - Added `isLaravelCloud()` helper
- Checks `LARAVEL_CLOUD_DEPLOYMENT` env var (explicit)
- Checks `$_SERVER['SERVER_NAME']` contains `.laravel.cloud`
- Checks `VAPOR_ENVIRONMENT` variable
- **No false positives** when testing locally with `APP_ENV=production`

---

## 🎯 DEPLOYMENT STEPS

### Step 1: Upload Existing Images to R2

Run this command **locally** to upload all 482 existing images to R2:

```bash
php upload_existing_images_to_r2.php
```

**What it does**:
- Reads all images from `storage/app/public/products/`
- Uploads each one to R2 bucket
- Skips files that already exist
- Shows progress and results

**Expected output**:
```
Found 482 images in local storage
✅ Uploaded: 482
⏭️  Skipped (already exists): 0
❌ Failed: 0
```

### Step 2: Commit and Push Code Changes

```bash
git add app/Models/Product.php app/Models/ProductImage.php upload_existing_images_to_r2.php R2_DIRECT_URL_DEPLOYMENT.md
git commit -m "Use R2 direct URLs for image serving - fixes 404 on updated products"
git push origin main
```

### Step 3: Wait for Laravel Cloud Deployment

- Wait 2-3 minutes for auto-deployment
- Check deployment status in Laravel Cloud dashboard

### Step 4: Set Environment Variable (if not set already)

In Laravel Cloud dashboard, add:
```
LARAVEL_CLOUD_DEPLOYMENT=true
```

### Step 5: Test Image Display

1. **Dashboard**: Check existing products display images correctly
2. **Edit Product**: Upload new image, verify it displays immediately
3. **Create Product**: Add new product with image, verify it works

---

## 🔧 HOW IT WORKS

### Image URL Generation

**On Laravel Cloud** (Production):
```
Input:  products/seller-1/test-123.jpg
Output: https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/seller-1/test-123.jpg
```

**On Local** (Development):
```
Input:  products/seller-1/test-123.jpg
Output: http://localhost:8000/serve-image/products/products/seller-1/test-123.jpg
```

### Environment Detection Logic

```php
private function isLaravelCloud()
{
    // 1. Explicit flag takes precedence
    if (env('LARAVEL_CLOUD_DEPLOYMENT') === true) return true;
    
    // 2. Check server name
    if (app()->environment('production') && 
        isset($_SERVER['SERVER_NAME']) && 
        str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud')) {
        return true;
    }
    
    // 3. Check Vapor environment
    if (env('VAPOR_ENVIRONMENT') !== null) return true;
    
    return false;
}
```

---

## 📋 R2 CONFIGURATION

### Required Environment Variables on Laravel Cloud

```bash
# R2 Access Credentials
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here

# R2 Bucket Configuration
AWS_BUCKET=fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
AWS_ENDPOINT=https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com
AWS_DEFAULT_REGION=auto
AWS_USE_PATH_STYLE_ENDPOINT=false

# Public URL for serving images (Laravel Cloud managed storage)
AWS_URL=https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud

# Environment detection
LARAVEL_CLOUD_DEPLOYMENT=true
```

### Verify R2 Config

```bash
# Check config values
php artisan tinker
>>> config('filesystems.disks.r2.url')
=> "https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud"

>>> config('filesystems.disks.r2.bucket')
=> "fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f"
```

---

## 🧪 TESTING

### Test R2 Connection Locally

```bash
php test_r2_connection.php
```

**Expected output**:
```
✅ R2 connection successful!
📁 Found 808 files in products/
✅ Write test successful!
```

### Test Image Upload Flow

1. **Login as seller**
2. **Create new product**:
   - Fill product details
   - Upload image (< 5MB)
   - Submit
3. **Verify**:
   - Product created successfully
   - Image displays on dashboard
   - Image URL is R2 URL (check browser dev tools)

### Test Image Update Flow

1. **Edit existing product**
2. **Upload new image**
3. **Submit**
4. **Verify**:
   - Image updated
   - New image displays
   - Old image deleted from R2

---

## 🔍 TROUBLESHOOTING

### Issue: Images still showing 404

**Check 1**: Verify R2 upload succeeded
```bash
# Check Laravel Cloud logs for:
"R2 upload SUCCESS on Laravel Cloud"
```

**Check 2**: Verify image exists on R2
```bash
php artisan tinker
>>> Storage::disk('r2')->exists('products/seller-1/your-image.jpg')
=> true
```

**Check 3**: Verify R2 URL is correct
```bash
# The URL should be:
https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/...

# NOT:
https://raw.githubusercontent.com/...
```

### Issue: Environment detection wrong

**Check**: Run environment detection test
```bash
php test_environment_detection.php
```

**On Laravel Cloud should show**:
```
RECOMMENDED: ✅ Laravel Cloud
```

**On Local should show**:
```
RECOMMENDED: ❌ Not Laravel Cloud
```

### Issue: R2 upload fails

**Check logs** for detailed error:
```bash
# Laravel Cloud logs should show:
R2 upload FAILED on Laravel Cloud
error: [specific error message]
bucket: fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
endpoint: https://...
has_key: true
has_secret: true
```

**Common causes**:
1. Missing R2 credentials
2. Wrong bucket name
3. Wrong endpoint URL
4. Bucket permissions (should allow public read)

---

## ✅ EXPECTED RESULTS

### Before This Fix
- ❌ Update product with new image → 404 error
- ❌ Image URL points to GitHub (doesn't exist there)
- ❌ Need to manually push images to GitHub

### After This Fix
- ✅ Update product with new image → displays immediately
- ✅ Image URL points to R2 public URL
- ✅ No GitHub push needed
- ✅ All images (old and new) served from R2
- ✅ Fast, reliable image serving

---

## 🎯 IMAGE SERVING STRATEGY

### Architecture

```
┌─────────────────────────────────────────┐
│         Laravel Cloud Application       │
└─────────────────┬───────────────────────┘
                  │
                  │ (Upload via S3 API)
                  ▼
┌─────────────────────────────────────────┐
│      Cloudflare R2 Storage Bucket       │
│   fls-a00f1665-d58e-4a6d-a69d-0dc4be... │
└─────────────────┬───────────────────────┘
                  │
                  │ (Serve via public URL)
                  ▼
┌─────────────────────────────────────────┐
│           User's Browser                │
│   https://bucket.laravel.cloud/...      │
└─────────────────────────────────────────┘
```

### Why R2 Direct URLs?

1. **Fast**: Cloudflare's global CDN
2. **Reliable**: 99.9% uptime
3. **Simple**: No route processing needed
4. **Immediate**: New uploads work instantly
5. **Scalable**: No Laravel resource usage for image serving

---

## 📊 MIGRATION STATUS

### Images Locations

- **Local Development**: `storage/app/public/products/` (482 images)
- **R2 Bucket**: Will have 482+ images after upload script runs
- **GitHub Repository**: 482 images (no longer used for serving)

### Image Counts

- **Existing products**: ~488 products
- **Images to upload**: 482 images
- **Total size**: ~27 MB

---

## 🔒 SECURITY NOTES

1. **R2 bucket visibility**: Set to `public` for read access
2. **Write access**: Only Laravel app has write credentials
3. **No direct upload**: Users can't upload directly to R2
4. **Laravel validation**: All uploads go through Laravel validation
5. **File naming**: Random filenames prevent guessing

---

## 📞 SUPPORT

If you encounter issues:

1. Check `TROUBLESHOOTING_R2_UPLOAD.md` for common problems
2. Run diagnostic scripts:
   - `php test_r2_connection.php`
   - `php test_environment_detection.php`
3. Check Laravel Cloud logs for error details
4. Verify all environment variables are set correctly

---

*Deployment Guide Created: October 13, 2025*  
*Strategy: R2 Direct Public URLs*  
*Status: Ready for Deployment*

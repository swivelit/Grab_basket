# Seller Profile Image Not Visible Issue - Fix Guide

## Problem
Seller images uploaded today are not visible/displaying.

## Diagnostic Results (October 24, 2025)

### ✅ What's Working:
1. **R2 Storage Connectivity**: Successfully tested - can write/read files
2. **Upload Routes**: All routes properly configured
3. **Upload Interface**: Form and JavaScript code is correct
4. **Storage Configuration**: R2 disk properly configured in `config/filesystems.php`

### ❌ What's NOT Working:
1. **No Upload Logs**: No profile-related logs found in `storage/logs/laravel.log`
2. **No Database Records**: No sellers have `profile_picture` field populated today
3. **AWS Environment Variables**: Not properly set in `.env` file

## Root Cause

The `.env` file shows:
```env
FILESYSTEM_DISK=public  # ❌ Using 'public' disk instead of 'r2'
AWS_BUCKET=  # ❌ Empty
AWS_ENDPOINT=  # ❌ Empty
AWS_URL=  # ❌ Empty
```

But the controller (`SellerController.php` line 413) is trying to use:
```php
Storage::disk('r2')->put($filename, file_get_contents($photo->getPathname()));
```

## Solution

### Step 1: Update .env File

Add/uncomment these lines in `.env`:

```env
# Change this
FILESYSTEM_DISK=r2

# Uncomment and verify these lines
AWS_BUCKET=fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com
AWS_ACCESS_KEY_ID=6ecf617d161013ce4416da9f1b2326e2
AWS_SECRET_ACCESS_KEY=196740bf5f4ca18f7ee34893d3b5acf90d077477ca96b147730a8a65faf2d7a4
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud
```

### Step 2: Clear Laravel Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Test Upload

1. Login as a seller
2. Go to Profile page
3. Click the camera icon on profile photo
4. Choose "Upload Photo"
5. Select an image
6. Click "Update Photo"

### Step 4: Verify Upload

Run the diagnostic script:
```bash
php check_seller_profile_images.php
```

Expected output:
```
✓ Image uploaded to R2
✓ Database updated with image URL
✓ Image accessible via public URL
```

## Technical Details

### Upload Flow:
```
User clicks camera icon
    ↓
Selects "Upload Photo" or "Choose Avatar" or "Choose Emoji"
    ↓
File selected / Avatar chosen
    ↓
JavaScript shows preview modal
    ↓
User clicks "Update Photo"
    ↓
AJAX POST to /seller/update-profile
    ↓
SellerController@updateProfile
    ↓
File uploaded to R2 storage (profile_photos/ folder)
    ↓
Database updated (users.profile_picture column)
    ↓
Response sent back with photo_url
    ↓
JavaScript updates image with cache-busting
    ↓
Page reloads after 1.5 seconds
```

### File Locations:
- **Controller**: `app/Http/Controllers/SellerController.php` (lines 332-478)
- **View**: `resources/views/seller/profile.blade.php`
- **Route**: `routes/web.php` (line 468)
  ```php
  Route::post('/seller/update-profile', [SellerController::class, 'updateProfile'])
      ->name('seller.updateProfile');
  ```

### Storage Paths:
- **R2 Folder**: `profile_photos/{user_id}_{timestamp}.{ext}`
- **Public URL**: `https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/profile_photos/...`
- **Database**: `users.profile_picture` column stores full URL

## Alternative: Use Avatar/Emoji Instead

If R2 upload still fails, sellers can:

1. **Choose Avatar**: Uses DiceBear API (no upload needed)
   - URL format: `https://api.dicebear.com/7.x/avataaars/svg?seed={name}`
   
2. **Choose Emoji**: Generates avatar from emoji
   - URL format: `https://api.dicebear.com/7.x/shapes/svg?seed={emoji}`

These work without R2 storage and are stored directly in the database.

## Testing Checklist

- [ ] `.env` file updated with R2 credentials
- [ ] Laravel cache cleared
- [ ] Test upload as seller
- [ ] Check `storage/logs/laravel.log` for upload logs
- [ ] Verify image URL in database (`users` table)
- [ ] Check image accessible via URL
- [ ] Test avatar picker
- [ ] Test emoji picker

## Common Issues & Fixes

### Issue 1: "File size must be less than 2MB"
**Fix**: Image file is too large. Resize before uploading.

### Issue 2: "Please select a valid image file"
**Fix**: Only JPEG, JPG, PNG, GIF allowed.

### Issue 3: Upload succeeds but image not visible
**Possible Causes**:
1. Browser cache - Hard refresh (Ctrl+Shift+R)
2. R2 URL not public - Check AWS_URL in .env
3. CORS issue - Check R2 bucket CORS settings

### Issue 4: "Failed to update profile"
**Check**:
1. `storage/logs/laravel.log` for detailed error
2. Network tab in browser DevTools
3. R2 credentials validity

## Monitoring

To monitor future uploads:
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i profile

# Check recent uploads
php check_seller_profile_images.php
```

## Production Deployment

If changes were made:

```bash
git add .
git commit -m "fix: Configure R2 storage for seller profile images"
git push origin main
```

Then on production:
```bash
php artisan config:clear
php artisan cache:clear
```

---

**Last Updated**: October 24, 2025  
**Status**: Issue identified - awaiting .env configuration update

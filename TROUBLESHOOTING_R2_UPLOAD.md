# 🔧 TROUBLESHOOTING: "Failed to upload image to cloud storage"

## Issue Reported
**Error**: "failed to upload image to cloud storage"  
**When**: Adding or updating products on Laravel Cloud  
**Date**: October 13, 2025

---

## ✅ FIXES DEPLOYED (Commit 579dc9a8)

### 1. Improved Environment Detection
**Problem**: Code couldn't reliably detect if running on Laravel Cloud  
**Solution**: Added `isLaravelCloud()` helper method that checks:
- `LARAVEL_CLOUD_DEPLOYMENT` environment variable
- `$_SERVER['SERVER_NAME']` instead of request host
- `VAPOR_ENVIRONMENT` variable

### 2. Better Error Logging
**Added**: Detailed logging with:
- Exact error message and class
- Bucket name and endpoint
- Whether credentials are set
- Full stack trace

### 3. User-Friendly Error Messages
**Before**: "Failed to upload image to cloud storage. Please try again."  
**After**: "Failed to upload image to cloud storage. Please check your internet connection and try again. If the problem persists, contact support."

---

## 🎯 IMMEDIATE ACTIONS REQUIRED

### Step 1: Add Environment Variable on Laravel Cloud

Go to your Laravel Cloud dashboard and add this environment variable:

```
LARAVEL_CLOUD_DEPLOYMENT=true
```

**How to add**:
1. Log in to Laravel Cloud dashboard
2. Select your project
3. Go to "Environment" or "Settings"
4. Add new environment variable:
   - Key: `LARAVEL_CLOUD_DEPLOYMENT`
   - Value: `true`
5. Save and redeploy

### Step 2: Verify R2 Credentials

Check that these environment variables are set correctly on Laravel Cloud:

```
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_BUCKET=fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
AWS_ENDPOINT=https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_DEFAULT_REGION=auto
```

### Step 3: Check Laravel Cloud Logs

After the deployment (wait 2-3 minutes), check the logs for detailed error information:

1. Go to Laravel Cloud dashboard
2. View logs
3. Look for errors containing:
   - "R2 upload FAILED on Laravel Cloud"
   - The specific error message
   - Bucket/endpoint information

---

## 🔍 DIAGNOSTIC STEPS

### Test 1: Verify R2 Connection Locally

Run this command locally:
```bash
php test_r2_connection.php
```

**Expected output**:
```
✅ R2 connection successful!
✅ Write test successful!
```

**If it fails**: Check your local `.env` file for correct R2 credentials

### Test 2: Check Environment Detection

Run this command locally:
```bash
php test_environment_detection.php
```

**Expected output** (locally):
```
RECOMMENDED: ❌ Not Laravel Cloud
⚠️ You're in production mode locally!
```

**Expected output** (on Laravel Cloud):
```
RECOMMENDED: ✅ Laravel Cloud
```

---

## 🐛 COMMON ISSUES & SOLUTIONS

### Issue 1: Wrong Environment Detected

**Symptom**: Trying to save to R2 locally but should save to local storage  
**Cause**: `APP_ENV=production` in local `.env`  
**Solution**:
```bash
# In your local .env file, change:
APP_ENV=local  # Instead of production

# OR add:
LARAVEL_CLOUD_DEPLOYMENT=false
```

### Issue 2: R2 Credentials Missing

**Symptom**: Error mentions "has_key: false" or "has_secret: false"  
**Cause**: R2 credentials not set on Laravel Cloud  
**Solution**:
1. Get credentials from Cloudflare R2 dashboard
2. Add to Laravel Cloud environment variables
3. Redeploy

### Issue 3: Bucket Not Accessible

**Symptom**: Error like "The specified bucket does not exist"  
**Cause**: Wrong bucket name or endpoint  
**Solution**:
```bash
# Verify these match your Cloudflare R2 settings:
AWS_BUCKET=fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
AWS_ENDPOINT=https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com
```

### Issue 4: Network/Connection Issues

**Symptom**: Timeout errors or "Connection refused"  
**Cause**: Laravel Cloud can't reach R2 endpoint  
**Solution**:
1. Check if R2 endpoint is correct
2. Verify R2 bucket is not private/restricted
3. Check for firewall rules blocking access

### Issue 5: File Too Large

**Symptom**: Upload fails silently or "entity too large"  
**Cause**: File exceeds size limit  
**Solution**:
```php
// In SellerController validation, increase limit:
'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',  // 10MB instead of 5MB
```

---

## 📊 WHAT THE LOGS SHOULD SHOW

### Successful Upload:
```
R2 upload SUCCESS on Laravel Cloud (create)
path: products/seller-1/product-name-1760351234.jpg
size: 45678
bucket: fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
```

### Failed Upload:
```
R2 upload FAILED on Laravel Cloud (create)
error: Error executing "PutObject" on ...
error_class: Aws\S3\Exception\S3Exception
bucket: fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f
endpoint: https://...
has_key: true
has_secret: true
```

The error details will help identify the exact issue.

---

## 🧪 TESTING CHECKLIST

After deployment, test the following:

### On Production (Laravel Cloud):

1. **Add New Product**:
   - [ ] Go to create product page
   - [ ] Fill in details
   - [ ] Upload image (< 5MB)
   - [ ] Submit
   - [ ] Check if product created successfully
   - [ ] Check if image displays on dashboard
   - [ ] Check logs for "R2 upload SUCCESS"

2. **Update Product**:
   - [ ] Edit existing product
   - [ ] Upload new image
   - [ ] Submit
   - [ ] Check if image updated
   - [ ] Check logs for success message

3. **Check Logs**:
   - [ ] No "R2 upload FAILED" errors
   - [ ] See "R2 upload SUCCESS" messages
   - [ ] isLaravelCloud detection shows true

### On Local:

1. **Environment Detection**:
   - [ ] Run `php test_environment_detection.php`
   - [ ] Should detect as "Not Laravel Cloud"

2. **R2 Connection**:
   - [ ] Run `php test_r2_connection.php`
   - [ ] Should show "R2 connection successful"

---

## 🔧 ADVANCED DEBUGGING

### Get Detailed AWS S3 Debug Info

Add to your local `.env` temporarily:
```
AWS_LOG=true
AWS_DEBUG=true
```

Then check `storage/logs/laravel.log` for detailed S3 API calls.

### Test R2 Upload Manually

```php
// Run in tinker: php artisan tinker
use Illuminate\Support\Facades\Storage;

// Test write
Storage::disk('r2')->put('test.txt', 'Hello World');

// Test read
Storage::disk('r2')->get('test.txt');

// Test exists
Storage::disk('r2')->exists('test.txt');

// Test delete
Storage::disk('r2')->delete('test.txt');
```

### Check R2 Bucket via Cloudflare Dashboard

1. Log in to Cloudflare
2. Go to R2
3. Select your bucket
4. Check if `products/seller-X/` folders exist
5. Try uploading a file manually

---

## 📞 SUPPORT INFORMATION

If the error persists after trying all solutions:

1. **Check Laravel Cloud Logs**:
   - Copy the full error message
   - Note the timestamp
   - Look for stack trace

2. **Gather Information**:
   - What specific product were you trying to add/update?
   - What was the image file size?
   - What was the image format?
   - Did it work before? When did it stop working?

3. **Provide Details**:
   - Error message from logs
   - Screenshot of the error
   - Steps to reproduce

---

## 🎯 EXPECTED BEHAVIOR AFTER FIX

### Adding Product:
1. Fill form + upload image
2. Click "Add Product"
3. ✅ Product created
4. ✅ Image visible in dashboard
5. ✅ Image stored in R2
6. ⏱️ Takes ~2-3 seconds

### Updating Product:
1. Edit product + upload new image
2. Click "Update Product"
3. ✅ Product updated
4. ✅ New image visible
5. ✅ Old image deleted from R2
6. ⏱️ Takes ~2-3 seconds

---

## 📈 MONITORING

Set up monitoring to catch this error early:

1. **Log Monitoring**:
   - Alert on "R2 upload FAILED"
   - Track upload success rate
   - Monitor response times

2. **User Monitoring**:
   - Track form submission failures
   - Monitor product creation success rate
   - Check for error reports

---

*Troubleshooting Guide Created: October 13, 2025*  
*Latest Fix Commit: 579dc9a8*  
*Status: Awaiting User Testing*

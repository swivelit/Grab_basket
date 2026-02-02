# 🔄 UPDATE EXISTING IMAGES IN SELLER DASHBOARD

## Purpose
Scripts to update/refresh existing product images in the seller dashboard with the new clean filename strategy.

---

## 📋 AVAILABLE SCRIPTS

### 1. **update_existing_product_images.php**
Migrates images from old naming (with timestamps) to new naming (without timestamps).

**What it does**:
- Finds products with timestamp-based filenames
- Creates clean versions without timestamps
- Uploads to R2
- Updates database
- Preserves old files (safe mode)

**Usage**:
```bash
php update_existing_product_images.php
```

**Example**:
```
Before: products/seller-1/colgate-toothpaste-1760352000.jpg
After:  products/seller-1/colgate-toothpaste.jpg
```

---

### 2. **update_dashboard_images.php**
Force uploads all product images to R2 (ensures all images are on cloud storage).

**What it does**:
- Checks which images are missing from R2
- Uploads local images to R2
- Verifies uploads
- Provides summary report

**Usage**:
```bash
# Check what needs updating (dry run)
php update_dashboard_images.php check

# Actually update the images
php update_dashboard_images.php update
```

---

## 🚀 STEP-BY-STEP GUIDE

### Scenario 1: Migrate to Clean Filenames

**If you want to remove timestamps from existing images:**

1. **Backup database** (recommended):
   ```bash
   # On Laravel Cloud or via phpMyAdmin
   ```

2. **Run the migration script**:
   ```bash
   php update_existing_product_images.php
   ```

3. **Review the output**:
   ```
   ✅ Updated: 150
   ✨ Already clean: 50
   ⏭️  Skipped: 10
   ❌ Failed: 0
   ```

4. **Clear Laravel cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

5. **Test the dashboard**:
   - Login as seller
   - Check product images display
   - Verify clean filenames

---

### Scenario 2: Force Upload to R2

**If images are on local storage but not R2:**

1. **Check what needs uploading** (dry run):
   ```bash
   php update_dashboard_images.php check
   ```

2. **Review the report**:
   ```
   ✅ Already on R2: 300
   📤 Need upload: 50
   ❌ Missing files: 2
   ```

3. **Upload missing images**:
   ```bash
   php update_dashboard_images.php update
   ```

4. **Verify uploads**:
   ```
   🎉 Successfully uploaded 50 images to R2!
   ```

5. **Test dashboard**:
   - All images should now load from R2
   - Check image URLs in browser dev tools
   - Should be: `https://fls-...laravel.cloud/products/...`

---

## 📊 WHAT THE SCRIPTS DO

### Update Existing Product Images

```
┌─────────────────────────────────────┐
│  Find products with timestamp names │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Extract clean filename             │
│  colgate-1760352000.jpg             │
│  → colgate.jpg                      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Copy to R2 with new name           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Update database                    │
│  product.image = new path           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  ✅ Clean filename ready!           │
└─────────────────────────────────────┘
```

### Update Dashboard Images

```
┌─────────────────────────────────────┐
│  Check all products                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Is image on R2?                    │
└──────┬──────────────┬───────────────┘
       │ YES          │ NO
       ▼              ▼
┌──────────┐   ┌─────────────────────┐
│ Skip     │   │ Upload from local   │
└──────────┘   └──────────┬──────────┘
                          │
                          ▼
               ┌─────────────────────┐
               │ Verify on R2        │
               └──────────┬──────────┘
                          │
                          ▼
               ┌─────────────────────┐
               │ ✅ Image on R2!     │
               └─────────────────────┘
```

---

## 🎯 EXAMPLES

### Example 1: Clean Filename Migration

**Before**:
```
Database:
- Product #1: products/seller-1/colgate-1760352000.jpg
- Product #2: products/seller-1/dove-1760352100.jpg
- Product #3: products/seller-1/dettol-1760352200.jpg
```

**Run**:
```bash
php update_existing_product_images.php
```

**After**:
```
Database:
- Product #1: products/seller-1/colgate.jpg
- Product #2: products/seller-1/dove.jpg
- Product #3: products/seller-1/dettol.jpg

R2 Storage:
✅ products/seller-1/colgate.jpg (new)
✅ products/seller-1/dove.jpg (new)
✅ products/seller-1/dettol.jpg (new)
🗂️ products/seller-1/colgate-1760352000.jpg (old - kept for safety)
```

---

### Example 2: Force R2 Upload

**Before**:
```
Local Storage:
- products/seller-1/product-a.jpg ✅
- products/seller-1/product-b.jpg ✅
- products/seller-1/product-c.jpg ✅

R2 Storage:
- (empty or incomplete)
```

**Run**:
```bash
php update_dashboard_images.php update
```

**After**:
```
Local Storage:
- products/seller-1/product-a.jpg ✅
- products/seller-1/product-b.jpg ✅
- products/seller-1/product-c.jpg ✅

R2 Storage:
- products/seller-1/product-a.jpg ✅ (uploaded)
- products/seller-1/product-b.jpg ✅ (uploaded)
- products/seller-1/product-c.jpg ✅ (uploaded)
```

---

## ⚠️ SAFETY FEATURES

### Both Scripts:
- ✅ **Non-destructive**: Old files are preserved
- ✅ **Verification**: Checks if new files exist before updating database
- ✅ **Error handling**: Catches and logs exceptions
- ✅ **Detailed output**: Shows exactly what's happening
- ✅ **Dry run option**: Check before making changes

### Safety Checks:
1. **Verify old file exists** before copying
2. **Verify new file uploaded** before updating database
3. **Skip external URLs** (don't modify)
4. **Skip static images** (from public/images)
5. **Detailed logging** for troubleshooting

---

## 🔍 TROUBLESHOOTING

### Issue: "Old file not found"

**Problem**: Image path in database doesn't exist on disk  
**Solution**:
1. Check if image was deleted
2. Check if path in database is correct
3. Upload new image via dashboard

### Issue: "R2 upload failed"

**Problem**: Can't upload to R2  
**Solution**:
1. Check R2 credentials in `.env`
2. Verify AWS_* environment variables
3. Test R2 connection: `php test_r2_connection.php`

### Issue: "New filename already exists"

**Problem**: Clean filename conflicts with existing file  
**Solution**:
- Script automatically uses existing file
- Updates database to point to it
- This is safe behavior

---

## 📋 CHECKLIST

### Before Running Scripts:

- [ ] Backup database
- [ ] Verify R2 credentials are correct
- [ ] Test R2 connection works
- [ ] Check local images exist
- [ ] Have Laravel Cloud access

### After Running Scripts:

- [ ] Clear Laravel cache
- [ ] Test seller dashboard
- [ ] Verify images display correctly
- [ ] Check image URLs (should be R2)
- [ ] Test product edit/update
- [ ] Verify new uploads work

---

## 🎉 EXPECTED RESULTS

### Dashboard:
```
Products Table:
┌────────────┬─────────────────┬────────────────────┐
│ Image      │ Product Name    │ Status             │
├────────────┼─────────────────┼────────────────────┤
│ [thumbnail]│ Colgate Total   │ ✅ From R2         │
│ [thumbnail]│ Dove Soap       │ ✅ From R2         │
│ [thumbnail]│ Dettol Handwash │ ✅ From R2         │
└────────────┴─────────────────┴────────────────────┘
```

### Image URLs:
```
https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/seller-1/colgate-total.jpg
https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/seller-1/dove-soap.jpg
https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/seller-1/dettol-handwash.jpg
```

### File Structure:
```
R2 Bucket:
├── products/
│   └── seller-1/
│       ├── colgate-total.jpg ✅ (clean)
│       ├── dove-soap.jpg ✅ (clean)
│       └── dettol-handwash.jpg ✅ (clean)
```

---

*Documentation Created: October 13, 2025*  
*Purpose: Update existing images to new clean filename strategy*  
*Safe: Non-destructive, preserves old files*

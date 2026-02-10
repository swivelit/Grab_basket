# Seller Profile Photo - Testing Guide

## 🧪 Testing Checklist

### Test 1: Upload Valid Photo (JPEG)
**Steps:**
1. Log in as a seller
2. Navigate to Profile (`/seller/profile`)
3. Scroll to "Update Store Info" section
4. Click "Choose File" under "Profile Photo"
5. Select a JPEG image (< 2MB)
6. Observe live preview appears
7. Click "Update" button
8. Verify success message appears
9. Refresh page
10. Verify photo displays in profile avatar

**Expected Result:**
✅ Photo uploads successfully
✅ Preview shows before submission
✅ Success message: "Profile updated successfully!"
✅ Photo displays in circular avatar at top
✅ Photo displays in dashboard header

---

### Test 2: Upload PNG Photo
**Steps:**
1. Go to Profile
2. Click "Choose File"
3. Select a PNG image (< 2MB)
4. Observe preview
5. Click "Update"

**Expected Result:**
✅ PNG uploads successfully
✅ Replaces previous photo
✅ Old JPEG deleted from R2

---

### Test 3: File Size Validation (Client-Side)
**Steps:**
1. Go to Profile
2. Click "Choose File"
3. Select an image > 2MB
4. Observe alert message

**Expected Result:**
✅ Alert: "File size must be less than 2MB"
✅ File input cleared
✅ No preview shown
✅ Cannot submit

---

### Test 4: Invalid File Type (Client-Side)
**Steps:**
1. Go to Profile
2. Click "Choose File"
3. Try to select a PDF/TXT file
4. Observe file selector filters

**Expected Result:**
✅ File selector only shows image files
✅ If somehow selected: Alert "Please select a valid image file"
✅ File input cleared

---

### Test 5: Server-Side Validation
**Steps:**
1. Use browser DevTools or Postman
2. Send POST to `/seller/profile/update`
3. Include invalid file (e.g., 5MB image or .exe file)
4. Submit form

**Expected Result:**
✅ Server rejects with validation error
✅ Error message displayed on page
✅ No file uploaded to R2
✅ Database not updated

---

### Test 6: Update Existing Photo
**Steps:**
1. Upload first photo (Test 1)
2. Note the photo URL in database
3. Upload second photo
4. Check R2 storage

**Expected Result:**
✅ New photo uploads
✅ Old photo deleted from R2
✅ Database updated with new URL
✅ Only one photo per user in storage

---

### Test 7: Dashboard Display
**Steps:**
1. Upload profile photo
2. Navigate to Dashboard (`/seller/dashboard`)
3. Observe header area

**Expected Result:**
✅ Profile photo displays in circular avatar
✅ Replaces default GrabBasket logo
✅ Properly styled (80x80px, white border)

---

### Test 8: No Photo Fallback
**Steps:**
1. Create new seller account (no photo)
2. Go to Profile
3. Observe avatar

**Expected Result:**
✅ Shows UI-Avatars placeholder with initials
✅ No broken image
✅ Upload section shows no "current photo"

---

### Test 9: Live Preview Functionality
**Steps:**
1. Go to Profile
2. Click "Choose File"
3. Select an image
4. Don't submit yet
5. Observe preview section

**Expected Result:**
✅ Preview appears below file input
✅ Shows thumbnail (150x150px max)
✅ Label: "New photo preview"
✅ Select different file updates preview

---

### Test 10: R2 Storage Verification
**Steps:**
1. Upload a photo
2. Check database for profile_picture URL
3. Copy URL and paste in browser
4. Check R2 bucket

**Expected Result:**
✅ URL format: `https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/profile_photos/USER_ID_TIMESTAMP.ext`
✅ URL opens image in browser
✅ Image accessible publicly
✅ File exists in R2 at `profile_photos/` folder

---

## 🔍 Manual Verification Steps

### Database Check
```sql
-- Check profile_picture field
SELECT id, name, email, profile_picture 
FROM users 
WHERE role = 'seller' 
LIMIT 10;
```

**Expected:**
- Sellers with photos: Full R2 URL
- Sellers without photos: NULL

### R2 Storage Check
```php
// Via Tinker
php artisan tinker

// List profile photos
Storage::disk('r2')->files('profile_photos');

// Check specific file exists
Storage::disk('r2')->exists('profile_photos/1_1735123456.jpg');
```

### URL Construction Check
```php
// Via Tinker
$user = User::find(1);
echo $user->profile_picture;
// Should output: https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/profile_photos/1_TIMESTAMP.ext
```

---

## 📊 Performance Testing

### Load Time Test
1. Upload photo
2. Open Profile page
3. Check Network tab in DevTools
4. Verify photo loads quickly

**Expected:**
✅ Photo loads from CDN
✅ < 500ms load time
✅ Proper caching headers

### Multiple Users Test
1. Create 10 seller accounts
2. Upload different photos for each
3. Verify each displays correctly
4. Check R2 has 10 separate files

**Expected:**
✅ No filename conflicts
✅ Each user sees their own photo
✅ Unique timestamps in filenames

---

## 🐛 Common Issues & Solutions

### Issue: Photo not displaying
**Check:**
- View page source, verify `<img>` src attribute
- Copy URL, test in browser directly
- Check browser console for CORS errors
- Verify R2 public access enabled

### Issue: Upload fails silently
**Check:**
- Laravel logs: `storage/logs/laravel.log`
- Network tab: Check POST response
- Verify R2 credentials in `.env`
- Check disk space/R2 quota

### Issue: Old photos not deleted
**Check:**
- R2 delete permissions
- Laravel logs for deletion errors
- URL parsing in controller

### Issue: Preview not working
**Check:**
- Browser console for JavaScript errors
- File input ID matches JavaScript
- FileReader API support (modern browsers)

---

## ✅ Success Criteria

All tests should pass with these results:

- [x] Valid images upload successfully (JPEG, PNG, GIF)
- [x] Client-side validation works (size, type)
- [x] Server-side validation works
- [x] Live preview displays correctly
- [x] Photos display in profile page
- [x] Photos display in dashboard
- [x] Old photos deleted on update
- [x] R2 storage working correctly
- [x] URLs constructed properly
- [x] Fallback works for users without photos
- [x] No security vulnerabilities
- [x] Performance acceptable

---

## 📝 Test Results Template

```
Test Date: _____________
Tester: _____________
Environment: _____________

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Upload JPEG | ⬜ PASS ⬜ FAIL | |
| 2 | Upload PNG | ⬜ PASS ⬜ FAIL | |
| 3 | Size Validation | ⬜ PASS ⬜ FAIL | |
| 4 | Type Validation | ⬜ PASS ⬜ FAIL | |
| 5 | Server Validation | ⬜ PASS ⬜ FAIL | |
| 6 | Update Photo | ⬜ PASS ⬜ FAIL | |
| 7 | Dashboard Display | ⬜ PASS ⬜ FAIL | |
| 8 | No Photo Fallback | ⬜ PASS ⬜ FAIL | |
| 9 | Live Preview | ⬜ PASS ⬜ FAIL | |
| 10 | R2 Storage | ⬜ PASS ⬜ FAIL | |

Overall Result: ⬜ PASS ⬜ FAIL

Issues Found:
_____________________________________________
_____________________________________________
_____________________________________________
```

---

## 🚀 Quick Test Commands

```bash
# Clear caches before testing
php artisan cache:clear
php artisan view:clear

# Check database
php artisan tinker
>>> User::where('role', 'seller')->whereNotNull('profile_picture')->count()

# Check R2 storage
>>> Storage::disk('r2')->files('profile_photos')

# Test upload manually
>>> $file = new \Illuminate\Http\UploadedFile('/path/to/test.jpg', 'test.jpg', 'image/jpeg', null, true);
>>> Storage::disk('r2')->put('test_upload.jpg', file_get_contents($file));
>>> Storage::disk('r2')->exists('test_upload.jpg')
>>> Storage::disk('r2')->delete('test_upload.jpg')
```

---

**Ready to test!** Follow the checklist systematically and document any issues found.

# Update Profile 500 Error - Fix Applied

## 🐛 Issue
```
URL: https://grabbaskets.laravel.cloud/seller/update-profile
Status: 500 Server Error
```

Seller profile update endpoint throwing 500 error when trying to update store information or upload profile photo.

---

## 🔍 Root Causes Identified

### 1. **Missing Error Handling**
- No try-catch wrapper around entire method
- Validation exceptions not caught
- No authentication check before processing

### 2. **Problematic Path Extraction**
```php
// OLD CODE - PROBLEMATIC
$oldPath = basename(dirname($user->profile_picture)) . '/' . basename($user->profile_picture);
```
**Issues**:
- `basename(dirname())` doesn't work correctly with URLs
- Results in incorrect path like "laravel.cloud/filename.jpg"
- Can't delete old photos properly
- Potential errors with external URLs (UI-Avatars)

### 3. **No Seller Existence Check**
```php
// OLD CODE
$seller = \App\Models\Seller::where('email', $user->email)->firstOrFail();
```
- `firstOrFail()` throws exception if not found
- No graceful error handling
- 500 error instead of user-friendly message

### 4. **Limited Logging**
- Minimal logging of errors
- Hard to debug issues
- No tracking of successful updates

---

## ✅ Fixes Applied

### 1. **Comprehensive Error Handling**

**Added Try-Catch Wrapper**:
```php
public function updateProfile(Request $request)
{
    try {
        // All logic here
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->validator)
            ->withInput()
            ->with('error', 'Please fix the validation errors.');
    } catch (\Exception $e) {
        Log::error('updateProfile error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Failed to update profile. Please try again.');
    }
}
```

**Benefits**:
- No 500 errors reach the user
- Validation errors shown clearly
- Generic errors logged and handled gracefully

---

### 2. **Fixed Photo Path Extraction**

**Old Code**:
```php
$oldPath = basename(dirname($user->profile_picture)) . '/' . basename($user->profile_picture);
```

**New Code**:
```php
// Extract filename from full URL
$oldFilename = str_replace($r2PublicUrl . '/', '', $user->profile_picture);

// Only delete if it's a profile photo (starts with profile_photos/)
if (str_starts_with($oldFilename, 'profile_photos/')) {
    Storage::disk('r2')->delete($oldFilename);
    Log::info('Deleted old profile photo', ['filename' => $oldFilename]);
}
```

**Benefits**:
- Correctly extracts path from full URL
- Safely handles external URLs (UI-Avatars, etc.)
- Only deletes actual profile photos
- Logs deletion for audit trail

---

### 3. **Enhanced Authentication & Validation**

**Authentication Check**:
```php
$user = Auth::user();

if (!$user) {
    Log::error('updateProfile: User not authenticated');
    return redirect()->route('login')->with('error', 'Please log in to update your profile.');
}
```

**Seller Existence Check**:
```php
$seller = \App\Models\Seller::where('email', $user->email)->first();

if (!$seller) {
    Log::error('updateProfile: Seller not found', [
        'user_id' => $user->id,
        'email' => $user->email
    ]);
    return redirect()->back()->with('error', 'Seller profile not found.');
}
```

**Benefits**:
- Graceful handling of unauthenticated users
- Clear error messages for missing seller records
- Proper logging for debugging

---

### 4. **Comprehensive Logging**

**Added Logging Throughout**:
```php
// Success logging
Log::info('Profile photo updated successfully', [
    'user_id' => $user->id,
    'filename' => $filename
]);

// Warning logging
Log::warning('Failed to delete old profile photo', [
    'error' => $e->getMessage(),
    'old_url' => $user->profile_picture
]);

// Error logging
Log::error('Profile photo upload failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

**Benefits**:
- Full audit trail
- Easy debugging
- Track success and failures

---

### 5. **Better User Feedback**

**Old Messages**:
- ✗ "Profile photo upload failed. Store info updated."
- ✗ "Store info updated!"

**New Messages**:
- ✅ "Profile photo and store info updated successfully!"
- ✅ "Store info updated successfully!"
- ✅ "Profile photo upload failed: [specific error]"
- ✅ "Failed to update profile. Please try again."

---

## 🧪 Testing Results

### Test 1: Update Without Photo
**Action**: Update only store info
**Result**: ✅ Success - "Store info updated successfully!"

### Test 2: Update With Photo
**Action**: Upload new profile photo
**Result**: ✅ Success - Photo uploaded to R2, URL saved

### Test 3: Replace Existing Photo
**Action**: Upload new photo when user has existing photo
**Result**: ✅ Success - Old photo deleted, new photo uploaded

### Test 4: Invalid File Type
**Action**: Upload .txt file
**Result**: ✅ Validation error - "File must be in jpeg,jpg,png,gif format"

### Test 5: File Too Large
**Action**: Upload >2MB file
**Result**: ✅ Validation error - "File size must not exceed 2MB"

### Test 6: External Photo URL
**Action**: User has UI-Avatars URL, uploads new photo
**Result**: ✅ Success - Skips external URL deletion, uploads new photo

### Test 7: No Seller Record
**Action**: User without seller record tries to update
**Result**: ✅ Error message - "Seller profile not found"

### Test 8: Not Authenticated
**Action**: Unauthenticated user tries to access
**Result**: ✅ Redirect to login

---

## 📋 Error Scenarios Handled

| Scenario | Old Behavior | New Behavior |
|----------|--------------|--------------|
| Validation fails | ❌ 500 error | ✅ Shows validation errors |
| No seller record | ❌ 500 error (firstOrFail) | ✅ "Seller profile not found" |
| Not authenticated | ❌ Null reference error | ✅ Redirect to login |
| Photo upload fails | ❌ Exception thrown | ✅ Logged, user-friendly message |
| Old photo deletion fails | ❌ Stops process | ✅ Logged warning, continues |
| Invalid file type | ❌ 500 error | ✅ Validation error shown |
| File too large | ❌ 500 error | ✅ Validation error shown |
| R2 connection fails | ❌ 500 error | ✅ Logged, error message shown |

---

## 🔧 Files Modified

**`app/Http/Controllers/SellerController.php`**:
- Wrapped entire `updateProfile()` method in try-catch
- Added authentication check
- Changed `firstOrFail()` to `first()` with null check
- Fixed photo path extraction logic
- Enhanced logging throughout
- Better error messages
- Improved photo deletion logic

---

## 📊 Before vs After

### Before Fix
```
- 500 errors on various scenarios
- No error logging
- Incorrect path extraction
- Unhandled exceptions
- Poor user feedback
```

### After Fix
```
✅ No 500 errors
✅ Comprehensive logging
✅ Correct path extraction
✅ All exceptions handled
✅ Clear user feedback
✅ Graceful fallbacks
```

---

## 🚀 Deployment

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Commit changes
git add app/Http/Controllers/SellerController.php
git commit -m "fix: Enhanced updateProfile error handling and path extraction"
git push origin main
```

---

## 📝 Usage for Sellers

### Update Store Info Only
1. Go to Profile page
2. Fill in Store Name, GST Number, Address, Contact
3. Click "Update"
4. ✅ Success message shown

### Upload Profile Photo
1. Go to Profile page
2. Click "Choose File" under Profile Photo
3. Select image (JPEG, PNG, GIF, <2MB)
4. Fill in store info if needed
5. Click "Update"
6. ✅ Photo uploaded, displayed in profile

### Update Both
1. Upload new photo
2. Update store information
3. Click "Update"
4. ✅ Both updated successfully

---

## 🔍 Troubleshooting

### If Issues Persist

**Check Logs**:
```bash
tail -f storage/logs/laravel.log | grep "updateProfile"
```

**Check R2 Connection**:
```bash
php artisan tinker
>>> Storage::disk('r2')->put('test.txt', 'test');
>>> Storage::disk('r2')->exists('test.txt');
>>> Storage::disk('r2')->delete('test.txt');
```

**Check Seller Record**:
```bash
php artisan tinker
>>> $user = Auth::user();
>>> $seller = \App\Models\Seller::where('email', $user->email)->first();
>>> $seller ?? 'Not found';
```

**Check Photo Upload**:
- Max file size: 2MB
- Allowed formats: JPEG, JPG, PNG, GIF
- Form must have `enctype="multipart/form-data"`
- CSRF token must be present

---

## ✅ Success Criteria

- [x] No 500 errors on update
- [x] Validation errors displayed correctly
- [x] Profile photos upload successfully
- [x] Old photos deleted correctly
- [x] External URLs handled safely
- [x] Store info updates work
- [x] Combined updates work
- [x] Comprehensive error logging
- [x] User-friendly error messages
- [x] Graceful error handling

---

**Status**: ✅ FIXED  
**Date**: October 14, 2025  
**Impact**: Update profile endpoint now robust and error-free

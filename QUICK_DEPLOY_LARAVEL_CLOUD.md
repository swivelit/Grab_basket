# Quick Deploy to Laravel Cloud

## Your Setup
- **Production URL**: https://grabbaskets.laravel.cloud
- **Repository**: https://github.com/grabbaskets-hash/grabbaskets
- **Latest Commit**: 2215393b (Deployment scripts)

## 🚀 Deployment Options

### Option 1: Auto-Deploy (Recommended - If Enabled)

Laravel Cloud should auto-deploy when you push to GitHub. 

**Check deployment status:**
1. Go to: https://cloud.laravel.com
2. Find your "grabbaskets" project
3. Check the "Deployments" tab
4. Look for commit `2215393b` - should say "Deployed" or "Deploying"

**If auto-deploy is working:**
- ✅ Wait 2-3 minutes for deployment to complete
- ✅ Skip to "Verify Deployment" section below

---

### Option 2: Manual Deploy via Dashboard

**If auto-deploy is NOT working or you want to force deploy:**

1. **Login to Laravel Cloud**
   - Go to: https://cloud.laravel.com
   - Login with your credentials

2. **Find Your Project**
   - Click on "grabbaskets" project

3. **Trigger Manual Deployment**
   - Click "Deploy Now" or "Deploy" button
   - Or go to Settings → Deploy → "Deploy Now"

4. **Wait for Deployment**
   - Watch the deployment logs
   - Wait for "Deployment Complete" status

---

### Option 3: SSH Deploy (If You Have SSH Access)

**If Laravel Cloud provides SSH access:**

```bash
# SSH into server
ssh your-username@grabbaskets.laravel.cloud

# Navigate to app directory (usually /var/www or /home/forge)
cd /path/to/app

# Pull latest code
git pull origin main

# Clear caches
php artisan optimize:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Verify Deployment

After deployment completes, verify the fixes are live:

### 1. Check Category Page Alignment
```
URL: https://grabbaskets.laravel.cloud/buyer/category/5

✓ Product grid should be properly aligned
✓ 4 columns on desktop, 2 on mobile
✓ No overlapping cards
```

### 2. Check PDF Export (Seller Dashboard)
```
1. Login as seller
2. Go to: https://grabbaskets.laravel.cloud/seller/dashboard
3. Navigate to Products → Export
4. Try "Export PDF" - should download
5. Try "Export PDF with Images" - should download with product images
```

### 3. Clear Browser Cache
```
Press: Ctrl + Shift + R (Windows/Linux)
Press: Cmd + Shift + R (Mac)

Or open in Incognito/Private mode to see fresh changes
```

---

## 🔍 Troubleshooting

### Deployment Not Showing

**Check if code is actually deployed:**
```
Visit: https://grabbaskets.laravel.cloud/test-route-check

If this returns 404, the new code isn't deployed yet
```

**Force cache clear:**
Create a file `public/clear-cache.php`:
```php
<?php
exec('cd .. && php artisan optimize:clear', $output);
echo "Cache cleared!\n";
print_r($output);
```

Then visit: `https://grabbaskets.laravel.cloud/clear-cache.php`

---

### PDF Export Still Not Working

**If PDF export fails:**
1. Check Laravel Cloud logs in dashboard
2. Look for memory or timeout errors
3. Contact Laravel Cloud support to increase:
   - PHP memory_limit to 2G
   - max_execution_time to 900 seconds

---

### Category Page Still Misaligned

**If alignment is still broken:**
1. Hard refresh: Ctrl+Shift+R
2. Clear browser cache completely
3. Try different browser
4. Check if Cloudflare cache needs purging

---

## 📞 Need Help?

### Laravel Cloud Support
- Dashboard: https://cloud.laravel.com
- Support: support@laravel.cloud
- Check deployment logs in dashboard

### Common Deploy Wait Times
- Auto-deploy: 2-5 minutes
- Manual deploy: 1-3 minutes
- Cache propagation: 1-2 minutes

**Total expected time: 5-10 minutes maximum**

---

## ✅ Success Checklist

After deployment:
- [ ] Laravel Cloud dashboard shows "Deployment Complete"
- [ ] Category page alignment fixed
- [ ] PDF export (simple) works
- [ ] PDF export (with images) works
- [ ] No console errors in browser (F12)
- [ ] Mobile view looks good

---

## Current Status

**Code Status:**
- ✅ All fixes committed to GitHub
- ✅ Latest commit: 2215393b
- ✅ Branch: main
- ⏳ Laravel Cloud deployment: **Pending your action**

**Next Step:** Choose one of the 3 deployment options above!

# HOSTINGER PARKED DOMAIN TROUBLESHOOTING GUIDE

## Issue: Domain Shows "Parked Domain" Page Instead of Laravel Application

### Possible Causes & Solutions

## 1. FILES IN WRONG DIRECTORY ⚠️ MOST COMMON

### Problem:
Laravel files uploaded to wrong directory structure on Hostinger.

### Solution:
```
Correct Hostinger Directory Structure:
/domains/yourdomain.com/
├── public_html/              <- Laravel's PUBLIC folder contents go here
│   ├── index.php            <- Laravel's public/index.php
│   ├── .htaccess            <- Our optimized .htaccess
│   ├── css/                 <- Compiled CSS assets
│   ├── js/                  <- Compiled JS assets
│   └── storage -> ../storage/app/public
├── app/                     <- Laravel app directory
├── bootstrap/               <- Laravel bootstrap
├── config/                  <- Laravel config
├── database/                <- Laravel database
├── resources/               <- Laravel resources
├── routes/                  <- Laravel routes
├── storage/                 <- Laravel storage
├── vendor/                  <- Composer vendor
├── .env                     <- Environment file
├── artisan                  <- Artisan CLI
└── composer.json            <- Composer config
```

### What NOT to do:
❌ Don't upload entire Laravel project to public_html/
❌ Don't create laravel/ subfolder in public_html/

## 2. DOMAIN NOT POINTED TO PUBLIC_HTML 🌐

### Check in Hostinger Control Panel:
1. Go to **Domains** section
2. Click **Manage** next to your domain
3. Verify **Document Root** is set to `public_html`
4. If not, change it to `public_html`

## 3. INDEX.PHP ISSUES 📄

### Problem:
Laravel's index.php not properly configured for Hostinger paths.

### Solution:
Update public_html/index.php to point to correct Laravel directories:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Hostinger: Adjust paths to Laravel directories one level up
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    require __DIR__.'/../../vendor/autoload.php';
}

// Bootstrap Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

## 4. DNS PROPAGATION DELAY ⏰

### Check:
- Domain was recently pointed to Hostinger
- DNS changes can take 24-48 hours to propagate
- Use DNS checker: whatsmydns.net

## 5. CACHE ISSUES 🔄

### Clear Hostinger Cache:
1. Go to **Website** section in Hostinger panel
2. Find **Cache** settings
3. Click **Purge Cache** or **Clear Cache**

### Clear Browser Cache:
- Hard refresh: Ctrl+F5 (Windows) / Cmd+Shift+R (Mac)
- Try incognito/private browsing mode

## 6. SSL CERTIFICATE ISSUES 🔒

### Check SSL Status:
1. In Hostinger panel, go to **SSL**
2. Ensure SSL certificate is active
3. If not, enable **Free SSL** certificate
4. Wait for activation (can take up to 24 hours)

## QUICK DIAGNOSTIC STEPS

### Step 1: Check File Structure
```bash
# Connect via FTP/File Manager and verify:
# - index.php exists in public_html/
# - .htaccess exists in public_html/
# - Laravel core files are one directory up from public_html/
```

### Step 2: Test Direct Access
```bash
# Try accessing: yourdomain.com/index.php
# If Laravel loads, it's an .htaccess issue
# If still parked domain, it's a file structure issue
```

### Step 3: Check Error Logs
```bash
# In Hostinger panel:
# Website → Error Logs
# Look for recent errors when accessing domain
```

## IMMEDIATE FIX CHECKLIST

- [ ] Verify domain points to correct Hostinger account
- [ ] Check document root is set to `public_html`
- [ ] Ensure index.php is in public_html/ directory
- [ ] Verify .htaccess is in public_html/ directory
- [ ] Check Laravel core files are outside public_html/
- [ ] Clear Hostinger cache
- [ ] Clear browser cache
- [ ] Wait for DNS propagation (if domain recently changed)

## EMERGENCY TEMPORARY FIX

If you need immediate access, create a temporary index.html in public_html/:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Site Under Maintenance</title>
</head>
<body>
    <h1>Laravel Site Loading...</h1>
    <p>If you see this, files are in correct location.</p>
    <p>Laravel application will be available shortly.</p>
</body>
</html>
```

This will confirm files are being served from the correct directory.
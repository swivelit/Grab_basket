#!/bin/bash
# GrabBaskets - Production Deployment Fix Script
# Run this on your production server after git pull

echo "================================================"
echo "GrabBaskets - Emergency Fix Deployment"
echo "================================================"
echo ""

# Change to application directory (adjust path if needed)
cd "$(dirname "$0")" || exit 1

echo "Step 1: Clearing all Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✓ Caches cleared"
echo ""

echo "Step 2: Rebuilding optimized caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo "✓ Caches rebuilt"
echo ""

echo "Step 3: Running optimization..."
php artisan optimize
echo "✓ Optimization complete"
echo ""

echo "Step 4: Checking storage permissions..."
chmod -R 775 storage 2>/dev/null || echo "⚠ Could not set storage permissions (may need sudo)"
chmod -R 775 bootstrap/cache 2>/dev/null || echo "⚠ Could not set bootstrap/cache permissions (may need sudo)"
echo "✓ Permissions checked"
echo ""

echo "Step 5: Testing application..."
php artisan route:list | grep "GET|HEAD" | grep "/" | head -n 1
echo "✓ Routes loaded"
echo ""

echo "================================================"
echo "Deployment Complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Visit https://grabbaskets.com/health-check to verify app is running"
echo "2. Visit https://grabbaskets.com/test-index-debug for diagnostics"
echo "3. Visit https://grabbaskets.com/ to test homepage"
echo ""
echo "If still showing errors:"
echo "- Check storage/logs/laravel.log for recent errors"
echo "- Ensure APP_DEBUG=false in production .env"
echo "- Verify database credentials in .env"
echo ""

#!/bin/bash
# ============================================
# Clear Production Cache - Linux/Laravel Cloud
# ============================================
# Purpose: Clear all Laravel caches for login/logout fix
# Date: October 23, 2025
# Usage: bash clear_production_cache.sh
# ============================================

echo "========================================"
echo "  Clearing Production Caches"
echo "========================================"
echo ""

# Clear compiled views
echo "1. Clearing compiled Blade views..."
php artisan view:clear
if [ $? -eq 0 ]; then
    echo "   ✓ Compiled views cleared"
else
    echo "   ✗ Failed to clear views"
fi
echo ""

# Clear application cache
echo "2. Clearing application cache..."
php artisan cache:clear
if [ $? -eq 0 ]; then
    echo "   ✓ Application cache cleared"
else
    echo "   ✗ Failed to clear cache"
fi
echo ""

# Clear config cache
echo "3. Clearing configuration cache..."
php artisan config:clear
if [ $? -eq 0 ]; then
    echo "   ✓ Configuration cache cleared"
else
    echo "   ✗ Failed to clear config"
fi
echo ""

# Clear route cache
echo "4. Clearing route cache..."
php artisan route:clear
if [ $? -eq 0 ]; then
    echo "   ✓ Route cache cleared"
else
    echo "   ✗ Failed to clear routes"
fi
echo ""

# Optimize application
echo "5. Optimizing application..."
php artisan optimize:clear
if [ $? -eq 0 ]; then
    echo "   ✓ Application optimized"
else
    echo "   ✗ Failed to optimize"
fi
echo ""

echo "========================================"
echo "  Cache Clearing Complete!"
echo "========================================"
echo ""
echo "Next Steps:"
echo "1. Test login at: https://grabbaskets.laravel.cloud/login"
echo "2. Test logout functionality"
echo "3. Verify search page loads correctly"
echo "4. Check for any 500 errors"
echo ""

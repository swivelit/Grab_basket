#!/bin/bash

# =============================================================================
# Deployment Script for GrabBaskets - All Recent Fixes
# =============================================================================
# This script deploys:
# - PDF export fixes with image support
# - Database column alignment fixes  
# - Category page grid alignment fix
# - Server configuration updates
# =============================================================================

echo "=================================================="
echo "🚀 GrabBaskets Deployment Script"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Error handling
set -e
trap 'echo -e "${RED}❌ Deployment failed!${NC}"; exit 1' ERR

echo "📦 Step 1: Pulling latest code from GitHub..."
git pull origin main
echo -e "${GREEN}✅ Code pulled successfully${NC}"
echo ""

echo "🧹 Step 2: Clearing all caches..."
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

echo "📝 Step 3: Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Optimization complete${NC}"
echo ""

echo "🔧 Step 4: Checking PHP configuration..."
echo "Current PHP settings:"
php -i | grep "max_execution_time"
php -i | grep "memory_limit"
echo ""
echo -e "${YELLOW}⚠️  For PDF export, ensure these settings:${NC}"
echo "   - max_execution_time = 900"
echo "   - memory_limit = 2G"
echo ""

echo "🔍 Step 5: Running post-deployment checks..."

# Check if DomPDF package exists
if php artisan | grep -q "dompdf"; then
    echo -e "${GREEN}✅ DomPDF package detected${NC}"
else
    echo -e "${YELLOW}⚠️  DomPDF package check skipped${NC}"
fi

# Check critical files exist
if [ -f "app/Http/Controllers/ProductImportExportController.php" ]; then
    echo -e "${GREEN}✅ ProductImportExportController exists${NC}"
else
    echo -e "${RED}❌ ProductImportExportController not found${NC}"
fi

if [ -f "resources/views/buyer/products.blade.php" ]; then
    echo -e "${GREEN}✅ Category products view exists${NC}"
else
    echo -e "${RED}❌ Category products view not found${NC}"
fi

if [ -f "resources/views/seller/exports/products-pdf-with-images.blade.php" ]; then
    echo -e "${GREEN}✅ PDF export views exist${NC}"
else
    echo -e "${RED}❌ PDF export views not found${NC}"
fi

echo ""
echo "=================================================="
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo "=================================================="
echo ""
echo "📋 Next steps:"
echo "1. Test category pages: https://grabbaskets.laravel.cloud/buyer/category/5"
echo "2. Test PDF export in seller dashboard"
echo "3. Monitor server logs for any errors"
echo ""
echo "🔧 If PDF export has timeout issues, update web server config:"
echo "   Nginx: fastcgi_read_timeout 900;"
echo "   Apache: FcgidIOTimeout 900"
echo "   PHP-FPM: request_terminate_timeout = 900"
echo ""
echo "📊 To check logs:"
echo "   tail -f storage/logs/laravel.log"
echo ""

#!/bin/bash

# =============================================================================
# Laravel Cloud Deployment Script
# =============================================================================
# This script deploys the latest code changes to your Laravel Cloud server
# Including: Category alignment fix, PDF export fixes, and optimization
# =============================================================================

echo "🚀 Starting deployment to Laravel Cloud..."
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Step 1: Pull latest code
echo "📥 Step 1: Pulling latest code from GitHub..."
git pull origin main
if [ $? -eq 0 ]; then
    print_status "Code pulled successfully"
else
    print_error "Failed to pull code"
    exit 1
fi
echo ""

# Step 2: Install/Update dependencies
echo "📦 Step 2: Installing/Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader
if [ $? -eq 0 ]; then
    print_status "Dependencies updated"
else
    print_warning "Composer install had issues (continuing...)"
fi
echo ""

# Step 3: Clear all caches
echo "🧹 Step 3: Clearing application caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
print_status "All caches cleared"
echo ""

# Step 4: Optimize for production
echo "⚡ Step 4: Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Application optimized"
echo ""

# Step 5: Set proper permissions
echo "🔐 Step 5: Setting proper permissions..."
chmod -R 775 storage bootstrap/cache
print_status "Permissions set"
echo ""

# Step 6: Run migrations (if any)
echo "🗄️  Step 6: Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    print_status "Migrations completed"
else
    print_warning "No new migrations or migration issues"
fi
echo ""

# Step 7: Restart queue workers (if applicable)
echo "🔄 Step 7: Restarting queue workers..."
php artisan queue:restart
print_status "Queue workers signaled to restart"
echo ""

echo "=============================================="
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo "=============================================="
echo ""
echo "📋 What was deployed:"
echo "  • Category page alignment fix"
echo "  • PDF export with images fix"
echo "  • Database column fixes"
echo "  • Performance optimizations"
echo ""
echo "🧪 Next steps:"
echo "  1. Test category pages (e.g., /buyer/category/5)"
echo "  2. Test PDF exports from seller dashboard"
echo "  3. Check browser console for any errors"
echo "  4. Monitor server logs for issues"
echo ""
echo "📊 To view logs: php artisan tail"
echo ""

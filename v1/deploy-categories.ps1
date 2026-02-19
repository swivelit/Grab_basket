# Laravel Deployment Commands for Categories and Subcategories

Write-Host "🚀 Starting Laravel Category & Subcategory Seeding..." -ForegroundColor Green

# Run migrations
Write-Host "📦 Running migrations..." -ForegroundColor Yellow
php artisan migrate --force

# Clear caches
Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run specific seeders
Write-Host "🌱 Seeding Categories..." -ForegroundColor Yellow
php artisan db:seed --class=CategorySeeder --force

Write-Host "🌱 Seeding Subcategories..." -ForegroundColor Yellow
php artisan db:seed --class=SubcategorySeeder --force

# Optimize for production
Write-Host "⚡ Optimizing for production..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "✅ Category and Subcategory seeding completed successfully!" -ForegroundColor Green
Write-Host "📊 Categories now include gender-based filtering: All, Men, Women, Kids" -ForegroundColor Cyan
Write-Host "🎯 Ready for modern category design implementation" -ForegroundColor Cyan
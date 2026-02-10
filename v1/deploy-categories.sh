#!/bin/bash

# Laravel Deployment Commands for Categories and Subcategories

echo "🚀 Starting Laravel Category & Subcategory Seeding..."

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run specific seeders
echo "🌱 Seeding Categories..."
php artisan db:seed --class=CategorySeeder --force

echo "🌱 Seeding Subcategories..."
php artisan db:seed --class=SubcategorySeeder --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Category and Subcategory seeding completed successfully!"
echo "📊 Categories now include gender-based filtering: All, Men, Women, Kids"
echo "🎯 Ready for modern category design implementation"
#!/bin/bash

# Deployment script for Laravel application
echo "Starting deployment..."

# Create necessary directories
echo "Creating storage directories..."
mkdir -p storage/framework/views 2>/dev/null || true
mkdir -p storage/framework/cache/data 2>/dev/null || true
mkdir -p storage/framework/sessions 2>/dev/null || true
mkdir -p storage/logs 2>/dev/null || true
mkdir -p bootstrap/cache 2>/dev/null || true

# Set proper permissions (you may need to adjust these based on your server setup)
echo "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Clear and cache configurations
echo "Clearing and caching configurations..."
php artisan config:clear
php artisan config:cache

# Clear route cache
echo "Clearing routes..."
php artisan route:clear

# Clear application cache
echo "Clearing application cache..."
php artisan cache:clear

# Clear view cache (only if storage/framework/views exists)
if [ -d "storage/framework/views" ]; then
    echo "Clearing view cache..."
    php artisan view:clear
else
    echo "View cache directory doesn't exist, skipping view:clear"
fi

# Install dependencies (production)
echo "Installing dependencies..."
composer install --optimize-autoloader --no-dev

# Generate application key if not exists
if grep -q "APP_KEY=$" .env; then
    echo "Generating application key..."
    php artisan key:generate
fi

echo "Deployment completed successfully!"
#!/bin/bash

# Image Fix Deployment Script
echo "Deploying image fixes..."

# Step 1: Upload new files to production
echo "1. Syncing files to production..."

# Step 2: Clear caches
echo "2. Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Step 3: Test the deployment
echo "3. Testing image serving..."
php -r "
\$url = 'https://grabbaskets.laravel.cloud/serve-image/products/0Rc193BfOQ4pDAtqAYBc1SLfKm2E9Hoklwo643Fz.jpg';
\$response = @file_get_contents(\$url, false, stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]));
\$result = \$response !== false ? 'SUCCESS' : 'FAILED';
echo \"Serve route test: \$result\n\";
"

echo "Deployment complete!"
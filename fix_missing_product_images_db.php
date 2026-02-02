<?php

// This script fixes products where the image file in database doesn't match what exists on disk
// Run this on production via: php fix_missing_product_images_db.php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Finding Products with Missing Image Files\n";
echo "===============================================\n\n";

// Get all products with images
$products = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->get();

$fixed = 0;
$missing = 0;

foreach ($products as $product) {
    $imagePath = ltrim($product->image, '/');
    
    // Skip external URLs
    if (str_starts_with($product->image, 'http')) {
        continue;
    }
    
    // Check if file exists locally
    $fullPath = storage_path('app/public/' . $imagePath);
    
    if (!file_exists($fullPath)) {
        $missing++;
        
        echo "❌ Product {$product->id}: {$product->name}\n";
        echo "   Missing file: {$imagePath}\n";
        
        // Try to find alternative file with same base name
        $directory = dirname($fullPath);
        $basename = basename($fullPath);
        
        // Extract the base name without timestamp (e.g., srm341 from srm341-1760340026.jpg)
        if (preg_match('/^(.+?)-\d+\.(\w+)$/', $basename, $matches)) {
            $baseNameOnly = $matches[1];
            $extension = $matches[2];
            
            // Look for files with same base name
            if (is_dir($directory)) {
                $pattern = $directory . '/' . $baseNameOnly . '-*.' . $extension;
                $alternatives = glob($pattern);
                
                if (!empty($alternatives)) {
                    // Use the most recent file
                    usort($alternatives, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    
                    $newFile = $alternatives[0];
                    $newRelativePath = str_replace(storage_path('app/public/'), '', $newFile);
                    $newRelativePath = str_replace('\\', '/', $newRelativePath); // Normalize slashes
                    
                    echo "   ✅ Found alternative: {$newRelativePath}\n";
                    echo "   📅 Modified: " . date('Y-m-d H:i:s', filemtime($newFile)) . "\n";
                    
                    // Update database
                    $product->image = $newRelativePath;
                    $product->save();
                    
                    echo "   💾 Database updated!\n";
                    $fixed++;
                } else {
                    echo "   ⚠️ No alternative found\n";
                }
            }
        }
        
        echo "\n";
    }
}

echo "===============================================\n";
echo "📊 Summary:\n";
echo "   Total products checked: " . count($products) . "\n";
echo "   Missing files: {$missing}\n";
echo "   Fixed: {$fixed}\n";
echo "   Still missing: " . ($missing - $fixed) . "\n";

if ($fixed > 0) {
    echo "\n✅ Database updated! Products should now display images correctly.\n";
    echo "💡 Clear cache with: php artisan cache:clear\n";
}

<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "=== Checking Specific Image: products/MO7_1760009921.png ===\n\n";

$imagePath = 'products/MO7_1760009921.png';

echo "🔍 Searching for: {$imagePath}\n\n";

// Check if file exists in cloud storage
try {
    $exists = Storage::exists($imagePath);
    echo "✅ File exists in cloud storage: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        $url = Storage::url($imagePath);
        echo "🌐 Cloud URL: {$url}\n";
        
        // Get file info
        $size = Storage::size($imagePath);
        echo "📊 File size: " . number_format($size / 1024, 2) . " KB\n";
        
        $lastModified = Storage::lastModified($imagePath);
        echo "📅 Last modified: " . date('Y-m-d H:i:s', $lastModified) . "\n";
    } else {
        echo "❌ File not found in cloud storage\n";
        
        // Check if it might be in a different path
        echo "\n🔍 Searching for similar files...\n";
        
        // List all files in products/ folder
        $productFiles = Storage::files('products');
        echo "📂 Found " . count($productFiles) . " files in products/ folder:\n";
        
        $found = false;
        foreach ($productFiles as $file) {
            if (strpos($file, 'MO7_1760009921') !== false) {
                echo "✅ Found similar: {$file}\n";
                $url = Storage::url($file);
                echo "🌐 URL: {$url}\n";
                $found = true;
            }
        }
        
        if (!$found) {
            echo "❌ No similar files found\n";
            
            // Show first few files for reference
            echo "\n📋 First 10 files in products/ folder:\n";
            foreach (array_slice($productFiles, 0, 10) as $file) {
                echo "- {$file}\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error checking file: " . $e->getMessage() . "\n";
}

// Also check if this path is referenced in any product
echo "\n🔍 Checking database for products with this image path...\n";
$products = \App\Models\Product::where('image', $imagePath)
    ->orWhere('image', 'LIKE', '%MO7_1760009921%')
    ->get();

if ($products->count() > 0) {
    echo "✅ Found " . $products->count() . " product(s) with this image:\n";
    foreach ($products as $product) {
        echo "- Product ID {$product->id}: {$product->name}\n";
        echo "  Image field: {$product->image}\n";
        echo "  Generated URL: {$product->image_url}\n";
    }
} else {
    echo "❌ No products found with this image path\n";
}

echo "\nDone!\n";
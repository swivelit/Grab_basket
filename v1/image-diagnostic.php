<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== Image URL Diagnostic ===\n\n";

// Check product 28 specifically
$product = Product::find(28);
if ($product) {
    echo "Product ID: " . $product->id . "\n";
    echo "Product Name: " . $product->name . "\n";
    echo "Raw Image Value: " . ($product->image ?? 'NULL') . "\n";
    
    try {
        echo "Smart Image URL: " . $product->image_url . "\n";
    } catch (Exception $e) {
        echo "Error getting image_url: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    // Check if file exists
    if ($product->image) {
        $paths = [
            'images/' . $product->image,
            'storage/app/public/' . $product->image,
            'public/storage/' . $product->image,
            'public/images/' . $product->image
        ];
        
        echo "\nFile existence check:\n";
        foreach ($paths as $path) {
            echo "$path: " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . "\n";
        }
    }
} else {
    echo "Product 28 not found\n";
}

// Check a few other products with SRM images
echo "\n=== SRM Products Check ===\n";
$srmProducts = Product::whereRaw("image LIKE 'SRM%'")->take(3)->get();
foreach ($srmProducts as $product) {
    echo "\nProduct {$product->id}: {$product->name}\n";
    echo "Image: {$product->image}\n";
    try {
        echo "Image URL: {$product->image_url}\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Environment Check ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "FILESYSTEM_DISK: " . config('filesystems.default') . "\n";
echo "R2_CONFIGURED: " . (config('filesystems.disks.r2') ? 'YES' : 'NO') . "\n";

?>
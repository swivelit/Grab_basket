<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

echo "=== Finding Products with Valid Images ===\n\n";

// Find products that have images in local storage
$localFiles = Storage::disk('public')->files('products');
echo "Files in local storage: " . count($localFiles) . "\n";

if (count($localFiles) > 0) {
    echo "Sample local files:\n";
    foreach (array_slice($localFiles, 0, 5) as $file) {
        echo "  - {$file}\n";
    }
    echo "\n";
}

// Find products that have legacy image field set
$productsWithLegacyImages = Product::whereNotNull('image')->take(10)->get();
echo "Products with legacy image field: " . $productsWithLegacyImages->count() . "\n";

foreach ($productsWithLegacyImages as $product) {
    echo "\nProduct ID: {$product->id}\n";
    echo "Name: " . substr($product->name, 0, 40) . "\n";
    echo "Legacy image: {$product->image}\n";
    
    // Check if file exists locally
    $existsLocal = Storage::disk('public')->exists($product->image);
    echo "Exists locally: " . ($existsLocal ? 'YES' : 'NO') . "\n";
    
    // Check if file exists on R2
    try {
        $existsR2 = Storage::disk('r2')->exists($product->image);
        echo "Exists on R2: " . ($existsR2 ? 'YES' : 'NO') . "\n";
    } catch (\Exception $e) {
        echo "R2 check failed: " . $e->getMessage() . "\n";
    }
    
    // Check the generated URL
    echo "Generated URL: {$product->image_url}\n";
    
    if ($existsLocal) {
        // Test local URL
        $localUrl = Storage::disk('public')->url($product->image);
        echo "Local URL would be: {$localUrl}\n";
    }
    
    echo "---\n";
}

// Check if there are any static image files that might work
echo "\n=== Testing Static Image Access ===\n";
$staticImagePath = 'images/srm/SRM701_1759987267.jpg';
$staticImageExists = file_exists(public_path($staticImagePath));
echo "Static image exists: " . ($staticImageExists ? 'YES' : 'NO') . "\n";
if ($staticImageExists) {
    echo "Static image URL: " . asset($staticImagePath) . "\n";
}
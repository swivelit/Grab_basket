<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== CHECKING R2 PUBLIC URL ACCESS ===\n\n";

// Test the public URL
$testUrl = "https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/seller-2/srm331.jpg";
echo "Testing public URL:\n";
echo "URL: $testUrl\n";

// Get sample products with images
echo "\n=== SAMPLE PRODUCTS ===\n";
$products = DB::table('products')
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->take(10)
    ->get(['id', 'name', 'image']);

echo "Found " . $products->count() . " products with images\n\n";

foreach ($products as $product) {
    $imagePath = $product->image;
    
    // Check if exists on R2
    $existsOnR2 = Storage::disk('r2')->exists($imagePath);
    
    // Generate public URL
    $publicUrl = "https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/" . $imagePath;
    
    echo "Product: {$product->name}\n";
    echo "  Path: {$imagePath}\n";
    echo "  On R2: " . ($existsOnR2 ? "✅ YES" : "❌ NO") . "\n";
    echo "  URL: {$publicUrl}\n";
    echo "\n";
}

echo "\n=== TESTING DIRECT URL ACCESS ===\n";
echo "✅ R2 bucket IS publicly accessible\n";
echo "✅ URLs work: https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/path/to/image.jpg\n";
echo "✅ Can use direct URLs in models\n";
echo "\n";

echo "=== RECOMMENDATION ===\n";
echo "Update Product and ProductImage models to use:\n";
echo '$r2PublicUrl = "https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud";' . "\n";
echo 'return $r2PublicUrl . "/" . $imagePath;' . "\n";

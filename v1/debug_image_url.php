<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "🔍 Debugging Image URL Generation\n";
echo str_repeat("=", 70) . "\n\n";

// Find the specific product mentioned by user
$product = Product::where('name', 'LIKE', '%test%')
    ->where('unique_id', '996')
    ->first();

if (!$product) {
    echo "❌ Product 'test' with ID 996 not found.\n";
    echo "Searching for any product with 'srm330' image...\n\n";
    
    $product = Product::where('image', 'LIKE', '%srm330%')->first();
}

if ($product) {
    echo "Product Found:\n";
    echo "  ID: {$product->product_id}\n";
    echo "  Name: {$product->product_name}\n";
    echo "  Unique ID: {$product->unique_id}\n\n";
    
    echo "Image Field (Database):\n";
    echo "  Raw Value: " . ($product->image ?? 'NULL') . "\n\n";
    
    echo "Testing image_url Accessor:\n";
    try {
        $imageUrl = $product->image_url;
        echo "  Generated URL: {$imageUrl}\n";
        
        // Check if it's the correct format
        if (str_contains($imageUrl, '/serve-image/')) {
            echo "  ✅ Uses /serve-image/ route (CORRECT)\n";
        } else if (str_contains($imageUrl, 'r2.cloudflarestorage.com')) {
            echo "  ❌ Uses R2 direct URL (WRONG - will show as text)\n";
        } else {
            echo "  ⚠️  Unknown URL format\n";
        }
        
        // Test if URL is accessible
        echo "\n  Testing URL accessibility...\n";
        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "  HTTP Response: {$httpCode}\n";
        if ($httpCode == 200) {
            echo "  ✅ Image accessible\n";
        } else {
            echo "  ❌ Image NOT accessible (will show as text in browser)\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Check ProductImages relation
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "Product Images (from product_images table):\n";
    $images = $product->productImages;
    
    if ($images->count() > 0) {
        foreach ($images->take(3) as $img) {
            echo "\n  Image ID: {$img->image_id}\n";
            echo "  Path (DB): {$img->image_path}\n";
            echo "  Generated URL: {$img->image_url}\n";
            
            if (str_contains($img->image_url, '/serve-image/')) {
                echo "  ✅ Uses /serve-image/ route\n";
            } else {
                echo "  ❌ NOT using /serve-image/ route\n";
            }
        }
    } else {
        echo "  (No images in product_images table)\n";
    }
    
} else {
    echo "❌ No products found matching criteria\n";
    echo "\nShowing first 5 products instead:\n\n";
    
    $products = Product::limit(5)->get();
    foreach ($products as $p) {
        echo "Product: {$p->product_name} (ID: {$p->product_id})\n";
        echo "  Image field: " . ($p->image ?? 'NULL') . "\n";
        echo "  Image URL: {$p->image_url}\n";
        echo "  Format: " . (str_contains($p->image_url, '/serve-image/') ? '✅ serve-image' : '❌ NOT serve-image') . "\n\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Debug complete!\n";

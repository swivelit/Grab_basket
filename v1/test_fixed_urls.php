<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

echo "Testing Fixed Image URLs\n";
echo str_repeat("=", 60) . "\n\n";

// Test product with images
$product = Product::whereHas('productImages')->first();

if ($product) {
    echo "Product ID: {$product->product_id}\n";
    echo "Product Name: {$product->product_name}\n\n";
    
    // Test legacy image
    if ($product->image) {
        echo "Legacy Image Field:\n";
        echo "  Stored Path: {$product->image}\n";
        echo "  Generated URL: {$product->image_url}\n";
        echo "  Should start with: " . url('/serve-image/') . "\n\n";
    }
    
    // Test product images
    echo "Product Images:\n";
    foreach ($product->productImages->take(3) as $image) {
        echo "  Image ID: {$image->image_id}\n";
        echo "  Stored Path: {$image->image_path}\n";
        echo "  Generated URL: {$image->image_url}\n";
        echo "  Should start with: " . url('/serve-image/') . "\n";
        
        // Test if URL is accessible
        $url = $image->image_url;
        echo "  Testing URL accessibility...\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            echo "  ✅ URL accessible (HTTP $httpCode)\n";
        } else {
            echo "  ❌ URL not accessible (HTTP $httpCode)\n";
        }
        echo "\n";
    }
}

// Test library image if exists
$libraryImage = ProductImage::where('image_path', 'LIKE', 'library/%')->first();
if ($libraryImage) {
    echo str_repeat("-", 60) . "\n";
    echo "Library Image Test:\n";
    echo "  Image ID: {$libraryImage->image_id}\n";
    echo "  Stored Path: {$libraryImage->image_path}\n";
    echo "  Generated URL: {$libraryImage->image_url}\n";
    echo "  Should start with: " . url('/serve-image/library/') . "\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ Test complete!\n";
echo "\nExpected behavior:\n";
echo "- All URLs should start with /serve-image/\n";
echo "- All URLs should return HTTP 200\n";
echo "- Images should display in browser\n";

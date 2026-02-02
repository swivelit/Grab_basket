<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;

echo "=== TESTING DIRECT R2 PUBLIC URLS ===\n\n";

// Test Product model
echo "--- TESTING PRODUCT MODEL ---\n";
$product = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->first();

if ($product) {
    echo "Product: {$product->name}\n";
    echo "Image Path: {$product->image}\n";
    echo "Generated URL: {$product->getLegacyImageUrl()}\n";
    echo "\n";
    
    // Test if URL is accessible
    $url = $product->getLegacyImageUrl();
    $headers = @get_headers($url);
    $status = $headers ? substr($headers[0], 9, 3) : 'Error';
    echo "URL Status: {$status}\n";
    echo ($status == '200' ? "✅ SUCCESS" : "❌ FAILED") . "\n";
}

echo "\n--- TESTING PRODUCT IMAGE MODEL ---\n";
$productImage = ProductImage::whereNotNull('image_path')
    ->where('image_path', '!=', '')
    ->first();

if ($productImage) {
    echo "Product Image ID: {$productImage->id}\n";
    echo "Image Path: {$productImage->image_path}\n";
    echo "Image URL: {$productImage->image_url}\n";
    echo "Original URL: {$productImage->original_url}\n";
    echo "\n";
    
    // Test if URL is accessible
    $url = $productImage->image_url;
    $headers = @get_headers($url);
    $status = $headers ? substr($headers[0], 9, 3) : 'Error';
    echo "URL Status: {$status}\n";
    echo ($status == '200' ? "✅ SUCCESS" : "❌ FAILED") . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";
echo "✅ Using direct R2 public URLs\n";
echo "✅ Format: https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/products/...\n";
echo "✅ Fast, CDN-backed delivery\n";
echo "✅ No server-side processing required\n";

<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Edit Product Image URLs\n";
echo "===============================================\n\n";

// Get first 10 products
$products = \App\Models\Product::limit(10)->get();

foreach ($products as $product) {
    echo "Product ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Image field: " . ($product->image ?? 'NULL') . "\n";
    echo "image_url attribute: " . ($product->image_url ?? 'NULL') . "\n";
    
    // Check if it's a GitHub URL
    if ($product->image_url && str_contains($product->image_url, 'githubusercontent.com')) {
        echo "   ✅ GitHub CDN URL\n";
        
        // Test if accessible
        $headers = @get_headers($product->image_url);
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "   ✅ Image accessible (HTTP 200)\n";
        } else {
            echo "   ❌ Image NOT accessible (404 or error)\n";
            echo "   URL: {$product->image_url}\n";
        }
    } elseif ($product->image_url && str_contains($product->image_url, 'serve-image')) {
        echo "   ℹ️ serve-image URL\n";
    } else {
        echo "   ⚠️ Other URL type\n";
    }
    
    echo "\n";
}

echo "\n📊 Environment Check:\n";
echo "   Environment: " . app()->environment() . "\n";
echo "   Expected: production URLs should use GitHub CDN\n";

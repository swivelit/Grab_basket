<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing Product 1284 Image Path\n";
echo "===============================================\n\n";

$product = \App\Models\Product::find(1284);

if ($product) {
    echo "Current image: {$product->image}\n";
    
    // Update to the existing file
    $product->image = 'products/seller-2/srm341-1760335961.jpg';
    $product->save();
    
    echo "✅ Updated to: {$product->image}\n";
    echo "\nVerifying...\n";
    
    $fullPath = storage_path('app/public/' . ltrim($product->image, '/'));
    echo "File exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
    echo "Image URL: {$product->image_url}\n";
    
    echo "\n🎯 Product image fixed! Please refresh the edit page.\n";
} else {
    echo "❌ Product not found\n";
}

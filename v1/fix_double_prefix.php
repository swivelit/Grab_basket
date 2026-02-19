<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing Double 'products/' Prefix\n";
echo "===============================================\n\n";

$products = \App\Models\Product::where('image', 'LIKE', 'products/products/%')->get();

echo "Found " . count($products) . " products with double prefix\n\n";

foreach ($products as $product) {
    $oldPath = $product->image;
    $newPath = str_replace('products/products/', 'products/', $oldPath);
    
    echo "Product {$product->id}: {$product->name}\n";
    echo "  Old: {$oldPath}\n";
    echo "  New: {$newPath}\n";
    
    $product->image = $newPath;
    $product->save();
    
    echo "  ✅ Fixed\n\n";
}

echo "===============================================\n";
echo "✅ All double prefixes fixed!\n";

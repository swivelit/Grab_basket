<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🔍 BULK IMPORT IMAGE DEBUG\n";
echo "===========================\n\n";

// Check recent products
$recentProducts = \App\Models\Product::orderBy('created_at', 'desc')->take(10)->get();

echo "📋 Last 10 products created:\n";
foreach($recentProducts as $product) {
    echo "- ID: {$product->id} | Name: {$product->name}\n";
    echo "  Image field: " . ($product->image ?: 'NULL') . "\n";
    echo "  Created: {$product->created_at}\n";
    
    // Check ProductImage entries
    $productImages = \App\Models\ProductImage::where('product_id', $product->id)->get();
    echo "  ProductImage records: " . $productImages->count() . "\n";
    foreach($productImages as $pi) {
        echo "    - Path: {$pi->image_path} | Primary: " . ($pi->is_primary ? 'Yes' : 'No') . "\n";
    }
    echo "\n";
}

echo "\n📊 Storage locations check:\n";

// Check if storage directories exist
$publicProductsPath = storage_path('app/public/products');
echo "Public products directory exists: " . (is_dir($publicProductsPath) ? 'YES' : 'NO') . "\n";
if (is_dir($publicProductsPath)) {
    $files = glob($publicProductsPath . '/*');
    echo "Files in public/products: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "Sample files: " . implode(', ', array_slice(array_map('basename', $files), 0, 5)) . "\n";
    }
}

echo "\n🔧 Recent log entries (image related):\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $lines = file($logPath);
    $imageLines = array_filter($lines, function($line) {
        return strpos(strtolower($line), 'image') !== false || 
               strpos(strtolower($line), 'bulk') !== false;
    });
    
    $recentImageLines = array_slice($imageLines, -10);
    foreach($recentImageLines as $line) {
        echo trim($line) . "\n";
    }
}

echo "\n🎯 Image assignment test:\n";
// Test image assignment logic
$testProduct = $recentProducts->first();
if ($testProduct) {
    echo "Testing with product: {$testProduct->name}\n";
    echo "Current image: " . ($testProduct->image ?: 'NULL') . "\n";
    echo "Image URL: " . $testProduct->image_url . "\n";
}
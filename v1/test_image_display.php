<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "=== Testing Product Image URLs ===" . PHP_EOL . PHP_EOL;

$product = App\Models\Product::latest()->first();

if (!$product) {
    echo "No products found!" . PHP_EOL;
    exit;
}

echo "Product ID: " . $product->id . PHP_EOL;
echo "Product Name: " . $product->name . PHP_EOL;
echo "Legacy Image Field: " . ($product->image ?? 'NULL') . PHP_EOL;
echo "Image URL Accessor: " . ($product->image_url ?? 'NULL') . PHP_EOL;
echo PHP_EOL;

echo "ProductImage Records: " . $product->productImages->count() . PHP_EOL;

if ($product->productImages->count() > 0) {
    echo PHP_EOL . "ProductImage Details:" . PHP_EOL;
    foreach ($product->productImages as $idx => $pi) {
        echo "  [{$idx}] ID: {$pi->id}" . PHP_EOL;
        echo "  [{$idx}] Path: {$pi->image_path}" . PHP_EOL;
        echo "  [{$idx}] URL: {$pi->image_url}" . PHP_EOL;
        echo "  [{$idx}] Primary: " . ($pi->is_primary ? 'YES' : 'NO') . PHP_EOL;
        
        // Check if file exists
        $publicExists = Storage::disk('public')->exists($pi->image_path);
        $r2Exists = false;
        try {
            $r2Exists = Storage::disk('r2')->exists($pi->image_path);
        } catch (\Throwable $e) {
            $r2Exists = false;
        }
        
        echo "  [{$idx}] Public Disk: " . ($publicExists ? '✅ EXISTS' : '❌ NOT FOUND') . PHP_EOL;
        echo "  [{$idx}] R2 Storage: " . ($r2Exists ? '✅ EXISTS' : '❌ NOT FOUND') . PHP_EOL;
        echo PHP_EOL;
    }
}

// Test legacy image path if set
if ($product->image) {
    echo "Legacy Image Path Check:" . PHP_EOL;
    $publicExists = Storage::disk('public')->exists($product->image);
    $r2Exists = false;
    try {
        $r2Exists = Storage::disk('r2')->exists($product->image);
    } catch (\Throwable $e) {
        $r2Exists = false;
    }
    
    echo "  Public Disk: " . ($publicExists ? '✅ EXISTS' : '❌ NOT FOUND') . PHP_EOL;
    echo "  R2 Storage: " . ($r2Exists ? '✅ EXISTS' : '❌ NOT FOUND') . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;

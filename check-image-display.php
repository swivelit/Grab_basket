<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Checking Product Image Display ===\n\n";

$products = Product::take(3)->get();

foreach ($products as $product) {
    echo "Product ID: {$product->id}\n";
    echo "Product Name: {$product->name}\n";
    echo "Image field: " . ($product->image ? $product->image : 'NULL') . "\n";
    echo "Image Data: " . (empty($product->image_data) ? 'None' : 'Yes (' . strlen($product->image_data) . ' chars)') . "\n";
    echo "Image MIME Type: " . ($product->image_mime_type ?? 'NULL') . "\n";
    echo "Image URL: {$product->image_url}\n";
    echo "Image URL Preview: " . substr($product->image_url, 0, 100) . "...\n";
    echo "----------------------------------------\n\n";
}

// Check if we have any products with database images
$dbImageCount = Product::whereNotNull('image_data')->count();
$fileImageCount = Product::whereNotNull('image')->count();

echo "Products with database images: {$dbImageCount}\n";
echo "Products with file images: {$fileImageCount}\n";
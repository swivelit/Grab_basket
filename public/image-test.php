<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "<h1>Image URL Test</h1>";

// Check product 28 specifically
$product = Product::find(28);
if ($product) {
    echo "<h2>Product ID: " . $product->id . "</h2>";
    echo "<p><strong>Product Name:</strong> " . $product->name . "</p>";
    echo "<p><strong>Raw Image Value:</strong> " . ($product->image ?? 'NULL') . "</p>";
    
    try {
        $imageUrl = $product->image_url;
        echo "<p><strong>Smart Image URL:</strong> " . $imageUrl . "</p>";
        
        // Display the image
        echo "<img src='{$imageUrl}' alt='{$product->name}' style='max-width: 300px; border: 1px solid #ccc;' onerror=\"this.src='https://via.placeholder.com/200?text=Image+Not+Found'\"/>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>Error getting image_url:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Product 28 not found</p>";
}

echo "<h2>Environment Info</h2>";
echo "<p><strong>APP_URL:</strong> " . config('app.url') . "</p>";
echo "<p><strong>Current URL:</strong> " . url('/') . "</p>";

?>
<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Testing Fixed Image URLs ===\n\n";

// Test products that should now have images
$productsWithImages = Product::whereNotNull('image')->take(5)->get();

foreach ($productsWithImages as $product) {
    echo "Product: {$product->name}\n";
    echo "Legacy image field: {$product->image}\n";
    echo "ProductImages count: " . $product->productImages->count() . "\n";
    echo "Generated image_url: {$product->image_url}\n";
    
    // Test if the URL works
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $product->image_url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$httpCode} " . ($httpCode == 200 ? "(✓ Working!)" : "(✗ Still not working)") . "\n";
    echo "---\n\n";
}

// Test the same products that would appear on index page
echo "=== Index Page Products Test ===\n";
$deals = Product::orderByDesc('discount')->take(3)->get();

foreach ($deals as $product) {
    echo "Deal Product: " . substr($product->name, 0, 40) . "\n";
    echo "Image URL: {$product->image_url}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $product->image_url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: {$httpCode} " . ($httpCode == 200 ? "(✓)" : "(✗)") . "\n\n";
}

echo "=== Summary ===\n";
echo "If images show HTTP 200, they should now display correctly on the index page!\n";
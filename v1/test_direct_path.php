<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Product;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing direct path URLs for products with linked images...\n\n";

// Get products that have recently linked images
$products = Product::whereHas('productImages', function($query) {
    $query->whereNotNull('image_path');
})->take(5)->get();

foreach ($products as $product) {
    echo "Product: {$product->name}\n";
    
    $imageUrl = $product->image_url;
    echo "Image URL: {$imageUrl}\n";
    
    // Try to access the URL and check response
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($imageUrl, false, $context);
    $httpCode = isset($http_response_header) ? $http_response_header[0] : 'No response';
    
    echo "HTTP Response: {$httpCode}\n";
    echo "Content length: " . (($response !== false) ? strlen($response) : '0') . " bytes\n";
    echo "---\n";
}

echo "\nTest complete!\n";
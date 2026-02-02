<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Index Page Image Analysis ===\n\n";

// Get the products that would be shown on the index page
try {
    // Skip flash sale products since the column doesn't exist
    echo "Flash Sale Products: 0 (column doesn't exist)\n";
    
    // Deals of the day (top discount products)
    $deals = Product::orderByDesc('discount')->take(12)->get();
    echo "Deals Products: " . $deals->count() . "\n";
    
    // Trending products (latest products)
    $trending = Product::orderBy('created_at', 'desc')->take(12)->get();
    echo "Trending Products: " . $trending->count() . "\n";
    
    // Test a few products from each category
    echo "\n=== Testing Image URLs ===\n";
    
    $testProducts = collect()
        ->merge($deals->take(3))
        ->merge($trending->take(3));
    
    foreach ($testProducts as $index => $product) {
        echo "\nProduct " . ($index + 1) . ":\n";
        echo "ID: {$product->id}\n";
        echo "Name: " . substr($product->name, 0, 50) . "\n";
        echo "Legacy image field: " . ($product->image ?? 'NULL') . "\n";
        
        // Check ProductImages
        $productImages = $product->productImages;
        echo "ProductImages count: " . $productImages->count() . "\n";
        
        if ($productImages->count() > 0) {
            foreach ($productImages as $img) {
                echo "  - ProductImage path: {$img->image_path}\n";
                echo "    ProductImage URL: {$img->image_url}\n";
                
                // Test if URL is accessible
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $img->image_url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                echo "    HTTP Status: {$httpCode}\n";
            }
        }
        
        // Test the main product image_url
        echo "Product image_url accessor: {$product->image_url}\n";
        
        // Test if the main URL is accessible
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $product->image_url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "Main image HTTP Status: {$httpCode}\n";
        
        echo "---\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Configuration Check ===\n";
echo "AWS_URL: " . (config('filesystems.disks.r2.url') ?: 'NOT SET') . "\n";
echo "Environment: " . app()->environment() . "\n";
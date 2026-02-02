<?php
// Simple test of the enhanced search functionality
echo "🔍 Testing Enhanced Search at /products endpoint\n";
echo "================================================\n\n";

// Check some products exist in database
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request to simulate the search
$request = Illuminate\Http\Request::create('/products?q=maya', 'GET');
$response = $kernel->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\n";

if ($response->getStatusCode() == 200) {
    echo "✅ Enhanced search endpoint working!\n";
    
    $content = $response->getContent();
    
    // Check for enhanced features
    $checks = [
        'product-image' => 'Product images',
        'Login to Add to Cart' => 'Guest login buttons',
        'Add to Cart' => 'Cart functionality',
        'bootstrap' => 'Bootstrap styling',
        'Search Results' => 'Search header',
        'price-badge' => 'Price styling',
        'card product-card' => 'Product cards'
    ];
    
    foreach ($checks as $searchFor => $description) {
        if (strpos($content, $searchFor) !== false) {
            echo "✅ $description - Found\n";
        } else {
            echo "❌ $description - Missing\n";
        }
    }
    
    // Count approximate products
    $productCount = substr_count($content, 'product-card');
    echo "\n📦 Found approximately $productCount products in search results\n";
    
} else {
    echo "❌ Search failed with status: " . $response->getStatusCode() . "\n";
    echo "Content preview: " . substr($response->getContent(), 0, 500) . "\n";
}

echo "\n🎯 Test Complete!\n";
?>
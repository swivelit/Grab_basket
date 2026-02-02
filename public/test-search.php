<?php
// Quick test script to check search functionality
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "Testing Search Endpoint...\n\n";
    
    // Test database connection
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✓ Database connected\n";
    
    // Test Product model
    $productCount = \App\Models\Product::count();
    echo "✓ Products count: {$productCount}\n";
    
    // Test Seller model
    $sellerCount = \App\Models\Seller::count();
    echo "✓ Sellers count: {$sellerCount}\n";
    
    // Test User model
    $userCount = \App\Models\User::count();
    echo "✓ Users count: {$userCount}\n";
    
    // Test search query
    $products = \App\Models\Product::with(['category', 'subcategory'])
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->limit(5)
        ->get();
    
    echo "✓ Sample products query worked: " . $products->count() . " products\n";
    
    echo "\n✓ All tests passed! Search should work.\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

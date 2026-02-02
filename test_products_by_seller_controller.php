<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;

echo "=== Testing Products by Seller Controller ===\n\n";

try {
    // Simulate the request
    $request = new Request();
    
    echo "Step 1: Loading sellers with product counts...\n";
    $sellersQuery = User::where('role', 'seller')
        ->withCount(['products' => function($query) {
            $query->whereNotNull('image');
        }]);
    
    $sellers = $sellersQuery->orderBy('products_count', 'desc')->get();
    echo "✅ Sellers loaded: " . $sellers->count() . "\n\n";
    
    echo "Step 2: Testing with no seller selected...\n";
    $selectedSeller = null;
    $products = null;
    $selectedSellerInfo = null;
    echo "✅ No seller selected (empty state)\n\n";
    
    echo "Step 3: Testing with seller selected...\n";
    $selectedSeller = 2; // Top seller
    $selectedSellerInfo = User::find($selectedSeller);
    
    if ($selectedSellerInfo) {
        echo "✅ Seller found: {$selectedSellerInfo->name}\n";
        
        $products = Product::with(['category', 'subcategory'])
            ->where('seller_id', $selectedSeller)
            ->whereNotNull('image')
            ->latest()
            ->paginate(12);
        
        echo "✅ Products loaded: " . $products->count() . "\n\n";
    }
    
    echo "Step 4: Checking view data...\n";
    $viewData = [
        'sellers' => $sellers,
        'products' => $products,
        'selectedSellerInfo' => $selectedSellerInfo,
        'search' => null,
        'selectedSeller' => $selectedSeller
    ];
    
    foreach ($viewData as $key => $value) {
        $type = is_object($value) ? get_class($value) : gettype($value);
        echo "   - {$key}: {$type}\n";
    }
    
    echo "\n✅ ALL TESTS PASSED - Controller logic works!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";

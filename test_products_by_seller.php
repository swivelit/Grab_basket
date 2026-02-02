<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;

echo "=== Testing Products by Seller Feature ===\n\n";

// Get all sellers with product counts
$sellers = User::where('role', 'seller')
    ->withCount(['products' => function($query) {
        $query->whereNotNull('image');
    }])
    ->orderBy('products_count', 'desc')
    ->get();

echo "✅ Total sellers: " . $sellers->count() . "\n\n";

echo "Top 10 sellers by product count:\n";
echo str_repeat("=", 80) . "\n";
printf("%-5s %-30s %-35s %s\n", "ID", "Name", "Email", "Products");
echo str_repeat("=", 80) . "\n";

foreach ($sellers->take(10) as $seller) {
    printf(
        "%-5d %-30s %-35s %d\n", 
        $seller->id, 
        substr($seller->name, 0, 28), 
        substr($seller->email, 0, 33), 
        $seller->products_count
    );
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Test getting products for a specific seller
$topSeller = $sellers->first();
if ($topSeller && $topSeller->products_count > 0) {
    echo "Testing product retrieval for top seller:\n";
    echo "Seller: {$topSeller->name} (ID: {$topSeller->id})\n";
    echo "Expected products: {$topSeller->products_count}\n\n";
    
    $products = Product::where('seller_id', $topSeller->id)
        ->whereNotNull('image')
        ->with(['category', 'subcategory'])
        ->limit(5)
        ->get();
    
    echo "✅ Retrieved {$products->count()} sample products:\n";
    foreach ($products as $product) {
        echo "   - [{$product->id}] {$product->name} - ₹{$product->price}\n";
        echo "     Category: " . ($product->category->name ?? 'N/A') . "\n";
        echo "     Image: " . ($product->image ? '✓' : '✗') . "\n";
    }
}

echo "\n=== Statistics ===\n";
$totalProducts = $sellers->sum('products_count');
$sellersWithProducts = $sellers->where('products_count', '>', 0)->count();
$sellersWithoutProducts = $sellers->where('products_count', '=', 0)->count();

echo "Total products (with images): {$totalProducts}\n";
echo "Sellers with products: {$sellersWithProducts}\n";
echo "Sellers without products: {$sellersWithoutProducts}\n";
echo "Average products per seller: " . ($sellersWithProducts > 0 ? round($totalProducts / $sellersWithProducts, 2) : 0) . "\n";

echo "\n=== Test Complete ===\n";

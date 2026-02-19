<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Product-Seller Relationship\n";
echo "====================================\n\n";

// Test 1: Get a product with seller
$product = App\Models\Product::with('seller')->first();

if ($product) {
    echo "✓ Product found: ID {$product->id}, Name: {$product->name}\n";
    echo "  Seller ID in product table: {$product->seller_id}\n";
    
    if ($product->seller) {
        echo "  ✓ Seller relationship loaded successfully!\n";
        echo "  Seller Store Name: {$product->seller->store_name}\n";
        echo "  Seller Address: {$product->seller->store_address}\n";
        echo "  Seller Contact: {$product->seller->store_contact}\n";
    } else {
        echo "  ✗ Seller relationship returned NULL\n";
        
        // Check if seller exists
        $seller = App\Models\Seller::find($product->seller_id);
        if ($seller) {
            echo "  ✗ BUT seller DOES exist in sellers table!\n";
            echo "    This means the relationship is broken.\n";
            echo "    Seller details: {$seller->store_name}\n";
        } else {
            echo "  ✗ Seller ID {$product->seller_id} does not exist in sellers table\n";
        }
    }
} else {
    echo "✗ No products found in database\n";
}

echo "\n";

// Test 2: Count products with seller_id
$productsWithSeller = App\Models\Product::whereNotNull('seller_id')->count();
echo "Products with seller_id: {$productsWithSeller}\n";

// Test 3: Count sellers
$sellersCount = App\Models\Seller::count();
echo "Total sellers in database: {$sellersCount}\n";

echo "\n";

// Test 4: Check if seller_ids in products match sellers table
$orphanProducts = App\Models\Product::whereNotNull('seller_id')
    ->whereNotIn('seller_id', App\Models\Seller::pluck('id'))
    ->count();
    
if ($orphanProducts > 0) {
    echo "⚠ WARNING: {$orphanProducts} products have seller_id that don't exist in sellers table\n";
    
    // Show some examples
    $examples = App\Models\Product::whereNotNull('seller_id')
        ->whereNotIn('seller_id', App\Models\Seller::pluck('id'))
        ->take(5)
        ->get(['id', 'name', 'seller_id']);
        
    echo "  Examples:\n";
    foreach ($examples as $ex) {
        echo "    - Product ID {$ex->id}: '{$ex->name}' has seller_id={$ex->seller_id} (doesn't exist)\n";
    }
} else {
    echo "✓ All products with seller_id have matching sellers\n";
}

echo "\nDone!\n";

<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Seller Info Display - All Scenarios\n";
echo "============================================\n\n";

// Scenario 1: Product with valid seller
echo "Scenario 1: Product with Valid Seller\n";
echo "--------------------------------------\n";
$product1 = App\Models\Product::with('seller')->where('seller_id', 2)->first();
if ($product1 && $product1->seller) {
    $seller1 = App\Models\Seller::where('email', $product1->seller->email)->first();
    echo "Product: {$product1->name}\n";
    echo "Seller ID: {$product1->seller_id}\n";
    echo "User Email: {$product1->seller->email}\n";
    
    if ($seller1 && $seller1->id > 0) {
        echo "✅ PASS: Seller info found\n";
        echo "   Store: {$seller1->store_name}\n";
        echo "   Address: {$seller1->store_address}\n";
        echo "   Contact: {$seller1->store_contact}\n";
        echo "   View will show: Full seller information\n";
    } else {
        echo "❌ FAIL: Seller info not found\n";
        echo "   View will show: 'Seller information is currently not available'\n";
    }
} else {
    echo "⚠️  No test product found\n";
}

echo "\n";

// Scenario 2: Check if any products have seller_id that doesn't exist
echo "Scenario 2: Products with Invalid seller_id\n";
echo "--------------------------------------------\n";
$invalidProducts = DB::table('products as p')
    ->leftJoin('users as u', 'p.seller_id', '=', 'u.id')
    ->whereNotNull('p.seller_id')
    ->whereNull('u.id')
    ->select('p.id', 'p.name', 'p.seller_id')
    ->take(5)
    ->get();

if ($invalidProducts->count() > 0) {
    echo "❌ FOUND {$invalidProducts->count()} products with invalid seller_id:\n";
    foreach ($invalidProducts as $inv) {
        echo "   - Product #{$inv->id}: {$inv->name} (seller_id={$inv->seller_id} doesn't exist)\n";
    }
    echo "   These products will show: 'Seller information is currently not available'\n";
} else {
    echo "✅ PASS: All products have valid seller_id\n";
}

echo "\n";

// Scenario 3: Users without seller table entry
echo "Scenario 3: Seller Users Without Seller Table Entry\n";
echo "-----------------------------------------------------\n";
$usersWithoutSeller = DB::table('users as u')
    ->leftJoin('sellers as s', 'u.email', '=', 's.email')
    ->where('u.role', 'seller')
    ->whereNull('s.id')
    ->select('u.id', 'u.name', 'u.email')
    ->get();

if ($usersWithoutSeller->count() > 0) {
    echo "❌ FOUND {$usersWithoutSeller->count()} seller users without sellers table entry:\n";
    foreach ($usersWithoutSeller as $u) {
        echo "   - User #{$u->id}: {$u->name} ({$u->email})\n";
        
        // Check if any products use this user
        $productCount = App\Models\Product::where('seller_id', $u->id)->count();
        if ($productCount > 0) {
            echo "     ⚠️  {$productCount} products use this seller!\n";
            echo "     These products will show: 'Seller information is currently not available'\n";
        }
    }
    
    echo "\n   FIX: Add entries to sellers table for these users\n";
} else {
    echo "✅ PASS: All seller users have sellers table entries\n";
}

echo "\n";

// Summary
echo "Summary\n";
echo "-------\n";
$totalProducts = App\Models\Product::count();
$productsWithValidSeller = DB::table('products as p')
    ->join('users as u', 'p.seller_id', '=', 'u.id')
    ->join('sellers as s', 'u.email', '=', 's.email')
    ->count();

$percentage = $totalProducts > 0 ? round(($productsWithValidSeller / $totalProducts) * 100, 2) : 0;

echo "Total products: {$totalProducts}\n";
echo "Products with valid seller info: {$productsWithValidSeller}\n";
echo "Coverage: {$percentage}%\n\n";

if ($percentage < 100) {
    $missing = $totalProducts - $productsWithValidSeller;
    echo "⚠️  {$missing} products ({100 - $percentage}%) will show 'Seller information is currently not available'\n";
} else {
    echo "✅ All products have seller information available!\n";
}

echo "\nDone!\n";

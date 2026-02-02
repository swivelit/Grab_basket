<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Finding Products Without Seller Info\n";
echo "=====================================\n\n";

// Get products that don't have matching seller info
$productsWithoutSeller = DB::table('products as p')
    ->leftJoin('users as u', 'p.seller_id', '=', 'u.id')
    ->leftJoin('sellers as s', 'u.email', '=', 's.email')
    ->whereNotNull('p.seller_id')
    ->whereNull('s.id')
    ->select('p.id', 'p.name', 'p.seller_id', 'u.name as user_name', 'u.email as user_email')
    ->get();

echo "Found {$productsWithoutSeller->count()} products without seller info:\n\n";

if ($productsWithoutSeller->count() > 0) {
    foreach ($productsWithoutSeller->take(25) as $p) {
        echo "Product #{$p->id}: {$p->name}\n";
        echo "  seller_id: {$p->seller_id}\n";
        
        if ($p->user_name) {
            echo "  User: {$p->user_name} ({$p->user_email})\n";
            echo "  ❌ Problem: User exists but no entry in sellers table\n";
        } else {
            echo "  ❌ Problem: seller_id doesn't match any user\n";
        }
        echo "\n";
    }
    
    // Group by seller_id to see patterns
    echo "\nGrouped by seller_id:\n";
    $grouped = $productsWithoutSeller->groupBy('seller_id');
    foreach ($grouped as $sellerId => $products) {
        $count = $products->count();
        $firstProduct = $products->first();
        echo "  seller_id {$sellerId}: {$count} products\n";
        if ($firstProduct->user_name) {
            echo "    User: {$firstProduct->user_name} ({$firstProduct->user_email})\n";
            echo "    ⚠️  Need to create seller entry for this user!\n";
        } else {
            echo "    ⚠️  Invalid seller_id - no user exists!\n";
        }
    }
}

echo "\nDone!\n";

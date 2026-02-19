<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Product-Seller ID Mismatches\n";
echo "======================================\n\n";

// Get all seller IDs from sellers table
$sellerIds = DB::table('sellers')->pluck('id')->toArray();
echo "Valid Seller IDs in sellers table: " . implode(', ', $sellerIds) . "\n\n";

// Get unique seller_id values from products
$productSellerIds = DB::table('products')
    ->whereNotNull('seller_id')
    ->distinct()
    ->pluck('seller_id')
    ->toArray();

echo "Seller IDs used in products table: " . implode(', ', $productSellerIds) . "\n\n";

// Find mismatches
$orphanSellerIds = array_diff($productSellerIds, $sellerIds);

if (count($orphanSellerIds) > 0) {
    echo "🔴 PROBLEM FOUND: Products have seller_id values that don't exist in sellers table!\n";
    echo "   Invalid seller_id values: " . implode(', ', $orphanSellerIds) . "\n\n";
    
    foreach ($orphanSellerIds as $invalidId) {
        $count = DB::table('products')->where('seller_id', $invalidId)->count();
        echo "   - {$count} products have seller_id = {$invalidId} (doesn't exist)\n";
        
        // Show sample products
        $samples = DB::table('products')
            ->where('seller_id', $invalidId)
            ->take(3)
            ->get(['id', 'name', 'seller_id']);
            
        foreach ($samples as $sample) {
            echo "     • Product #{$sample->id}: {$sample->name}\n";
        }
    }
    
    echo "\n💡 SOLUTION: These products need their seller_id updated to valid seller IDs.\n";
    echo "   Or create sellers with IDs: " . implode(', ', $orphanSellerIds) . "\n";
} else {
    echo "✅ All product seller_id values have matching sellers!\n";
}

echo "\nDone!\n";

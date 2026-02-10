<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Seller Data Mismatch\n";
echo "==============================\n\n";

// Check products table
$productSample = DB::table('products')->first();
if ($productSample) {
    echo "Sample product from products table:\n";
    echo "  ID: {$productSample->id}\n";
    echo "  Name: {$productSample->name}\n";
    echo "  Seller ID: {$productSample->seller_id}\n\n";
}

// Check sellers table
$sellerSample = DB::table('sellers')->first();
if ($sellerSample) {
    echo "Sample seller from sellers table:\n";
    echo "  ID: {$sellerSample->id}\n";
    echo "  Store Name: {$sellerSample->store_name}\n\n";
} else {
    echo "⚠ NO SELLERS FOUND IN SELLERS TABLE!\n\n";
}

// Count mismatches
$totalProducts = DB::table('products')->whereNotNull('seller_id')->count();
echo "Products with seller_id: {$totalProducts}\n";

$totalSellers = DB::table('sellers')->count();
echo "Total sellers: {$totalSellers}\n\n";

if ($totalSellers == 0) {
    echo "🔴 PROBLEM FOUND: Sellers table is EMPTY!\n";
    echo "   Products have seller_id but no sellers exist.\n";
    echo "   This is why seller info shows as 'not available'.\n\n";
    
    // Check users table for seller data
    $usersWithRole = DB::table('users')->where('role', 'seller')->count();
    echo "Users with role='seller': {$usersWithRole}\n";
    
    if ($usersWithRole > 0) {
        echo "\n💡 SOLUTION: Seller data may be in 'users' table.\n";
        echo "   Need to either:\n";
        echo "   1. Migrate seller data from users to sellers table, OR\n";
        echo "   2. Change Product relationship back to User model\n";
    }
}

echo "\nDone!\n";

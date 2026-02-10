<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debugging Seller Information Display Issue\n";
echo "==========================================\n\n";

// Test with a random product
$product = App\Models\Product::with('seller')->inRandomOrder()->first();

if (!$product) {
    echo "❌ No products found in database\n";
    exit;
}

echo "Testing Product:\n";
echo "  ID: {$product->id}\n";
echo "  Name: {$product->name}\n";
echo "  Seller ID: {$product->seller_id}\n\n";

// Step 1: Check if seller (User) relationship works
echo "Step 1: Product->seller relationship (User model)\n";
if ($product->seller) {
    echo "  ✅ User found:\n";
    echo "     User ID: {$product->seller->id}\n";
    echo "     Name: {$product->seller->name}\n";
    echo "     Email: {$product->seller->email}\n";
    echo "     Role: " . ($product->seller->role ?? 'N/A') . "\n\n";
} else {
    echo "  ❌ No user found with ID {$product->seller_id}\n";
    echo "     This product has invalid seller_id!\n\n";
    
    // Check if user exists at all
    $userExists = App\Models\User::where('id', $product->seller_id)->exists();
    echo "     User ID {$product->seller_id} exists: " . ($userExists ? 'YES' : 'NO') . "\n\n";
    exit;
}

// Step 2: Try to get seller info from sellers table
echo "Step 2: Lookup in sellers table by email\n";
$sellerInfo = App\Models\Seller::where('email', $product->seller->email)->first();

if ($sellerInfo) {
    echo "  ✅ Seller info found:\n";
    echo "     Seller ID: {$sellerInfo->id}\n";
    echo "     Store Name: {$sellerInfo->store_name}\n";
    echo "     Store Address: {$sellerInfo->store_address}\n";
    echo "     Store Contact: {$sellerInfo->store_contact}\n\n";
} else {
    echo "  ❌ No seller info found for email: {$product->seller->email}\n\n";
    
    // Check all sellers
    echo "  Available sellers in sellers table:\n";
    $allSellers = App\Models\Seller::all(['id', 'email', 'store_name']);
    foreach ($allSellers as $s) {
        echo "     - Seller ID {$s->id}: {$s->store_name} ({$s->email})\n";
    }
    echo "\n";
}

// Step 3: Check what the controller would return
echo "Step 3: What controller returns:\n";
if ($sellerInfo) {
    echo "  ✅ Seller object with store info\n";
    echo "     Will display: {$sellerInfo->store_name}\n";
    echo "     Address: {$sellerInfo->store_address}\n";
    echo "     Contact: {$sellerInfo->store_contact}\n";
} else {
    echo "  ⚠️  Dummy seller object (fallback)\n";
    echo "     Will display: 'Store Not Available'\n";
    echo "     Message: 'Seller information is currently not available for this product'\n";
}

echo "\n";

// Step 4: Statistics
echo "Statistics:\n";
echo "-----------\n";
$totalProducts = App\Models\Product::count();
$productsWithSeller = App\Models\Product::whereNotNull('seller_id')->count();
$validUsers = App\Models\User::where('role', 'seller')->count();
$validSellers = App\Models\Seller::count();

echo "Total products: {$totalProducts}\n";
echo "Products with seller_id: {$productsWithSeller}\n";
echo "Users with role='seller': {$validUsers}\n";
echo "Sellers in sellers table: {$validSellers}\n\n";

// Check for orphan products (seller_id doesn't match any user)
$orphanCount = DB::table('products')
    ->whereNotNull('seller_id')
    ->whereNotIn('seller_id', DB::table('users')->pluck('id'))
    ->count();

if ($orphanCount > 0) {
    echo "⚠️  WARNING: {$orphanCount} products have seller_id that don't exist in users table!\n";
    
    $examples = DB::table('products')
        ->whereNotNull('seller_id')
        ->whereNotIn('seller_id', DB::table('users')->pluck('id'))
        ->take(5)
        ->get(['id', 'name', 'seller_id']);
    
    echo "   Examples:\n";
    foreach ($examples as $ex) {
        echo "     - Product #{$ex->id}: {$ex->name} (seller_id={$ex->seller_id})\n";
    }
    echo "\n";
}

// Check for users without seller info
$usersWithoutSellerInfo = DB::table('users')
    ->where('role', 'seller')
    ->whereNotIn('email', DB::table('sellers')->pluck('email'))
    ->count();

if ($usersWithoutSellerInfo > 0) {
    echo "⚠️  WARNING: {$usersWithoutSellerInfo} seller users have no entry in sellers table!\n";
    
    $examples = DB::table('users')
        ->where('role', 'seller')
        ->whereNotIn('email', DB::table('sellers')->pluck('email'))
        ->take(5)
        ->get(['id', 'name', 'email']);
    
    echo "   Examples:\n";
    foreach ($examples as $ex) {
        echo "     - User #{$ex->id}: {$ex->name} ({$ex->email})\n";
    }
    echo "\n   These sellers won't show store information!\n";
}

echo "\nDone!\n";

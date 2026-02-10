<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating Missing Seller Entries\n";
echo "================================\n\n";

// Get users who are sellers but don't have seller table entries
$usersWithoutSellers = DB::table('users as u')
    ->leftJoin('sellers as s', 'u.email', '=', 's.email')
    ->where('u.role', 'seller')
    ->whereNull('s.id')
    ->select('u.*')
    ->get();

if ($usersWithoutSellers->count() == 0) {
    echo "✅ All seller users already have sellers table entries!\n";
    exit;
}

echo "Found {$usersWithoutSellers->count()} seller users without sellers table entries:\n\n";

foreach ($usersWithoutSellers as $user) {
    echo "User #{$user->id}: {$user->name} ({$user->email})\n";
    
    // Check how many products they have
    $productCount = DB::table('products')->where('seller_id', $user->id)->count();
    echo "  Products: {$productCount}\n";
    
    // Create seller entry
    echo "  Creating seller entry...\n";
    
    try {
        $sellerId = DB::table('sellers')->insertGetId([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'store_name' => $user->name . "'s Store", // Default store name
            'store_address' => 'Please update store address',
            'store_contact' => $user->phone ?? 'Please update contact',
            'billing_address' => null,
            'state' => null,
            'city' => null,
            'pincode' => null,
            'gst_number' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "  ✅ Created seller entry with ID: {$sellerId}\n";
        echo "  Store Name: {$user->name}'s Store\n";
        echo "  ⚠️  Seller should update their store information!\n\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Error: {$e->getMessage()}\n\n";
    }
}

echo "\n";
echo "Verification:\n";
echo "-------------\n";

// Check coverage again
$totalProducts = DB::table('products')->count();
$productsWithValidSeller = DB::table('products as p')
    ->join('users as u', 'p.seller_id', '=', 'u.id')
    ->join('sellers as s', 'u.email', '=', 's.email')
    ->count();

$percentage = $totalProducts > 0 ? round(($productsWithValidSeller / $totalProducts) * 100, 2) : 0;

echo "Total products: {$totalProducts}\n";
echo "Products with valid seller info: {$productsWithValidSeller}\n";
echo "Coverage: {$percentage}%\n\n";

if ($percentage >= 100) {
    echo "✅ SUCCESS! All products now have seller information!\n";
} else {
    $missing = $totalProducts - $productsWithValidSeller;
    echo "⚠️  {$missing} products still missing seller info\n";
}

echo "\nDone!\n";

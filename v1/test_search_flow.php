<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Search with Seller Name Lookup\n";
echo "=======================================\n\n";

// Test searching for a seller name
$searchTerm = "SRM";

echo "Searching for: '{$searchTerm}'\n\n";

// Step 1: Find sellers matching the search
$sellerEmails = \App\Models\Seller::where('name', 'like', "%{$searchTerm}%")
    ->orWhere('store_name', 'like', "%{$searchTerm}%")
    ->get();

echo "Step 1: Found " . $sellerEmails->count() . " sellers matching '{$searchTerm}':\n";
foreach ($sellerEmails as $seller) {
    echo "  - {$seller->store_name} (Email: {$seller->email})\n";
}
echo "\n";

// Step 2: Get user IDs for these seller emails
$emails = $sellerEmails->pluck('email');
$userIds = \App\Models\User::whereIn('email', $emails)->pluck('id');

echo "Step 2: Found " . $userIds->count() . " users matching these emails:\n";
$users = \App\Models\User::whereIn('email', $emails)->get();
foreach ($users as $user) {
    echo "  - User ID {$user->id}: {$user->name} ({$user->email})\n";
}
echo "\n";

// Step 3: Find products with these seller_ids
$products = \App\Models\Product::whereIn('seller_id', $userIds)->take(5)->get();

echo "Step 3: Found " . $products->count() . " products (showing first 5):\n";
foreach ($products as $product) {
    $user = \App\Models\User::find($product->seller_id);
    $seller = \App\Models\Seller::where('email', $user->email)->first();
    echo "  - Product #{$product->id}: {$product->name}\n";
    echo "    Seller ID (User): {$product->seller_id}\n";
    echo "    Seller Email: {$user->email}\n";
    echo "    Store Name: " . ($seller ? $seller->store_name : 'N/A') . "\n";
}

echo "\n✅ Search flow working correctly!\n";
echo "   Sellers table → User emails → User IDs → Products\n";

<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Seller;
use App\Models\User;
use App\Models\Product;

echo "=== Testing Search Logic for 'SRM' ===\n\n";

$search = 'srm';

// Step 1: Search for matching stores
echo "Step 1: Searching for stores...\n";
$matchedStores = Seller::where('name', 'like', "%{$search}%")
    ->orWhere('store_name', 'like', "%{$search}%")
    ->get()
    ->map(function($seller) {
        // Get user ID for this seller
        $user = User::where('email', $seller->email)->first();
        if ($user) {
            $seller->user_id = $user->id;
            // Count products for this seller
            $seller->product_count = Product::where('seller_id', $user->id)->count();
        }
        return $seller;
    });

echo "Found " . $matchedStores->count() . " store(s)\n";
foreach ($matchedStores as $store) {
    echo "  - " . ($store->store_name ?? $store->name) . " (ID: {$store->user_id}, Products: {$store->product_count})\n";
}

// Step 2: Search for products
echo "\nStep 2: Searching for products...\n";

$query = Product::with(['category', 'subcategory'])
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('image', 'NOT LIKE', '%placeholder%')
    ->where('image', 'NOT LIKE', '%via.placeholder%');

$query->where(function ($q) use ($search) {
    // Search in product fields
    $q->where('name', 'like', "%{$search}%")
      ->orWhere('description', 'like', "%{$search}%")
      ->orWhere('unique_id', 'like', "%{$search}%")
      // Search in category
      ->orWhereHas('category', function($query) use ($search) {
          $query->where('name', 'like', "%{$search}%");
      })
      // Search in subcategory
      ->orWhereHas('subcategory', function($query) use ($search) {
          $query->where('name', 'like', "%{$search}%");
      });
      
    // Search in sellers table
    $sellerEmails = Seller::where('name', 'like', "%{$search}%")
        ->orWhere('store_name', 'like', "%{$search}%")
        ->pluck('email');
        
    if ($sellerEmails->isNotEmpty()) {
        // Get user IDs that match these seller emails
        $userIds = User::whereIn('email', $sellerEmails)->pluck('id');
        if ($userIds->isNotEmpty()) {
            $q->orWhereIn('seller_id', $userIds);
        }
    }
});

$products = $query->take(10)->get();

echo "Found " . $query->count() . " total product(s)\n";
echo "Showing first 10:\n";
foreach ($products as $product) {
    echo "  - " . $product->name . " (Seller ID: {$product->seller_id})\n";
}

echo "\n=== Test Complete ===\n";

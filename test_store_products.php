<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Seller;
use App\Models\User;
use App\Models\Product;

echo "=== Testing Store Products Page (Store ID 4) ===\n\n";

// Get seller with ID 4
$seller = Seller::find(4);

if (!$seller) {
    echo "❌ ERROR: Seller with ID 4 not found!\n";
    exit(1);
}

echo "✅ Seller found:\n";
echo "   - ID: {$seller->id}\n";
echo "   - Email: {$seller->email}\n";
echo "   - Store Name: {$seller->store_name}\n\n";

// Find the corresponding user by email
$user = User::where('email', $seller->email)->first();

if (!$user) {
    echo "❌ WARNING: No User found with email {$seller->email}\n";
    echo "   This seller has no matching user account!\n";
    exit(0);
}

echo "✅ Matching User found:\n";
echo "   - User ID: {$user->id}\n";
echo "   - Email: {$user->email}\n";
echo "   - Role: {$user->role}\n\n";

// Get products for this user ID
$products = Product::where('seller_id', $user->id)
    ->whereNotNull('image')
    ->get();

echo "✅ Products found: " . $products->count() . "\n\n";

if ($products->count() > 0) {
    echo "First 5 products:\n";
    foreach ($products->take(5) as $product) {
        echo "   - [{$product->id}] {$product->name} - ₹{$product->price}\n";
        echo "     Image: " . ($product->image ?: 'N/A') . "\n";
    }
} else {
    echo "⚠️  No products with images found for this seller\n";
    
    // Check if there are products without images
    $allProducts = Product::where('seller_id', $user->id)->count();
    if ($allProducts > 0) {
        echo "   Note: Seller has {$allProducts} products but none have images\n";
    }
}

echo "\n=== Test Complete ===\n";

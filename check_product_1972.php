<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Product ID 1972\n";
echo "========================\n\n";

// Get product 1972
$product = App\Models\Product::with(['category', 'subcategory', 'seller'])->find(1972);

if (!$product) {
    echo "❌ Product ID 1972 NOT FOUND in database\n";
    exit;
}

echo "Product Details:\n";
echo "  ID: {$product->id}\n";
echo "  Name: {$product->name}\n";
echo "  Price: ₹{$product->price}\n";
echo "  Seller ID: {$product->seller_id}\n";
echo "  Category: " . ($product->category ? $product->category->name : 'N/A') . "\n";
echo "  Subcategory: " . ($product->subcategory ? $product->subcategory->name : 'N/A') . "\n";
echo "  Image: " . ($product->image ?? 'NULL') . "\n\n";

// Check seller (User)
echo "Seller (User) Information:\n";
if ($product->seller) {
    echo "  ✅ User found:\n";
    echo "     User ID: {$product->seller->id}\n";
    echo "     Name: {$product->seller->name}\n";
    echo "     Email: {$product->seller->email}\n";
    echo "     Role: " . ($product->seller->role ?? 'N/A') . "\n\n";
    
    // Check seller info from sellers table
    echo "Seller Business Info:\n";
    $sellerInfo = App\Models\Seller::where('email', $product->seller->email)->first();
    
    if ($sellerInfo) {
        echo "  ✅ Seller info found:\n";
        echo "     Seller ID: {$sellerInfo->id}\n";
        echo "     Store Name: {$sellerInfo->store_name}\n";
        echo "     Address: {$sellerInfo->store_address}\n";
        echo "     Contact: {$sellerInfo->store_contact}\n\n";
    } else {
        echo "  ❌ No seller info in sellers table\n";
        echo "     Email: {$product->seller->email}\n";
        echo "     This product will show: 'Seller information is currently not available'\n\n";
    }
} else {
    echo "  ❌ No user found with seller_id: {$product->seller_id}\n\n";
}

// Check reviews
$reviewCount = App\Models\Review::where('product_id', 1972)->count();
echo "Reviews: {$reviewCount}\n\n";

// Check other products from same seller
$otherProductsCount = App\Models\Product::where('seller_id', $product->seller_id)
    ->where('id', '!=', 1972)
    ->count();
echo "Other products from same seller: {$otherProductsCount}\n\n";

// Simulate what the controller would return
echo "Controller Output:\n";
echo "------------------\n";
try {
    $seller = null;
    if ($product->seller && $product->seller->email) {
        $seller = App\Models\Seller::where('email', $product->seller->email)->first();
    }
    
    if ($seller && $seller->id > 0) {
        echo "✅ Page will display:\n";
        echo "   Store Name: {$seller->store_name}\n";
        echo "   Address: {$seller->store_address}\n";
        echo "   Contact: {$seller->store_contact}\n";
        echo "   'View Store Products' button\n";
    } else {
        echo "⚠️  Page will display:\n";
        echo "   'Seller information is currently not available for this product'\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\nDone!\n";

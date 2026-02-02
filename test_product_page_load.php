<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Product Page Load for Product 1972\n";
echo "===========================================\n\n";

try {
    // Simulate what ProductController::show() does
    $id = 1972;
    $product = App\Models\Product::with(['category', 'subcategory', 'seller'])->findOrFail($id);
    
    echo "✅ Product loaded successfully\n";
    echo "   Name: {$product->name}\n";
    echo "   Price: ₹{$product->price}\n\n";
    
    // Get seller info
    $seller = null;
    if ($product->seller && $product->seller->email) {
        $seller = App\Models\Seller::where('email', $product->seller->email)->first();
    }
    
    if ($seller) {
        echo "✅ Seller info loaded\n";
        echo "   Store: {$seller->store_name}\n\n";
    } else {
        echo "⚠️  No seller info (will show 'not available' message)\n\n";
    }
    
    // Get reviews
    $reviews = App\Models\Review::where('product_id', $product->id)->with('user')->latest()->get();
    echo "✅ Reviews loaded: {$reviews->count()} reviews\n\n";
    
    // Get other products
    $otherProducts = App\Models\Product::where('seller_id', $product->seller_id)
        ->where('id', '!=', $product->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->latest()->take(8)->get();
    
    echo "✅ Other products loaded: {$otherProducts->count()} products\n\n";
    
    // Check image
    echo "Image Details:\n";
    echo "  Path: {$product->image}\n";
    echo "  Image URL: {$product->image_url}\n";
    echo "  Original URL: {$product->original_image_url}\n\n";
    
    echo "✅ SUCCESS! Product page should load without errors\n";
    echo "\nPage URL: https://grabbaskets.laravel.cloud/product/1972\n";
    echo "Expected content:\n";
    echo "  - Product: Berry Honey(300g)\n";
    echo "  - Price: ₹239.00\n";
    echo "  - Store Info: Maltrix Honey\n";
    echo "  - Address: 42A1,Anamalai Nagar, 3rd street Koduvilarpatti,Theni - 625513\n";
    echo "  - Contact: 9080584167\n";
    echo "  - Other products from seller: {$otherProducts->count()}\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}\n";
    echo "   Line: {$e->getLine()}\n";
    echo "\n   This is the error that would appear on the product page.\n";
}

echo "\nDone!\n";

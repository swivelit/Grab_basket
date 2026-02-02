<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Fixed Seller Relationship\n";
echo "==================================\n\n";

// Test with a product that has seller_id = 2
$product = App\Models\Product::with('seller')->where('seller_id', 2)->first();

if ($product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Seller ID in product: {$product->seller_id}\n\n";
    
    if ($product->seller) {
        echo "✓ User relationship loaded:\n";
        echo "  User Name: {$product->seller->name}\n";
        echo "  User Email: {$product->seller->email}\n\n";
        
        // Now get seller info from sellers table
        $sellerInfo = App\Models\Seller::where('email', $product->seller->email)->first();
        
        if ($sellerInfo) {
            echo "✓ Seller info found in sellers table:\n";
            echo "  Seller ID: {$sellerInfo->id}\n";
            echo "  Store Name: {$sellerInfo->store_name}\n";
            echo "  Store Address: {$sellerInfo->store_address}\n";
            echo "  Store Contact: {$sellerInfo->store_contact}\n\n";
            echo "✅ SUCCESS! Seller information will now display to buyers!\n";
        } else {
            echo "✗ No seller info found in sellers table for email: {$product->seller->email}\n";
        }
    } else {
        echo "✗ No user found with ID {$product->seller_id}\n";
    }
} else {
    echo "No product found with seller_id = 13\n";
}

echo "\nDone!\n";

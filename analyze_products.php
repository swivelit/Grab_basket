<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Seller;
use App\Models\Product;

echo "=== Product Assignment Analysis ===\n";

// Get seller
$seller = Seller::where('email', 'swivel.training@gmail.com')->first();

if (!$seller) {
    echo "❌ Seller not found with email: swivel.training@gmail.com\n";
    exit(1);
}

echo "✅ Seller found: {$seller->name} (ID: {$seller->id})\n\n";

// Get total product counts
$totalProducts = Product::count();
$productsWithImages = Product::whereNotNull('image')->where('image', '!=', '')->count();
$productsWithoutImages = Product::whereNull('image')->orWhere('image', '')->count();
$productsAssignedToSeller = Product::where('seller_id', $seller->id)->count();
$unassignedProducts = Product::where('seller_id', '!=', $seller->id)
                            ->orWhereNull('seller_id')
                            ->orWhere('seller_id', 0)
                            ->count();

echo "📊 Current Database Status:\n";
echo "Total products in database: {$totalProducts}\n";
echo "Products with images: {$productsWithImages}\n";
echo "Products without images: {$productsWithoutImages}\n";
echo "Products assigned to {$seller->name}: {$productsAssignedToSeller}\n";
echo "Unassigned/other seller products: {$unassignedProducts}\n\n";

if ($totalProducts != 488) {
    echo "⚠️  Note: You mentioned 488 products, but database shows {$totalProducts} products.\n";
    echo "This script will assign ALL {$totalProducts} existing products to the seller.\n\n";
}

// Show products assigned to other sellers if any
if ($unassignedProducts > 0) {
    echo "🔍 Products not assigned to {$seller->name}:\n";
    $otherProducts = Product::where('seller_id', '!=', $seller->id)
                           ->orWhereNull('seller_id')
                           ->orWhere('seller_id', 0)
                           ->select('id', 'name', 'seller_id')
                           ->take(10)
                           ->get();
    
    foreach ($otherProducts as $product) {
        $sellerInfo = $product->seller_id ? "Seller ID: {$product->seller_id}" : "Unassigned";
        echo "   • ID {$product->id}: {$product->name} ({$sellerInfo})\n";
    }
    
    if ($unassignedProducts > 10) {
        echo "   ... and " . ($unassignedProducts - 10) . " more\n";
    }
    echo "\n";
}

echo "Ready to assign all products to {$seller->name}? (This will update {$unassignedProducts} products)\n";
?>
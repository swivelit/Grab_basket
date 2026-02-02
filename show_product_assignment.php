<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;

echo "=== Product Assignment Status ===\n";

$seller = Seller::where('email', 'swivel.training@gmail.com')->first();

if ($seller) {
    echo "Seller: {$seller->name} (ID: {$seller->id})\n";
    echo "Email: {$seller->email}\n\n";
    
    // Get products by category for this seller
    $categories = Category::all();
    
    $totalProducts = 0;
    foreach ($categories as $category) {
        $productCount = Product::where('category_id', $category->id)
                               ->where('seller_id', $seller->id)
                               ->count();
        if ($productCount > 0) {
            echo "📦 {$category->name}: {$productCount} products\n";
            
            // Show first few products as examples
            $examples = Product::where('category_id', $category->id)
                              ->where('seller_id', $seller->id)
                              ->take(3)
                              ->get();
            foreach ($examples as $product) {
                echo "   • {$product->name} (₹{$product->price})\n";
            }
            if ($productCount > 3) {
                echo "   ... and " . ($productCount - 3) . " more\n";
            }
            echo "\n";
            $totalProducts += $productCount;
        }
    }
    
    echo "🎯 Total products assigned to {$seller->name}: {$totalProducts}\n";
    
    // Check if there are any unassigned products
    $unassigned = Product::whereNull('seller_id')->orWhere('seller_id', 0)->count();
    if ($unassigned > 0) {
        echo "⚠️  Unassigned products: {$unassigned}\n";
    } else {
        echo "✅ All products are properly assigned!\n";
    }
    
} else {
    echo "❌ Seller not found\n";
}
?>
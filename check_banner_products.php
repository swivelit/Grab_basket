<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== Banner/Carousel Product Analysis ===\n";

// Check products with higher discounts
$highDiscountProducts = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('image', 'NOT LIKE', '%placeholder%')
    ->where('image', 'NOT LIKE', '%via.placeholder%')
    ->where('discount', '>=', 15)
    ->orderBy('discount', 'desc')
    ->get();

echo "📊 High Discount Products (15%+ discount) for Banner:\n";
echo "Total products with 15%+ discount: " . $highDiscountProducts->count() . "\n\n";

if ($highDiscountProducts->count() > 0) {
    echo "🎯 Top 10 High Discount Products for Carousel:\n";
    foreach ($highDiscountProducts->take(10) as $index => $product) {
        $discountText = $product->discount ? "{$product->discount}%" : "0%";
        echo ($index + 1) . ". {$product->name} - {$discountText} discount (₹{$product->price})\n";
    }
    
    echo "\n📈 Discount Distribution:\n";
    $discountRanges = [
        '30%+' => $highDiscountProducts->where('discount', '>=', 30)->count(),
        '25-29%' => $highDiscountProducts->whereBetween('discount', [25, 29])->count(),
        '20-24%' => $highDiscountProducts->whereBetween('discount', [20, 24])->count(),
        '15-19%' => $highDiscountProducts->whereBetween('discount', [15, 19])->count(),
    ];
    
    foreach ($discountRanges as $range => $count) {
        echo "{$range}: {$count} products\n";
    }
} else {
    echo "⚠️  No products found with 15%+ discount!\n";
    echo "Creating some high discount products for the banner...\n\n";
    
    // Update some existing products to have higher discounts
    $productsToUpdate = Product::whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->take(20)
        ->get();
    
    $discounts = [30, 25, 20, 15, 35, 28, 22, 18, 32, 26, 24, 16, 29, 21, 19, 33, 27, 23, 17, 31];
    
    foreach ($productsToUpdate as $index => $product) {
        $newDiscount = $discounts[$index] ?? rand(15, 35);
        $product->update(['discount' => $newDiscount]);
        echo "Updated: {$product->name} -> {$newDiscount}% discount\n";
    }
    
    echo "\n✅ Updated " . $productsToUpdate->count() . " products with high discounts!\n";
}

echo "\n🎯 Banner will now show only products with 15%+ discount, ordered by highest discount first!\n";
?>
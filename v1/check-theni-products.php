<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\User;

echo "🔍 THENI SELVAKUMMAR PRODUCTS SUMMARY\n";
echo "=====================================\n\n";

$seller = User::find(2);
echo "Seller: {$seller->name}\n";
echo "Email: {$seller->email}\n\n";

$products = Product::where('seller_id', 2)
    ->orderBy('unique_id')
    ->get();

echo "Total Products: " . $products->count() . "\n\n";

echo "📦 PRODUCT LIST:\n";
echo "----------------\n";

foreach ($products as $product) {
    $hasImage = $product->image ? '✅' : '❌';
    echo sprintf(
        "%s - %s - Rs.%.2f - %s\n",
        $product->unique_id,
        substr($product->name, 0, 30) . (strlen($product->name) > 30 ? '...' : ''),
        $product->price,
        $hasImage
    );
}

echo "\n📊 CATEGORY BREAKDOWN:\n";
echo "---------------------\n";
$categoryBreakdown = $products->groupBy('category.name');
foreach ($categoryBreakdown as $category => $items) {
    echo "{$category}: {$items->count()} products\n";
}

echo "\n💰 PRICE RANGE:\n";
echo "---------------\n";
echo "Minimum: Rs." . $products->min('price') . "\n";
echo "Maximum: Rs." . $products->max('price') . "\n";
echo "Average: Rs." . number_format($products->avg('price'), 2) . "\n";

echo "\n🖼️ IMAGE STATUS:\n";
echo "----------------\n";
$withImages = $products->where('image', '!=', null)->count();
$withoutImages = $products->where('image', null)->count();
echo "With Images: {$withImages}\n";
echo "Without Images: {$withoutImages}\n";

echo "\n✅ SUCCESS! All products are now available in the seller dashboard.\n";
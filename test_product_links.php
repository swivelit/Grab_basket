<?php
// Test product links in shelf sections
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "Testing product links in shelf sections...\n\n";

// Get products from each section
$flashSale = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('discount', '>', 20)
    ->inRandomOrder()
    ->take(3)
    ->get();

$deals = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('discount', '>', 0)
    ->inRandomOrder()
    ->take(3)
    ->get();

$trending = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->latest()
    ->take(3)
    ->get();

$freeDelivery = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('delivery_charge', 0)
    ->take(3)
    ->get();

echo "Flash Sale Products:\n";
foreach($flashSale as $product) {
    echo "  ID: {$product->id} | Name: {$product->name} | Has Image: " . ($product->image ? 'Yes' : 'No') . "\n";
    echo "  URL: /product/{$product->id}\n";
}

echo "\nDeals of the Day Products:\n";
foreach($deals as $product) {
    echo "  ID: {$product->id} | Name: {$product->name} | Has Image: " . ($product->image ? 'Yes' : 'No') . "\n";
    echo "  URL: /product/{$product->id}\n";
}

echo "\nTrending Products:\n";
foreach($trending as $product) {
    echo "  ID: {$product->id} | Name: {$product->name} | Has Image: " . ($product->image ? 'Yes' : 'No') . "\n";
    echo "  URL: /product/{$product->id}\n";
}

echo "\nFree Delivery Products:\n";
foreach($freeDelivery as $product) {
    echo "  ID: {$product->id} | Name: {$product->name} | Has Image: " . ($product->image ? 'Yes' : 'No') . "\n";
    echo "  URL: /product/{$product->id}\n";
}

echo "\n\nTesting ProductController::show() method...\n";
// Test if ProductController can load these products
try {
    $testProduct = $flashSale->first();
    if ($testProduct) {
        echo "Testing product ID: {$testProduct->id}\n";
        $product = Product::with(['category', 'subcategory', 'seller'])->findOrFail($testProduct->id);
        echo "✅ Product loaded successfully\n";
        echo "  - Has seller: " . ($product->seller ? 'Yes' : 'No') . "\n";
        echo "  - Has category: " . ($product->category ? 'Yes' : 'No') . "\n";
        echo "  - Has subcategory: " . ($product->subcategory ? 'Yes' : 'No') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== CATEGORY 14 DIAGNOSTIC ===\n\n";

// Check if category 14 exists
$category = Category::find(14);
if (!$category) {
    echo "❌ Category 14 does NOT exist in the database\n";
    echo "\nAvailable categories:\n";
    $cats = Category::orderBy('id')->get(['id', 'name']);
    foreach ($cats as $cat) {
        echo "  - ID: {$cat->id}, Name: {$cat->name}\n";
    }
    exit;
}

echo "✅ Category 14 EXISTS\n";
echo "   Name: {$category->name}\n";
echo "   Slug: {$category->slug}\n";
echo "   Description: " . substr($category->description ?? 'N/A', 0, 50) . "\n";
echo "   Created: {$category->created_at}\n\n";

// Check products in category 14
$productCount = Product::where('category_id', 14)->count();
echo "📦 Total products in category 14: {$productCount}\n";

// Check products with valid images
$validImageCount = Product::where('category_id', 14)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('image', 'NOT LIKE', '%placeholder%')
    ->where('image', 'NOT LIKE', '%via.placeholder%')
    ->count();

echo "🖼️  Products with valid images: {$validImageCount}\n\n";

if ($productCount > 0) {
    echo "Sample products from category 14:\n";
    $samples = Product::where('category_id', 14)
        ->limit(5)
        ->get(['id', 'name', 'price', 'image']);
    
    foreach ($samples as $product) {
        echo "  - ID: {$product->id}, Name: " . substr($product->name, 0, 40) . "\n";
        echo "    Price: ₹{$product->price}, Image: " . ($product->image ?: 'NULL') . "\n";
    }
}

// Check for any database errors
echo "\n=== DATABASE CONNECTION TEST ===\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n";
} catch (\Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== ROUTE TEST ===\n";
echo "Expected URL: https://grabbaskets.com/buyer/category/14\n";
echo "Route should map to: BuyerController@productsByCategory\n";

echo "\nDone!\n";

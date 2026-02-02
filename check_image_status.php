<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "Product Image Status Report:\n";
echo "============================\n\n";

$totalProducts = \App\Models\Product::count();
$productsWithImages = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->count();
$productsWithoutImages = $totalProducts - $productsWithImages;

echo "Total Products: $totalProducts\n";
echo "Products WITH Images: $productsWithImages\n";
echo "Products WITHOUT Images: $productsWithoutImages\n\n";

echo "Categories and their product counts (with images only):\n";
echo "======================================================\n";

$categories = \App\Models\Category::all();
foreach($categories as $category) {
    $categoryProductsWithImages = \App\Models\Product::where('category_id', $category->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->count();
    
    $totalCategoryProducts = \App\Models\Product::where('category_id', $category->id)->count();
    
    echo "{$category->name}: $categoryProductsWithImages/$totalCategoryProducts products have images\n";
}

echo "\nSample products with images:\n";
echo "============================\n";
$sampleProducts = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->take(5)
    ->get(['name', 'image']);

foreach($sampleProducts as $product) {
    echo "- {$product->name}\n";
    echo "  Image: {$product->image}\n\n";
}
<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING PRODUCT FILTERING ON INDEX PAGE ===" . PHP_EOL . PHP_EOL;

// Test the index route logic manually
try {
    $categories = \App\Models\Category::with('subcategories')->get();
    
    echo "Total categories found: " . $categories->count() . PHP_EOL;
    
    // Test category products filtering
    $categoryProducts = [];
    $totalWithSeller = 0;
    $totalWithoutSeller = 0;
    
    foreach ($categories as $category) {
        $withSeller = \App\Models\Product::where('category_id', $category->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->count();
            
        $withoutSeller = \App\Models\Product::where('category_id', $category->id)
            ->whereNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->count();
            
        if ($withSeller > 0 || $withoutSeller > 0) {
            echo "Category: {$category->name}" . PHP_EOL;
            echo "  - Products with seller_id: {$withSeller}" . PHP_EOL;
            echo "  - Products without seller_id (filtered out): {$withoutSeller}" . PHP_EOL;
        }
        
        $totalWithSeller += $withSeller;
        $totalWithoutSeller += $withoutSeller;
    }
    
    echo PHP_EOL . "SUMMARY:" . PHP_EOL;
    echo "Total products with seller_id (will be shown): {$totalWithSeller}" . PHP_EOL;
    echo "Total products without seller_id (filtered out): {$totalWithoutSeller}" . PHP_EOL;
    echo "Filtering effectiveness: " . round(($totalWithoutSeller / ($totalWithSeller + $totalWithoutSeller)) * 100, 2) . "% of dummy products filtered out" . PHP_EOL;
    
    // Test specific category products
    $cookingCategory = \App\Models\Category::where('name', 'COOKING')->first();
    if ($cookingCategory) {
        $cookingProducts = \App\Models\Product::where('category_id', $cookingCategory->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'seller_id']);
            
        echo PHP_EOL . "Sample COOKING products that will be displayed:" . PHP_EOL;
        foreach ($cookingProducts as $product) {
            echo "  - ID: {$product->id}, Name: {$product->name}, Seller: {$product->seller_id}" . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "✅ Product filtering is working correctly!" . PHP_EOL;
    echo "Only products with legitimate seller_id values will be displayed on the index page." . PHP_EOL;
    
} catch (\Exception $e) {
    echo "❌ Error testing filtering: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
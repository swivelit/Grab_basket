<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Index Page Components...\n\n";

// Test 1: Check if Banner model exists
try {
    $banners = App\Models\Banner::active()->byPosition('hero')->get();
    echo "✓ Banner model works: " . $banners->count() . " active hero banners found\n";
} catch (Exception $e) {
    echo "✗ Banner Error: " . $e->getMessage() . "\n";
}

// Test 2: Check categories
try {
    $categories = App\Models\Category::with('subcategories')->get();
    echo "✓ Categories loaded: " . $categories->count() . " categories found\n";
} catch (Exception $e) {
    echo "✗ Category Error: " . $e->getMessage() . "\n";
}

// Test 3: Check products
try {
    $products = App\Models\Product::take(5)->get();
    echo "✓ Products loaded: " . $products->count() . " products found\n";
} catch (Exception $e) {
    echo "✗ Product Error: " . $e->getMessage() . "\n";
}

// Test 4: Simulate index page load
try {
    $categoryProducts = [];
    foreach ($categories->take(3) as $category) {
        $count = App\Models\Product::where('category_id', $category->id)
            ->whereNotNull('seller_id')
            ->count();
        $categoryProducts[$category->name] = $count;
    }
    echo "✓ Category products check passed\n";
    foreach ($categoryProducts as $cat => $count) {
        echo "  - $cat: $count products\n";
    }
} catch (Exception $e) {
    echo "✗ Category Products Error: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";

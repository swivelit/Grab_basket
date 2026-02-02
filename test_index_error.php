<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Index Page Route ===\n\n";

try {
    // Simulate the exact route closure
    echo "Step 1: Loading banners...\n";
    $banners = \App\Models\Banner::active()->byPosition('hero')->get();
    echo "✓ Banners loaded: " . $banners->count() . " banners\n\n";
    
    echo "Step 2: Loading categories with subcategories...\n";
    $categories = \App\Models\Category::with('subcategories')->get();
    echo "✓ Categories loaded: " . $categories->count() . " categories\n\n";
    
    echo "Step 3: Building category products...\n";
    $categoryProducts = [];
    foreach ($categories as $category) {
        $categoryProducts[$category->name] = \App\Models\Product::where('category_id', $category->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(6)
            ->get();
    }
    echo "✓ Category products built\n\n";
    
    echo "Step 4: Loading specific categories...\n";
    $cookingCategory = \App\Models\Category::where('name', 'COOKING')->first();
    $beautyCategory = \App\Models\Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
    $dentalCategory = \App\Models\Category::where('name', 'DENTAL CARE')->first();
    echo "✓ Cooking: " . ($cookingCategory ? 'Found' : 'Not found') . "\n";
    echo "✓ Beauty: " . ($beautyCategory ? 'Found' : 'Not found') . "\n";
    echo "✓ Dental: " . ($dentalCategory ? 'Found' : 'Not found') . "\n\n";
    
    echo "Step 5: Building mixed products collection...\n";
    $mixedProducts = collect();
    
    if ($cookingCategory) {
        $cookingProducts = \App\Models\Product::where('category_id', $cookingCategory->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(6)
            ->get();
        $mixedProducts = $mixedProducts->merge($cookingProducts);
        echo "✓ Added cooking products: " . $cookingProducts->count() . "\n";
    }
    
    if ($beautyCategory) {
        $beautyProducts = \App\Models\Product::where('category_id', $beautyCategory->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(3)
            ->get();
        $mixedProducts = $mixedProducts->merge($beautyProducts);
        echo "✓ Added beauty products: " . $beautyProducts->count() . "\n";
    }
    
    if ($dentalCategory) {
        $dentalProducts = \App\Models\Product::where('category_id', $dentalCategory->id)
            ->whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->inRandomOrder()
            ->take(3)
            ->get();
        $mixedProducts = $mixedProducts->merge($dentalProducts);
        echo "✓ Added dental products: " . $dentalProducts->count() . "\n";
    }
    
    echo "\nStep 6: Creating pagination...\n";
    $shuffledProducts = $mixedProducts->shuffle();
    $products = new \Illuminate\Pagination\LengthAwarePaginator(
        $shuffledProducts->forPage(1, 12),
        $shuffledProducts->count(),
        12,
        1,
        ['path' => '/']
    );
    echo "✓ Pagination created: " . $products->count() . " products on page 1\n\n";
    
    echo "Step 7: Loading trending products...\n";
    $trending = \App\Models\Product::whereNotNull('seller_id')
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->take(12)
        ->get();
    echo "✓ Trending products: " . $trending->count() . "\n\n";
    
    echo "Step 8: Loading lookbook product...\n";
    $lookbookProduct = \App\Models\Product::whereNotNull('seller_id')
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->first();
    echo "✓ Lookbook product: " . ($lookbookProduct ? 'Loaded' : 'None found') . "\n\n";
    
    echo "Step 9: Loading blog products...\n";
    $blogProducts = \App\Models\Product::whereNotNull('seller_id')
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->inRandomOrder()
        ->take(8)
        ->get();
    echo "✓ Blog products: " . $blogProducts->count() . "\n\n";
    
    echo "Step 10: Checking if view exists...\n";
    if (view()->exists('index')) {
        echo "✓ View 'index' exists\n\n";
        
        echo "Step 11: Attempting to render view...\n";
        $view = view('index', compact('categories', 'products', 'trending', 'lookbookProduct', 'blogProducts', 'categoryProducts', 'banners'));
        $rendered = $view->render();
        echo "✓ View rendered successfully! (Length: " . strlen($rendered) . " bytes)\n\n";
        
        echo "=== ALL TESTS PASSED ===\n";
        echo "The index page should be working!\n";
    } else {
        echo "✗ View 'index' does NOT exist!\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR FOUND:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

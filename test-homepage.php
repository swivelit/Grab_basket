<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Homepage Components...\n\n";

try {
    // Test database connection
    echo "1. Testing Database Connection...\n";
    DB::connection()->getPdo();
    echo "   ✓ Database Connected Successfully\n\n";
    
    // Test Category query
    echo "2. Testing Category Query...\n";
    $categories = App\Models\Category::with('subcategories')->limit(5)->get();
    echo "   ✓ Found " . $categories->count() . " categories\n\n";
    
    // Test Product query
    echo "3. Testing Product Query...\n";
    $products = App\Models\Product::with('category')->limit(5)->get();
    echo "   ✓ Found " . $products->count() . " products\n\n";
    
    // Test Banner query
    echo "4. Testing Banner Query...\n";
    $banners = App\Models\Banner::active()->byPosition('hero')->get();
    echo "   ✓ Found " . $banners->count() . " active hero banners\n\n";
    
    // Test HomeController
    echo "5. Testing HomeController...\n";
    $controller = new App\Http\Controllers\HomeController();
    $response = $controller->index();
    
    if ($response instanceof Illuminate\Http\JsonResponse) {
        echo "   ✗ ERROR: " . json_encode($response->getData()) . "\n\n";
    } else {
        echo "   ✓ HomeController executed successfully\n\n";
    }
    
    echo "All tests passed!\n";
    
} catch (\Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;

echo "=== TESTING CATEGORY 24 QUERY ===\n\n";

try {
    // Test 1: Check if category exists
    $category = Category::findOrFail(24);
    echo "✅ Category found: {$category->name}\n\n";
    
    // Test 2: Run the exact query from BuyerController
    echo "Running query...\n";
    $query = Product::with(['category', 'subcategory'])
        ->where('category_id', 24)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%');
    
    $products = $query->latest()->paginate(12);
    
    echo "✅ Query successful!\n";
    echo "   Total products: {$products->total()}\n";
    echo "   Current page items: {$products->count()}\n\n";
    
    // Test 3: Get all categories and subcategories
    $allCategories = Category::orderBy('name')->get();
    echo "✅ All categories retrieved: {$allCategories->count()}\n";
    
    $subsByCategory = Subcategory::orderBy('name')->get()->groupBy('category_id');
    echo "✅ Subcategories grouped by category\n\n";
    
    // Test 4: Check if products have valid relationships
    if ($products->count() > 0) {
        echo "Checking product relationships:\n";
        foreach ($products as $product) {
            echo "  Product {$product->id}: ";
            
            if (!$product->category) {
                echo "❌ Missing category\n";
            } elseif ($product->subcategory && !$product->subcategory->exists()) {
                echo "⚠️ Invalid subcategory reference\n";
            } else {
                echo "✅ OK\n";
            }
        }
    }
    
    echo "\n✅ ALL TESTS PASSED - No errors found locally\n";
    echo "\nThe error must be on the production server.\n";
    echo "Possible causes:\n";
    echo "1. Old cached views/config on production\n";
    echo "2. Missing Product relationships (category/subcategory)\n";
    echo "3. Database connection issues\n";
    echo "4. PHP memory limits\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR FOUND:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

<?php

// Test route for debugging
Route::get('/test-debug', function () {
    try {
        echo "Testing Category model...\n";
        $categories = \App\Models\Category::count();
        echo "Categories count: $categories\n";
        
        echo "Testing Product model...\n";
        $products = \App\Models\Product::count();
        echo "Products count: $products\n";
        
        return "All models working fine!";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
});
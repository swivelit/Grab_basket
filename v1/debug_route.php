<?php

// Simple test route to verify basic functionality
Route::get('/test-bulk-upload-debug', function() {
    try {
        // Test 1: Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return 'ERROR: ZipArchive class not available';
        }
        
        // Test 2: Check if we can access the controller
        $controller = new \App\Http\Controllers\SellerController();
        
        // Test 3: Check authentication
        if (!Auth::check()) {
            return 'Not authenticated - please login first';
        }
        
        // Test 4: Check if seller has products
        $productCount = \App\Models\Product::where('seller_id', Auth::id())->count();
        
        // Test 5: Check categories
        $categoryCount = \App\Models\Category::count();
        
        return response()->json([
            'status' => 'OK',
            'ziparchive_available' => class_exists('ZipArchive'),
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'seller_products' => $productCount,
            'categories_available' => $categoryCount,
            'storage_configured' => config('filesystems.default'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

?>
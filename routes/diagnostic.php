<?php

use Illuminate\Support\Facades\Route;

// Add this temporary diagnostic route to test
Route::get('/test-index-debug', function () {
    try {
        $diagnostics = [];
        
        // Test 1: Banner model
        try {
            $banners = \App\Models\Banner::active()->byPosition('hero')->get();
            $diagnostics['banners'] = 'OK - ' . $banners->count() . ' banners';
        } catch (\Exception $e) {
            $diagnostics['banners'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Test 2: Categories
        try {
            $categories = \App\Models\Category::with('subcategories')->get();
            $diagnostics['categories'] = 'OK - ' . $categories->count() . ' categories';
        } catch (\Exception $e) {
            $diagnostics['categories'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Test 3: Products
        try {
            $products = \App\Models\Product::whereNotNull('seller_id')->take(5)->get();
            $diagnostics['products'] = 'OK - ' . $products->count() . ' products';
        } catch (\Exception $e) {
            $diagnostics['products'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Test 4: View exists
        $diagnostics['view_exists'] = view()->exists('index') ? 'YES' : 'NO';
        
        // Test 5: Database connection
        try {
            \DB::connection()->getPdo();
            $diagnostics['database'] = 'OK - Connected';
        } catch (\Exception $e) {
            $diagnostics['database'] = 'ERROR: ' . $e->getMessage();
        }
        
        return response()->json([
            'status' => 'Index Page Diagnostics',
            'timestamp' => now()->toDateTimeString(),
            'tests' => $diagnostics,
            'message' => 'All tests completed. Check results above.'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Diagnostic failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

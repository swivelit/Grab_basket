<?php
/**
 * Guest Search 500 Error Diagnostic
 * Tests the search functionality and identifies the root cause
 */

require_once __DIR__ . '/vendor/autoload.php';

// Set up Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔍 GUEST SEARCH 500 ERROR DIAGNOSTIC\n";
echo "===================================\n\n";

try {
    // Test 1: Check if OptimizedBuyerController exists and is accessible
    echo "1. CONTROLLER VERIFICATION:\n";
    if (class_exists('App\Http\Controllers\OptimizedBuyerController')) {
        echo "   ✅ OptimizedBuyerController: EXISTS\n";
        
        // Check if the guestSearch method exists
        $reflectionClass = new ReflectionClass('App\Http\Controllers\OptimizedBuyerController');
        if ($reflectionClass->hasMethod('guestSearch')) {
            echo "   ✅ guestSearch method: EXISTS\n";
        } else {
            echo "   ❌ guestSearch method: MISSING\n";
        }
    } else {
        echo "   ❌ OptimizedBuyerController: MISSING\n";
    }
    echo "\n";

    // Test 2: Check routes
    echo "2. ROUTE VERIFICATION:\n";
    try {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $productsRoute = $routes->getByName('products.index');
        if ($productsRoute) {
            echo "   ✅ products.index route: EXISTS\n";
            echo "   📍 URI: " . $productsRoute->uri() . "\n";
            echo "   📍 Action: " . $productsRoute->getActionName() . "\n";
        } else {
            echo "   ❌ products.index route: MISSING\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Route check failed: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 3: Test database connection
    echo "3. DATABASE CONNECTION TEST:\n";
    try {
        $connection = \Illuminate\Support\Facades\DB::connection();
        $connection->getPdo();
        echo "   ✅ Database connection: WORKING\n";
    } catch (\Exception $e) {
        echo "   ❌ Database connection: FAILED - " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 4: Test basic query
    echo "4. BASIC QUERY TEST:\n";
    try {
        $productCount = \App\Models\Product::count();
        echo "   ✅ Product model query: WORKING ($productCount products)\n";
    } catch (\Exception $e) {
        echo "   ❌ Product model query: FAILED - " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 5: Simulate the search request
    echo "5. SEARCH FUNCTIONALITY TEST:\n";
    try {
        // Create a mock request
        $request = new \Illuminate\Http\Request();
        $request->merge(['q' => 'maya oils']);
        
        // Test if we can create the controller
        $controller = new \App\Http\Controllers\OptimizedBuyerController();
        echo "   ✅ Controller instantiation: SUCCESS\n";
        
        // Try to call the method (this might reveal the specific error)
        ob_start();
        $response = $controller->guestSearch($request);
        $output = ob_get_clean();
        
        echo "   ✅ guestSearch method execution: SUCCESS\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Search execution: FAILED\n";
        echo "   🔍 Error: " . $e->getMessage() . "\n";
        echo "   📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Show more details if it's a specific error
        if (method_exists($e, 'getTraceAsString')) {
            echo "   📋 Stack trace (first 5 lines):\n";
            $trace = explode("\n", $e->getTraceAsString());
            for ($i = 0; $i < min(5, count($trace)); $i++) {
                echo "      " . trim($trace[$i]) . "\n";
            }
        }
    }
    echo "\n";

    // Test 6: Check if all required models exist
    echo "6. MODEL VERIFICATION:\n";
    $models = ['Product', 'Category', 'Seller', 'User'];
    foreach ($models as $model) {
        $fullModelName = "App\\Models\\$model";
        if (class_exists($fullModelName)) {
            echo "   ✅ $model model: EXISTS\n";
        } else {
            echo "   ❌ $model model: MISSING\n";
        }
    }
    echo "\n";

    // Test 7: Check database tables
    echo "7. DATABASE TABLES CHECK:\n";
    $tables = ['products', 'categories', 'sellers', 'users'];
    foreach ($tables as $table) {
        try {
            $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
            echo "   " . ($exists ? "✅" : "❌") . " Table '$table': " . ($exists ? "EXISTS" : "MISSING") . "\n";
        } catch (\Exception $e) {
            echo "   ❌ Table '$table': ERROR - " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // Test 8: Check if the view exists
    echo "8. VIEW VERIFICATION:\n";
    if (view()->exists('buyer.products')) {
        echo "   ✅ buyer.products view: EXISTS\n";
    } else {
        echo "   ❌ buyer.products view: MISSING\n";
    }
    echo "\n";

    echo "🔧 DIAGNOSTIC COMPLETE!\n";
    echo "Check the above results to identify the root cause of the 500 error.\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR during diagnostic: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
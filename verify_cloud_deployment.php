<?php

// Test script to verify cloud deployment status
echo "=== CLOUD DEPLOYMENT VERIFICATION ===" . PHP_EOL;

// Check if we can connect to database and run queries
try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel bootstrap successful" . PHP_EOL;
    
    // Test if route exists in route cache
    $router = app('router');
    $routes = $router->getRoutes();
    $serveRouteExists = false;
    
    foreach ($routes->getRoutes() as $route) {
        if (str_contains($route->uri(), 'serve-image')) {
            $serveRouteExists = true;
            echo "✅ Serve-image route found: " . $route->uri() . PHP_EOL;
            break;
        }
    }
    
    if (!$serveRouteExists) {
        echo "❌ Serve-image route NOT found in routes" . PHP_EOL;
    }
    
    // Test product filtering query
    $totalProducts = \App\Models\Product::count();
    $productsWithSeller = \App\Models\Product::whereNotNull('seller_id')->count();
    $productsWithoutSeller = \App\Models\Product::whereNull('seller_id')->count();
    
    echo PHP_EOL . "Product Statistics:" . PHP_EOL;
    echo "Total products: $totalProducts" . PHP_EOL;
    echo "Products with seller_id: $productsWithSeller" . PHP_EOL;
    echo "Products without seller_id: $productsWithoutSeller" . PHP_EOL;
    
    // Test image files in storage
    $storageProductsPath = storage_path('app/public/products');
    if (is_dir($storageProductsPath)) {
        $imageFiles = glob($storageProductsPath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        echo "Image files in storage: " . count($imageFiles) . PHP_EOL;
    } else {
        echo "❌ Storage products directory not found" . PHP_EOL;
    }
    
    // Test a sample product image URL generation
    $sampleProduct = \App\Models\Product::whereNotNull('seller_id')
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->first();
    
    if ($sampleProduct) {
        echo PHP_EOL . "Sample Product Test:" . PHP_EOL;
        echo "Product ID: {$sampleProduct->id}" . PHP_EOL;
        echo "Product Name: {$sampleProduct->name}" . PHP_EOL;
        echo "Seller ID: {$sampleProduct->seller_id}" . PHP_EOL;
        echo "Image field: {$sampleProduct->image}" . PHP_EOL;
        echo "Generated URL: " . $sampleProduct->image_url . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ Deployment verification complete" . PHP_EOL;
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . PHP_EOL;
}
?>
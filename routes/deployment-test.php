<?php

// Simple route test that we can access via web
Route::get('/test-deployment', function () {
    $response = [
        'status' => 'deployment-test',
        'timestamp' => now()->toDateTimeString(),
        'routes' => [],
        'serve_route_exists' => false,
        'sample_image_url' => '',
        'product_count' => 0
    ];
    
    try {
        // Check if routes are loaded
        $router = app('router');
        $routes = $router->getRoutes();
        
        foreach ($routes->getRoutes() as $route) {
            if (str_contains($route->uri(), 'serve-image')) {
                $response['serve_route_exists'] = true;
                $response['routes'][] = $route->uri();
            }
        }
        
        // Get product count with seller filter
        $response['product_count'] = \App\Models\Product::whereNotNull('seller_id')->count();
        
        // Get sample image URL
        $sampleProduct = \App\Models\Product::whereNotNull('seller_id')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->first();
            
        if ($sampleProduct) {
            $response['sample_image_url'] = $sampleProduct->image_url;
        }
        
        $response['status'] = 'success';
        
    } catch (\Exception $e) {
        $response['status'] = 'error';
        $response['error'] = $e->getMessage();
    }
    
    return response()->json($response, 200, [], JSON_PRETTY_PRINT);
});
?>
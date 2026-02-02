<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Category ID: 20...\n\n";

try {
    // Check if category 20 exists
    $category = \App\Models\Category::find(20);
    
    if (!$category) {
        echo "✗ Category 20 does not exist in database\n";
        exit(1);
    }
    
    echo "✓ Category found: {$category->name} (ID: {$category->id})\n\n";
    
    // Create controller and test
    $controller = new \App\Http\Controllers\BuyerController();
    $request = \Illuminate\Http\Request::create('/buyer/category/20', 'GET');
    
    $response = $controller->productsByCategory($request, 20);
    
    echo "✓ Controller executed\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✓ Returned a View\n";
        
        try {
            $rendered = $response->render();
            echo "✓ View rendered successfully\n";
            echo "Content size: " . strlen($rendered) . " bytes\n";
        } catch (\Exception $renderError) {
            echo "✗ View rendering failed!\n";
            echo "Error: " . $renderError->getMessage() . "\n";
            echo "File: " . $renderError->getFile() . "\n";
            echo "Line: " . $renderError->getLine() . "\n";
            
            // Show more details
            if (strpos($renderError->getMessage(), 'Attempt to read property') !== false) {
                echo "\nThis is a null property access error.\n";
                echo "Check the view file for variables that might be null.\n";
            }
        }
    }
    
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    echo "✗ Category 20 not found (ModelNotFoundException)\n";
} catch (\Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

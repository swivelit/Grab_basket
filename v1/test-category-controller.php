<?php

// Direct test of BuyerController::productsByCategory

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing BuyerController::productsByCategory method...\n\n";

try {
    // Create controller instance
    $controller = new \App\Http\Controllers\BuyerController();
    
    // Create a mock request for category ID 1
    $request = \Illuminate\Http\Request::create('/buyer/category/1', 'GET');
    
    // Call the method
    $response = $controller->productsByCategory($request, 1);
    
    echo "✓ Controller method executed\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\Response) {
        echo "Status: " . $response->getStatusCode() . "\n";
        $content = $response->getContent();
        echo "Content size: " . strlen($content) . " bytes\n";
        
        if ($response->getStatusCode() != 200) {
            echo "\nFirst 500 chars of response:\n";
            echo substr($content, 0, 500) . "\n";
        }
    } elseif ($response instanceof \Illuminate\View\View) {
        echo "✓ Returned a View object\n";
        try {
            $rendered = $response->render();
            echo "✓ View rendered successfully\n";
            echo "Content size: " . strlen($rendered) . " bytes\n";
        } catch (\Exception $renderError) {
            echo "✗ View rendering failed!\n";
            echo "Error: " . $renderError->getMessage() . "\n";
            echo "File: " . $renderError->getFile() . "\n";
            echo "Line: " . $renderError->getLine() . "\n";
        }
    }
    
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    echo "✗ Category not found (ID: 1)\n";
    echo "Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "✗ Exception occurred\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}

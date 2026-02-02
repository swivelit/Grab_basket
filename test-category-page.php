<?php

// Test the category page route locally

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "Testing Category Page (category_id=1)...\n\n";
    
    // Create a test request
    $request = Illuminate\Http\Request::create('/buyer/category/1', 'GET');
    
    // Process through kernel
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✓ Category page loaded successfully\n";
        echo "Content size: " . strlen($response->getContent()) . " bytes\n";
    } else {
        echo "✗ Error loading category page\n";
        echo "Response:\n" . substr($response->getContent(), 0, 500) . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

<?php

// Direct test of the index route
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== TESTING INDEX PAGE DIRECTLY ===\n\n";

try {
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✓ SUCCESS! Index page loads correctly\n";
        echo "Response length: " . strlen($response->getContent()) . " bytes\n";
    } else {
        echo "✗ ERROR! Status code is not 200\n";
        echo "Response content:\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION CAUGHT:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Full Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);

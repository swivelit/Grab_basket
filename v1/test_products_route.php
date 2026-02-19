<?php
/**
 * Quick route test for products search
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔍 Testing /products route...\n";

try {
    // Create a test request
    $request = Illuminate\Http\Request::create('/products?q=maya+oils', 'GET');
    $response = $kernel->handle($request);
    
    echo "✅ Route responded with status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ SUCCESS: Search route is working!\n";
    } else {
        echo "⚠️  WARNING: Got status " . $response->getStatusCode() . "\n";
        echo "Response preview:\n";
        echo substr($response->getContent(), 0, 500) . "...\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTest completed.\n";
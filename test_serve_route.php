<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing serve route directly...\n\n";

// Test URL
$testUrl = 'https://grabbaskets.laravel.cloud/serve-image/products/0Rc193BfOQ4pDAtqAYBc1SLfKm2E9Hoklwo643Fz.jpg';

echo "Testing URL: {$testUrl}\n";

// Try to access the URL
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($testUrl, false, $context);
$httpCode = isset($http_response_header) ? $http_response_header[0] : 'No response';

echo "HTTP Response: {$httpCode}\n";
echo "Content length: " . (($response !== false) ? strlen($response) : '0') . " bytes\n";

if ($response !== false && strpos($httpCode, '200') !== false) {
    echo "✅ SUCCESS! Image is served correctly!\n";
} else {
    echo "❌ FAILED! Image not accessible.\n";
    
    // Show response content for debugging
    if ($response !== false && strlen($response) < 1000) {
        echo "Response content: " . substr($response, 0, 500) . "\n";
    }
}

echo "\nDone!\n";
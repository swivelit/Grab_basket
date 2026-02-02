<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Admin Delivery Partners Dashboard ===\n\n";

try {
    $url = '/admin/delivery-partners/dashboard';
    echo "Testing: $url\n\n";
    
    $request = \Illuminate\Http\Request::create($url, 'GET');
    $request->headers->set('Accept', 'text/html');
    
    $response = $app->handle($request);
    $status = $response->getStatusCode();
    
    echo "Status: $status\n";
    
    if ($status === 500) {
        $content = $response->getContent();
        
        // Extract error message
        if (preg_match('/class="exception-message">([^<]+)</', $content, $matches)) {
            echo "Error: " . trim($matches[1]) . "\n";
        }
        
        // Extract file and line
        if (preg_match('/in <strong>([^<]+)<\/strong> line <strong>(\d+)<\/strong>/', $content, $matches)) {
            echo "File: " . trim($matches[1]) . ":" . trim($matches[2]) . "\n";
        }
        
        // Extract trace
        if (preg_match_all('/<div class="frame-file">([^<]+)<\/div>.*?<div class="frame-line">(\d+)<\/div>/s', $content, $matches, PREG_SET_ORDER)) {
            echo "\nStack Trace:\n";
            foreach (array_slice($matches, 0, 5) as $i => $match) {
                echo ($i + 1) . ". " . trim($match[1]) . ":" . trim($match[2]) . "\n";
            }
        }
    } else {
        echo "✅ Success!\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

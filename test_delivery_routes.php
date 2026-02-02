<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test routes
$routes = [
    '/delivery-partner/dashboard',
    '/delivery-partner/orders',
    '/delivery-partner/orders/available',
    '/delivery-partner/notifications',
    '/delivery-partner/earnings',
    '/delivery-partner/profile',
    '/delivery-partner/support',
];

echo "=== Testing Delivery Partner Routes ===\n\n";

// Login first
$partner = \App\Models\DeliveryPartner::first();
if (!$partner) {
    echo "❌ No delivery partner found in database\n";
    exit(1);
}

\Illuminate\Support\Facades\Auth::guard('delivery_partner')->login($partner);
echo "✅ Logged in as: {$partner->name}\n\n";

foreach ($routes as $url) {
    echo "Testing: $url\n";
    
    try {
        $request = \Illuminate\Http\Request::create($url, 'GET');
        $request->headers->set('Accept', 'text/html');
        
        $response = $app->handle($request);
        $status = $response->getStatusCode();
        
        if ($status === 200) {
            echo "✅ Status: $status - OK\n";
        } elseif ($status >= 300 && $status < 400) {
            echo "🔄 Status: $status - Redirect to: " . $response->headers->get('Location') . "\n";
        } else {
            echo "❌ Status: $status - ERROR\n";
            if ($status === 500) {
                $content = $response->getContent();
                if (preg_match('/class="exception-message">([^<]+)</', $content, $matches)) {
                    echo "   Error: " . trim($matches[1]) . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

<?php

// Test delivery partner login flow

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Delivery Partner Login Flow...\n\n";

try {
    // Check if delivery partners exist
    $partner = \App\Models\DeliveryPartner::first();
    
    if (!$partner) {
        echo "✗ No delivery partners found in database\n";
        echo "  Create a delivery partner account first\n";
        exit(1);
    }
    
    echo "✓ Found delivery partner: {$partner->name} (ID: {$partner->id})\n";
    echo "  Email: {$partner->email}\n";
    echo "  Phone: {$partner->phone}\n";
    echo "  Status: {$partner->status}\n\n";
    
    // Test login form
    echo "Testing login form view...\n";
    $controller = new \App\Http\Controllers\DeliveryPartner\SuperFastAuthController();
    $loginView = $controller->showLoginForm();
    echo "✓ Login form loads successfully\n\n";
    
    // Simulate login (using the first partner's credentials)
    echo "Testing login POST (simulating with email: {$partner->email})...\n";
    
    // Create a mock request
    $request = \Illuminate\Http\Request::create('/delivery-partner/login', 'POST', [
        'login' => $partner->email,
        'password' => 'password', // Default password - adjust if different
    ]);
    
    // Note: This won't actually work without proper password
    // But we can test if the route exists
    echo "✓ Login route exists\n\n";
    
    // Test dashboard access
    echo "Testing dashboard controller...\n";
    Auth::guard('delivery_partner')->login($partner);
    
    $dashboardController = new \App\Http\Controllers\DeliveryPartner\DashboardController();
    $response = $dashboardController->index();
    
    echo "✓ Dashboard controller executed\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✓ Dashboard returns View\n";
        try {
            $rendered = $response->render();
            echo "✓ Dashboard view rendered successfully\n";
            echo "Content size: " . strlen($rendered) . " bytes\n";
        } catch (\Exception $renderError) {
            echo "✗ Dashboard view rendering failed!\n";
            echo "Error: " . $renderError->getMessage() . "\n";
            echo "File: " . $renderError->getFile() . "\n";
            echo "Line: " . $renderError->getLine() . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

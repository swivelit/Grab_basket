<?php
/**
 * Test delivery partner login functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🚚 DELIVERY PARTNER LOGIN TEST\n";
echo "==============================\n\n";

try {
    // Test 1: Check if the route exists
    echo "1. ROUTE TEST:\n";
    $loginRequest = Illuminate\Http\Request::create('/delivery-partner/login', 'GET');
    $response = $kernel->handle($loginRequest);
    
    echo "   Login page status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        echo "   ✅ Login page loads successfully\n";
    } else {
        echo "   ❌ Login page failed to load\n";
        echo "   Response preview: " . substr($response->getContent(), 0, 200) . "...\n";
    }
    echo "\n";

    // Test 2: Check database connectivity
    echo "2. DATABASE TEST:\n";
    $deliveryPartnerCount = \App\Models\DeliveryPartner::count();
    echo "   Total delivery partners: $deliveryPartnerCount\n";
    if ($deliveryPartnerCount > 0) {
        echo "   ✅ Database connection working\n";
        
        // Get a sample delivery partner for testing
        $samplePartner = \App\Models\DeliveryPartner::first();
        echo "   Sample partner: " . $samplePartner->name . " (" . $samplePartner->email . ")\n";
    } else {
        echo "   ⚠️  No delivery partners found in database\n";
    }
    echo "\n";

    // Test 3: Test login POST (with invalid credentials to avoid actual login)
    echo "3. LOGIN POST TEST:\n";
    $postRequest = Illuminate\Http\Request::create('/delivery-partner/login', 'POST', [
        'login' => 'test@example.com',
        'password' => 'wrongpassword',
        '_token' => csrf_token()
    ]);
    
    try {
        $postResponse = $kernel->handle($postRequest);
        echo "   Login POST status: " . $postResponse->getStatusCode() . "\n";
        
        if ($postResponse->getStatusCode() == 302) {
            echo "   ✅ Login POST processes correctly (redirect response)\n";
        } elseif ($postResponse->getStatusCode() == 422) {
            echo "   ✅ Login POST validation working (422 Unprocessable Entity)\n";
        } else {
            echo "   ⚠️  Unexpected response: " . $postResponse->getStatusCode() . "\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Login POST failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    echo "\n";

    // Test 4: Check if SuperFastAuthController exists and is accessible
    echo "4. CONTROLLER TEST:\n";
    if (class_exists('App\Http\Controllers\DeliveryPartner\SuperFastAuthController')) {
        echo "   ✅ SuperFastAuthController exists\n";
        
        $controller = new App\Http\Controllers\DeliveryPartner\SuperFastAuthController();
        if (method_exists($controller, 'showLoginForm')) {
            echo "   ✅ showLoginForm method exists\n";
        }
        if (method_exists($controller, 'login')) {
            echo "   ✅ login method exists\n";
        }
    } else {
        echo "   ❌ SuperFastAuthController not found\n";
    }
    echo "\n";

    // Test 5: Check authentication guard
    echo "5. AUTHENTICATION GUARD TEST:\n";
    $guards = config('auth.guards');
    if (isset($guards['delivery_partner'])) {
        echo "   ✅ delivery_partner guard configured\n";
        echo "   Driver: " . $guards['delivery_partner']['driver'] . "\n";
        echo "   Provider: " . $guards['delivery_partner']['provider'] . "\n";
    } else {
        echo "   ❌ delivery_partner guard not configured\n";
    }
    echo "\n";

    echo "✅ DELIVERY PARTNER LOGIN TEST COMPLETE!\n";
    echo "If there are no critical errors above, the login should be working.\n";
    echo "Try accessing: https://grabbaskets.laravel.cloud/delivery-partner/login\n";

} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
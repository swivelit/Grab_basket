<?php
/**
 * Razorpay Configuration Test Script
 * Tests Razorpay API connection and configuration
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

echo "=== RAZORPAY CONFIGURATION TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Configuration Check
echo "1. CONFIGURATION CHECK\n";
echo str_repeat('-', 40) . "\n";

$keyId = config('services.razorpay.key');
$keySecret = config('services.razorpay.secret');

echo "Key ID: " . ($keyId ? substr($keyId, 0, 10) . '...' : 'NOT SET') . "\n";
echo "Key Secret: " . ($keySecret ? substr($keySecret, 0, 10) . '...' : 'NOT SET') . "\n";

if (empty($keyId) || empty($keySecret)) {
    echo "❌ CRITICAL: Razorpay credentials not configured!\n";
    echo "Please check your .env file and config/services.php\n\n";
    exit(1);
} else {
    echo "✅ Razorpay credentials configured\n\n";
}

// Test 2: API Connection Test
echo "2. API CONNECTION TEST\n";
echo str_repeat('-', 40) . "\n";

try {
    $api = new Api($keyId, $keySecret);
    echo "✅ Razorpay API object created successfully\n";
    
    // Test creating a dummy order
    $testOrderData = [
        'receipt' => 'test_order_' . time(),
        'amount' => 100, // Rs 1.00 in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ];
    
    echo "Testing order creation with amount: ₹1.00\n";
    
    $startTime = microtime(true);
    $testOrder = $api->order->create($testOrderData);
    $apiTime = (microtime(true) - $startTime) * 1000;
    
    if ($testOrder && isset($testOrder['id'])) {
        echo "✅ Test order created successfully\n";
        echo "Order ID: " . $testOrder['id'] . "\n";
        echo "API Response Time: " . round($apiTime, 2) . "ms\n";
        echo "Order Status: " . $testOrder['status'] . "\n";
        echo "Amount: ₹" . ($testOrder['amount'] / 100) . "\n";
    } else {
        echo "❌ Test order creation failed\n";
        echo "Response: " . json_encode($testOrder) . "\n";
    }
    
} catch (\Razorpay\Api\Errors\BadRequestError $e) {
    echo "❌ Bad Request Error: " . $e->getMessage() . "\n";
    echo "This usually means invalid credentials or request format\n";
} catch (\Razorpay\Api\Errors\ServerError $e) {
    echo "❌ Server Error: " . $e->getMessage() . "\n";
    echo "Razorpay server is having issues\n";
} catch (\Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
    echo "Error Type: " . get_class($e) . "\n";
}

echo "\n";

// Test 3: Environment Check
echo "3. ENVIRONMENT CHECK\n";
echo str_repeat('-', 40) . "\n";

$environment = app()->environment();
echo "Laravel Environment: " . $environment . "\n";

if ($environment === 'production') {
    echo "⚠️  Production environment detected\n";
    echo "Ensure you're using LIVE Razorpay keys, not test keys\n";
} else {
    echo "✅ Development environment - test keys are fine\n";
}

// Check if keys look like test keys
if (strpos($keyId, 'rzp_test_') === 0) {
    echo "ℹ️  Using TEST Razorpay keys\n";
} elseif (strpos($keyId, 'rzp_live_') === 0) {
    echo "ℹ️  Using LIVE Razorpay keys\n";
} else {
    echo "⚠️  Unknown key format\n";
}

echo "\n";

// Test 4: Laravel Integration Check
echo "4. LARAVEL INTEGRATION CHECK\n";
echo str_repeat('-', 40) . "\n";

// Check if PaymentController exists
if (class_exists('App\Http\Controllers\PaymentController')) {
    echo "✅ PaymentController exists\n";
} else {
    echo "❌ PaymentController not found\n";
}

// Check if routes are defined
try {
    $createOrderRoute = route('payment.createOrder');
    $verifyRoute = route('payment.verify');
    echo "✅ Payment routes configured\n";
    echo "Create Order Route: " . $createOrderRoute . "\n";
    echo "Verify Route: " . $verifyRoute . "\n";
} catch (\Exception $e) {
    echo "❌ Payment routes not configured: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Recommendations
echo "5. RECOMMENDATIONS\n";
echo str_repeat('-', 40) . "\n";

$recommendations = [];

if ($apiTime > 5000) {
    $recommendations[] = "API response is slow ({$apiTime}ms) - check network connection";
}

if ($environment === 'production' && strpos($keyId, 'rzp_test_') === 0) {
    $recommendations[] = "Using test keys in production - switch to live keys";
}

if (empty($recommendations)) {
    echo "✅ No issues found! Razorpay should work correctly.\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($recommendations as $i => $recommendation) {
        echo "   " . ($i + 1) . ". {$recommendation}\n";
    }
}

echo "\n=== RAZORPAY TEST COMPLETE ===\n";
echo "If you're still getting 'Payment initialization failed', check:\n";
echo "1. Browser console for JavaScript errors\n";
echo "2. Laravel logs in storage/logs/laravel.log\n";
echo "3. Network tab in browser developer tools\n";
?>
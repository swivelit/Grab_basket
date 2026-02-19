<?php
/**
 * Razorpay Debug Script
 * Tests Razorpay configuration and API connectivity
 */

require __DIR__.'/vendor/autoload.php';

// Load Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .info { background: #e7f3ff; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Razorpay Payment Debug</h1>
";

// 1. Check Environment Variables
echo "<div class='section'>
    <h2>1️⃣ Environment Configuration</h2>";

$razorpayKey = config('services.razorpay.key');
$razorpaySecret = config('services.razorpay.secret');

echo "<p><strong>RAZORPAY_KEY_ID:</strong> ";
if (!empty($razorpayKey)) {
    echo "<span class='success'>✅ Configured</span> (Key: " . substr($razorpayKey, 0, 10) . "...)</p>";
} else {
    echo "<span class='error'>❌ NOT CONFIGURED</span></p>";
}

echo "<p><strong>RAZORPAY_KEY_SECRET:</strong> ";
if (!empty($razorpaySecret)) {
    echo "<span class='success'>✅ Configured</span> (Length: " . strlen($razorpaySecret) . " chars)</p>";
} else {
    echo "<span class='error'>❌ NOT CONFIGURED</span></p>";
}

echo "</div>";

// 2. Test Razorpay API Connection
echo "<div class='section'>
    <h2>2️⃣ Razorpay API Connection Test</h2>";

if (empty($razorpayKey) || empty($razorpaySecret)) {
    echo "<p class='error'>❌ Cannot test API: Credentials not configured</p>";
    echo "<div class='info'><strong>Fix:</strong> Add these to your .env file:<br>
    <code>RAZORPAY_KEY_ID=your_key_id<br>
    RAZORPAY_KEY_SECRET=your_key_secret</code></div>";
} else {
    try {
        $api = new Api($razorpayKey, $razorpaySecret);
        
        // Try to create a test order
        $testOrderData = [
            'receipt'         => 'test_' . time(),
            'amount'          => 100, // ₹1.00
            'currency'        => 'INR',
            'payment_capture' => 1
        ];
        
        echo "<p>Attempting to create test order with Razorpay...</p>";
        echo "<pre>" . json_encode($testOrderData, JSON_PRETTY_PRINT) . "</pre>";
        
        $razorpayOrder = $api->order->create($testOrderData);
        
        if ($razorpayOrder && isset($razorpayOrder['id'])) {
            echo "<p class='success'>✅ <strong>SUCCESS!</strong> Razorpay API is working correctly</p>";
            echo "<p><strong>Test Order ID:</strong> {$razorpayOrder['id']}</p>";
            echo "<p><strong>Status:</strong> {$razorpayOrder['status']}</p>";
            echo "<p><strong>Amount:</strong> ₹" . ($razorpayOrder['amount'] / 100) . "</p>";
            echo "<div class='info'>✅ Razorpay integration is working. The payment initialization issue may be in frontend JavaScript or session handling.</div>";
        } else {
            echo "<p class='error'>❌ API call succeeded but response invalid</p>";
            echo "<pre>" . json_encode($razorpayOrder, JSON_PRETTY_PRINT) . "</pre>";
        }
        
    } catch (\Razorpay\Api\Errors\Error $e) {
        echo "<p class='error'>❌ <strong>Razorpay API Error:</strong></p>";
        echo "<p class='error'>Message: " . $e->getMessage() . "</p>";
        echo "<p class='error'>Code: " . $e->getCode() . "</p>";
        
        if (strpos($e->getMessage(), 'authentication') !== false || strpos($e->getMessage(), 'key') !== false) {
            echo "<div class='info'><strong>⚠️ Authentication Error:</strong> Your Razorpay credentials may be incorrect.<br>
            - Check if you're using <strong>LIVE</strong> keys (rzp_live_...) in production<br>
            - Or <strong>TEST</strong> keys (rzp_test_...) for testing<br>
            - Verify credentials in Razorpay Dashboard → Account Settings → API Keys</div>";
        }
        
    } catch (\Exception $e) {
        echo "<p class='error'>❌ <strong>General Error:</strong></p>";
        echo "<p class='error'>Message: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

echo "</div>";

// 3. Check Payment Controller Configuration
echo "<div class='section'>
    <h2>3️⃣ Payment Controller Status</h2>";

$controllerPath = app_path('Http/Controllers/PaymentController.php');
if (file_exists($controllerPath)) {
    echo "<p class='success'>✅ PaymentController.php exists</p>";
    
    // Check if createOrder method exists
    $controllerContent = file_get_contents($controllerPath);
    if (strpos($controllerContent, 'function createOrder') !== false) {
        echo "<p class='success'>✅ createOrder method exists</p>";
    } else {
        echo "<p class='error'>❌ createOrder method not found</p>";
    }
    
    if (strpos($controllerContent, 'use Razorpay\\Api\\Api') !== false) {
        echo "<p class='success'>✅ Razorpay API imported</p>";
    } else {
        echo "<p class='error'>❌ Razorpay API not imported</p>";
    }
} else {
    echo "<p class='error'>❌ PaymentController.php not found</p>";
}

echo "</div>";

// 4. Check Routes
echo "<div class='section'>
    <h2>4️⃣ Payment Routes</h2>";

try {
    $routes = app('router')->getRoutes();
    
    $paymentRoutes = [
        'payment.createOrder' => false,
        'payment.verify' => false
    ];
    
    foreach ($routes as $route) {
        $name = $route->getName();
        if (isset($paymentRoutes[$name])) {
            $paymentRoutes[$name] = true;
            echo "<p class='success'>✅ Route '{$name}' registered</p>";
        }
    }
    
    foreach ($paymentRoutes as $name => $exists) {
        if (!$exists) {
            echo "<p class='error'>❌ Route '{$name}' not found</p>";
        }
    }
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Error checking routes: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 5. Check Razorpay Package
echo "<div class='section'>
    <h2>5️⃣ Razorpay Package Status</h2>";

if (class_exists('Razorpay\Api\Api')) {
    echo "<p class='success'>✅ Razorpay PHP SDK installed</p>";
    
    $reflection = new ReflectionClass('Razorpay\Api\Api');
    echo "<p><strong>Package Location:</strong> " . dirname($reflection->getFileName()) . "</p>";
} else {
    echo "<p class='error'>❌ Razorpay PHP SDK not found</p>";
    echo "<div class='info'><strong>Fix:</strong> Install Razorpay package:<br>
    <code>composer require razorpay/razorpay</code></div>";
}

echo "</div>";

// 6. Session & Auth Check
echo "<div class='section'>
    <h2>6️⃣ Session & Authentication</h2>";

echo "<p><strong>Session Driver:</strong> " . config('session.driver') . "</p>";
echo "<p><strong>Session Path:</strong> " . storage_path('framework/sessions') . "</p>";

if (file_exists(storage_path('framework/sessions'))) {
    echo "<p class='success'>✅ Session directory exists</p>";
    $sessionFiles = count(glob(storage_path('framework/sessions/*')));
    echo "<p><strong>Active Sessions:</strong> {$sessionFiles}</p>";
} else {
    echo "<p class='error'>❌ Session directory not found</p>";
}

echo "</div>";

// 7. Recommendations
echo "<div class='section'>
    <h2>7️⃣ Troubleshooting Recommendations</h2>
    <ol>
        <li><strong>Clear caches:</strong> <code>php artisan config:clear && php artisan cache:clear</code></li>
        <li><strong>Check browser console:</strong> Press F12 and check for JavaScript errors</li>
        <li><strong>Verify Razorpay Dashboard:</strong> Check if API keys are active and not expired</li>
        <li><strong>Test with TEST keys first:</strong> Use rzp_test_... keys before going live</li>
        <li><strong>Check server logs:</strong> Look in <code>storage/logs/laravel.log</code></li>
        <li><strong>Verify HTTPS:</strong> Razorpay requires HTTPS in production</li>
        <li><strong>Check cart items:</strong> Make sure cart is not empty before payment</li>
    </ol>
</div>";

// 8. Next Steps
echo "<div class='section'>
    <h2>8️⃣ Next Steps</h2>";

if (!empty($razorpayKey) && !empty($razorpaySecret)) {
    echo "<p class='success'>✅ Configuration looks good!</p>";
    echo "<p><strong>If payment still fails, check:</strong></p>
    <ul>
        <li>Browser console (F12) for JavaScript errors</li>
        <li>Network tab for failed API requests</li>
        <li>Laravel logs in storage/logs/laravel.log</li>
        <li>Make sure cart has items before initiating payment</li>
        <li>Verify address fields are filled correctly</li>
    </ul>";
} else {
    echo "<p class='error'>⚠️ Configuration incomplete - add Razorpay credentials to .env</p>";
}

echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 30px;'>
    Debug completed at " . date('Y-m-d H:i:s') . " | 
    <a href='javascript:location.reload()'>Refresh</a>
</p>";

echo "</body></html>";

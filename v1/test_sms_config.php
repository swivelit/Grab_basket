<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SMS Configuration Check ===\n\n";

echo "SMS_ENABLED: " . (config('services.sms.enabled') ? 'YES' : 'NO') . "\n";
echo "SMS_PROVIDER: " . config('services.sms.provider') . "\n";
echo "TWILIO_SID: " . (config('services.twilio.sid') ? 'Configured' : 'NOT SET') . "\n";
echo "TWILIO_TOKEN: " . (config('services.twilio.token') ? 'Configured' : 'NOT SET') . "\n";
echo "TWILIO_FROM: " . config('services.twilio.from') . "\n";
echo "TWILIO_SENDER_NAME: " . config('services.twilio.sender_name') . "\n\n";

if (!config('services.sms.enabled')) {
    echo "⚠️ SMS is disabled. Set SMS_ENABLED=true in .env to enable.\n";
    exit(0);
}

if (config('services.sms.provider') !== 'twilio') {
    echo "⚠️ SMS provider is not Twilio. Current: " . config('services.sms.provider') . "\n";
    exit(0);
}

echo "=== Testing SMS Service ===\n\n";

// Create a test order object
$testOrder = new stdClass();
$testOrder->id = 12345;
$testOrder->amount = 999;
$testOrder->product = new stdClass();
$testOrder->product->name = "Test Product";

// Create a test seller
$testSeller = new stdClass();
$testSeller->id = 1;
$testSeller->name = "Test Seller";
$testSeller->phone = "+919876543210"; // Replace with actual test phone

// Create a test buyer
$testBuyer = new stdClass();
$testBuyer->id = 2;
$testBuyer->name = "Test Buyer";
$testBuyer->phone = "+919876543210"; // Replace with actual test phone

echo "Test data:\n";
echo "- Seller: {$testSeller->name} ({$testSeller->phone})\n";
echo "- Buyer: {$testBuyer->name} ({$testBuyer->phone})\n";
echo "- Order: #{$testOrder->id} - ₹{$testOrder->amount}\n\n";

// Test SMS Service
$smsService = new \App\Services\SmsService();

echo "1. Testing Seller Order Notification...\n";
try {
    $result1 = $smsService->sendOrderConfirmationToSeller($testSeller, $testOrder);
    echo "   Status: " . ($result1['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    if (!$result1['success']) {
        echo "   Error: " . ($result1['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

echo "2. Testing Buyer Payment Confirmation...\n";
try {
    $result2 = $smsService->sendPaymentConfirmationToBuyer($testBuyer, $testOrder);
    echo "   Status: " . ($result2['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    if (!$result2['success']) {
        echo "   Error: " . ($result2['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

echo "3. Testing Buyer Status Update (shipped)...\n";
try {
    $result3 = $smsService->sendOrderStatusUpdateToBuyer($testBuyer, $testOrder, 'shipped');
    echo "   Status: " . ($result3['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    if (!$result3['success']) {
        echo "   Error: " . ($result3['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

echo "✅ SMS Service setup is complete!\n";
echo "📱 Check Twilio dashboard for SMS delivery status.\n";
echo "⚠️ Note: Replace test phone numbers with real numbers to test actual delivery.\n";

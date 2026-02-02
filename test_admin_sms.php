<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Admin SMS Notifications ===\n\n";

// Check SMS configuration
echo "SMS Configuration:\n";
echo "- SMS_ENABLED: " . (config('services.sms.enabled') ? 'YES' : 'NO') . "\n";
echo "- SMS_PROVIDER: " . config('services.sms.provider') . "\n";
echo "- Admin Numbers: +918438074230, +919659993496\n\n";

if (!config('services.sms.enabled')) {
    echo "⚠️ SMS is disabled. Enable it to test.\n";
    exit(0);
}

// Create test data
$testOrder = new stdClass();
$testOrder->id = 99999;
$testOrder->amount = 1499;
$testOrder->payment_method = 'razorpay';
$testOrder->product = new stdClass();
$testOrder->product->name = "Test Product - Samsung Galaxy Phone";

$testBuyer = new stdClass();
$testBuyer->name = "Vignesh Kumar";

$testSeller = new stdClass();
$testSeller->name = "Tech Store";

echo "Test Order Details:\n";
echo "- Order ID: #{$testOrder->id}\n";
echo "- Product: {$testOrder->product->name}\n";
echo "- Amount: ₹{$testOrder->amount}\n";
echo "- Buyer: {$testBuyer->name}\n";
echo "- Seller: {$testSeller->name}\n";
echo "- Payment: {$testOrder->payment_method}\n\n";

// Test sending SMS to admins
$smsService = new \App\Services\SmsService();

echo "Sending SMS to admin numbers...\n\n";

try {
    $result = $smsService->sendNewOrderNotificationToAdmins($testOrder, $testBuyer, $testSeller);
    
    echo "Overall Status: " . ($result['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n\n";
    
    echo "Individual Results:\n";
    foreach ($result['results'] as $adminResult) {
        $status = $adminResult['success'] ? '✅ SUCCESS' : '❌ FAILED';
        echo "- {$adminResult['phone']}: {$status}";
        if (!$adminResult['success'] && isset($adminResult['error'])) {
            echo " - Error: {$adminResult['error']}";
        }
        echo "\n";
    }
    
    echo "\n";
    
    if ($result['success']) {
        echo "✅ Admin SMS notifications are working!\n";
        echo "📱 Check the admin phones (+918438074230, +919659993496) for SMS.\n";
    } else {
        echo "⚠️ Some or all admin SMS notifications failed.\n";
        echo "Check the errors above and Twilio dashboard for details.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n📋 Expected SMS Message Format:\n";
echo "\"New Order Alert! Order #99999 | Product: Test Product - Samsung Galaxy Phone | Amount: ₹1499 | Buyer: Vignesh Kumar | Seller: Tech Store | Payment: razorpay - grabbaskets-TN\"\n";

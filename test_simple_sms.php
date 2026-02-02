<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Sending Test SMS to Both Admins ===\n\n";

// Both admin numbers
$adminNumbers = [
    '+918438074230',
    '+919659993496'
];

$testMessage = "Test Message from GrabBaskets! This is a test to verify SMS is working correctly. Order system is live and ready! - grabbaskets-TN";

echo "Message: $testMessage\n\n";

// Use TwilioChannel directly
$twilioChannel = new \App\Notifications\Channels\TwilioChannel();

foreach ($adminNumbers as $adminPhone) {
    echo "Sending to: $adminPhone\n";
    
    try {
        $result = $twilioChannel->sendSms($adminPhone, $testMessage);
        
        if ($result['success']) {
            echo "✅ SUCCESS!\n";
            echo "   Message SID: " . $result['message_sid'] . "\n";
        } else {
            echo "❌ FAILED\n";
            echo "   Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "📱 Check both phones for the test messages!\n";

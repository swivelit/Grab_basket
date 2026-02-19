<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DeliveryPartner;
use App\Notifications\DeliveryPartnerNotification;

echo "=== Testing Twilio SMS Notification ===\n\n";

// Get first delivery partner
$partner = DeliveryPartner::first();

if (!$partner) {
    echo "❌ No delivery partner found in database\n";
    exit(1);
}

echo "Partner: {$partner->name}\n";
echo "Phone: {$partner->phone}\n";
echo "Email: {$partner->email}\n\n";

// Check SMS config
echo "SMS Enabled: " . (config('services.sms.enabled') ? 'YES' : 'NO') . "\n";
echo "Twilio SID: " . (config('services.twilio.sid') ? 'Configured' : 'Missing') . "\n";
echo "Twilio Token: " . (config('services.twilio.token') ? 'Configured' : 'Missing') . "\n";
echo "Twilio From: " . (config('services.twilio.from') ?: 'Not set') . "\n\n";

if (!config('services.sms.enabled')) {
    echo "⚠️  SMS is disabled in config. Enable with SMS_ENABLED=true in .env\n";
    exit(1);
}

echo "Sending test SMS notification...\n\n";

try {
    $partner->notify(new DeliveryPartnerNotification(
        '🎉 Test Notification',
        'This is a test SMS from GrabBaskets! If you received this, SMS notifications are working perfectly.',
        'success',
        'https://grabbaskets.com/delivery-partner/dashboard',
        'Open Dashboard',
        ['send_email' => false] // Don't send email, only SMS
    ));
    
    echo "✅ SMS notification sent successfully!\n";
    echo "📱 Check the phone number: {$partner->phone}\n";
    echo "📋 Check Laravel logs for delivery status: storage/logs/laravel.log\n\n";
    
} catch (\Exception $e) {
    echo "❌ Failed to send SMS:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";

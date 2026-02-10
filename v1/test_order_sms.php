<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing SMS Service for Orders...\n\n";

// Get a test user (seller)
$seller = \App\Models\User::where('role', 'seller')->first();
if (!$seller) {
    echo "No seller found in database\n";
    exit(1);
}

// Get a test user (buyer)
$buyer = \App\Models\User::where('role', 'buyer')->first();
if (!$buyer) {
    echo "No buyer found in database\n";
    exit(1);
}

// Get or create a test order
$order = \App\Models\Order::first();
if (!$order) {
    echo "No order found in database\n";
    exit(1);
}

echo "Seller: {$seller->name} ({$seller->phone})\n";
echo "Buyer: {$buyer->name} ({$buyer->phone})\n";
echo "Order ID: {$order->id}\n\n";

// Test SMS Service
$smsService = new \App\Services\SmsService();

echo "1. Testing seller order notification...\n";
$result1 = $smsService->sendOrderConfirmationToSeller($seller, $order);
echo "Result: " . ($result1['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$result1['success']) {
    echo "Error: " . ($result1['error'] ?? 'Unknown') . "\n";
}
echo "\n";

echo "2. Testing buyer payment confirmation...\n";
$result2 = $smsService->sendPaymentConfirmationToBuyer($buyer, $order);
echo "Result: " . ($result2['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$result2['success']) {
    echo "Error: " . ($result2['error'] ?? 'Unknown') . "\n";
}
echo "\n";

echo "3. Testing buyer status update (shipped)...\n";
$result3 = $smsService->sendOrderStatusUpdateToBuyer($buyer, $order, 'shipped');
echo "Result: " . ($result3['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if (!$result3['success']) {
    echo "Error: " . ($result3['error'] ?? 'Unknown') . "\n";
}
echo "\n";

echo "Check Twilio dashboard for SMS delivery status.\n";

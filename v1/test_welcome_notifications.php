<?php

/**
 * Test Notifications with Admin Numbers
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Notifications\BuyerWelcome;
use App\Notifications\SellerWelcome;

echo "🧪 Testing Welcome Notifications with Admin Numbers\n";
echo str_repeat("=", 60) . "\n\n";

// Admin phone numbers
$adminNumbers = ['+918438074230', '+919659993496'];

// Test 1: Create/Update test buyer with admin phone
echo "📧 Test 1: Buyer Welcome Notification\n";
echo str_repeat("-", 60) . "\n";

$testBuyer = User::updateOrCreate(
    ['email' => 'test.buyer@grabbaskets.com'],
    [
        'name' => 'Test Buyer',
        'email' => 'test.buyer@grabbaskets.com',
        'phone' => $adminNumbers[0],
        'role' => 'buyer',
        'password' => bcrypt('password123'),
        'billing_address' => 'Test Address',
        'city' => 'Test City',
        'state' => 'Test State',
        'pincode' => '600001'
    ]
);

echo "Test Buyer Created:\n";
echo "  Name: {$testBuyer->name}\n";
echo "  Email: {$testBuyer->email}\n";
echo "  Phone: {$testBuyer->phone}\n";
echo "  Role: {$testBuyer->role}\n\n";

try {
    $testBuyer->notify(new BuyerWelcome());
    echo "✅ Buyer welcome notification sent successfully!\n";
    echo "   📧 Email: {$testBuyer->email}\n";
    echo "   📱 SMS: {$testBuyer->phone}\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Create/Update test seller with admin phone
echo "📧 Test 2: Seller Welcome Notification\n";
echo str_repeat("-", 60) . "\n";

$testSeller = User::updateOrCreate(
    ['email' => 'test.seller@grabbaskets.com'],
    [
        'name' => 'Test Seller',
        'email' => 'test.seller@grabbaskets.com',
        'phone' => $adminNumbers[1],
        'role' => 'seller',
        'password' => bcrypt('password123'),
        'billing_address' => 'Seller Address',
        'city' => 'Seller City',
        'state' => 'Seller State',
        'pincode' => '600002'
    ]
);

echo "Test Seller Created:\n";
echo "  Name: {$testSeller->name}\n";
echo "  Email: {$testSeller->email}\n";
echo "  Phone: {$testSeller->phone}\n";
echo "  Role: {$testSeller->role}\n\n";

try {
    $testSeller->notify(new SellerWelcome());
    echo "✅ Seller welcome notification sent successfully!\n";
    echo "   📧 Email: {$testSeller->email}\n";
    echo "   📱 SMS: {$testSeller->phone}\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ All tests completed!\n\n";

echo "🔍 What to check:\n";
echo "   1. Email: test.buyer@grabbaskets.com (buyer welcome)\n";
echo "   2. Email: test.seller@grabbaskets.com (seller welcome)\n";
echo "   3. SMS to {$adminNumbers[0]} (buyer welcome)\n";
echo "   4. SMS to {$adminNumbers[1]} (seller welcome)\n";
echo "   5. Twilio dashboard for delivery status\n";
echo "   6. Laravel logs: storage/logs/laravel.log\n\n";

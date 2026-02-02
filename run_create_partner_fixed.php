<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrap complete application for Eloquent
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
// Make a dummy request to bootstrap the framework (starts session, database, etc.)

$kernel->handle(Illuminate\Http\Request::create('/health-check', 'GET'));

use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Hash;

// Now the framework is booted and Eloquent is available
$existing = DeliveryPartner::where('email', 'test@delivery.com')->first();
if ($existing) {
    echo "Test partner already exists: ID={$existing->id}, email={$existing->email}\n";
    exit(0);
}

try {
    $partner = DeliveryPartner::create([
        'name' => 'Test Delivery Partner',
        'email' => 'test@delivery.com',
        'phone' => '9999999999',
        'password' => Hash::make('password123'),
        'address' => 'Test Address',
        'city' => 'Test City',
        'state' => 'Test State',
        'pincode' => '123456',
        'vehicle_type' => 'bike',
        'vehicle_number' => 'TEST-0001',
        'license_number' => 'LIC-TEST-0001',
        'license_expiry' => '2026-12-31',
        'aadhar_number' => '111122223333',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
        'status' => 'approved',
        'is_verified' => true,
        'is_online' => true,
        'is_available' => true
    ]);
    echo "Created test partner: ID={$partner->id}, email={$partner->email}\n";
} catch (\Exception $e) {
    echo "Failed to create partner: " . $e->getMessage() . "\n";
}

<?php
/**
 * Create test delivery partners
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating Test Delivery Partners...\n\n";

$partners = [
    [
        'name' => 'Rajesh Kumar',
        'email' => 'rajesh@grabbaskets.com',
        'phone' => '+919876543210',
        'password' => bcrypt('password123'),
        'address' => 'Theni, Tamil Nadu',
        'status' => 'approved',
        'is_online' => true,
        'is_available' => true,
        'vehicle_type' => 'bike',
        'vehicle_number' => 'TN01AB1234',
        'rating' => 4.5,
    ],
    [
        'name' => 'Priya Sharma',
        'email' => 'priya@grabbaskets.com',
        'phone' => '+919876543211',
        'password' => bcrypt('password123'),
        'address' => 'Theni, Tamil Nadu',
        'status' => 'approved',
        'is_online' => true,
        'is_available' => false,
        'vehicle_type' => 'scooter',
        'vehicle_number' => 'TN02CD5678',
        'rating' => 4.7,
    ],
];

foreach ($partners as $partnerData) {
    $existing = \App\Models\DeliveryPartner::where('email', $partnerData['email'])->first();
    
    if ($existing) {
        echo "⚠️  {$partnerData['name']} already exists (ID: {$existing->id})\n";
        continue;
    }
    
    $partner = \App\Models\DeliveryPartner::create($partnerData);
    echo "✅ Created: {$partner->name} (ID: {$partner->id})\n";
}

echo "\n=== All Delivery Partners ===\n";
$all = \App\Models\DeliveryPartner::all();
foreach ($all as $p) {
    echo "  - ID: {$p->id}, Name: {$p->name}, Status: {$p->status}\n";
}

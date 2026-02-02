<?php
/**
 * Check if delivery partner 3 exists
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Delivery Partner ID 3...\n\n";

$partner = \App\Models\DeliveryPartner::find(3);

if ($partner) {
    echo "✅ Delivery Partner #3 EXISTS\n";
    echo "Name: " . $partner->name . "\n";
    echo "Email: " . $partner->email . "\n";
    echo "Phone: " . $partner->phone . "\n";
    echo "Status: " . $partner->status . "\n";
    echo "Online: " . ($partner->is_online ? 'Yes' : 'No') . "\n";
    echo "Available: " . ($partner->is_available ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Delivery Partner #3 DOES NOT EXIST\n\n";
    echo "Available Delivery Partners:\n";
    $partners = \App\Models\DeliveryPartner::all();
    foreach ($partners as $p) {
        echo "  - ID: {$p->id}, Name: {$p->name}, Status: {$p->status}\n";
    }
}

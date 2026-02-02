<?php
/**
 * List all delivery partners with their IDs
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALL DELIVERY PARTNERS IN DATABASE ===\n\n";

$partners = \App\Models\DeliveryPartner::all();

if ($partners->count() == 0) {
    echo "No delivery partners found.\n";
} else {
    foreach ($partners as $partner) {
        echo "ID: {$partner->id}\n";
        echo "Name: {$partner->name}\n";
        echo "Email: {$partner->email}\n";
        echo "Phone: {$partner->phone}\n";
        echo "Status: {$partner->status}\n";
        echo "Created: {$partner->created_at}\n";
        echo "---\n";
    }
    
    echo "\nTotal: {$partners->count()} partner(s)\n";
}

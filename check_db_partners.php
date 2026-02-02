<?php
/**
 * Check database directly for delivery partners
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIRECT DATABASE QUERY ===\n\n";

$partners = DB::table('delivery_partners')->get();

foreach ($partners as $partner) {
    echo "ID: {$partner->id}\n";
    echo "Name: {$partner->name}\n";
    echo "Email: {$partner->email}\n";
    echo "Phone: {$partner->phone}\n";
    echo "Status: {$partner->status}\n";
    if (isset($partner->deleted_at)) {
        echo "Deleted: {$partner->deleted_at}\n";
    }
    echo "---\n";
}

echo "\nTotal rows: " . $partners->count() . "\n";

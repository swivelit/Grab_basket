<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking User and Seller Email Mismatches\n";
echo "==========================================\n\n";

// Check user 3
$user3 = App\Models\User::find(3);
if ($user3) {
    echo "User ID 3:\n";
    echo "  Name: {$user3->name}\n";
    echo "  Email: {$user3->email}\n";
    
    $seller3 = App\Models\Seller::where('email', $user3->email)->first();
    if ($seller3) {
        echo "  ✅ Seller entry exists (ID: {$seller3->id})\n";
    } else {
        echo "  ❌ No seller entry with this email\n";
    }
    echo "\n";
}

// Check user 4
$user4 = App\Models\User::find(4);
if ($user4) {
    echo "User ID 4:\n";
    echo "  Name: {$user4->name}\n";
    echo "  Email: {$user4->email}\n";
    
    $seller4 = App\Models\Seller::where('email', $user4->email)->first();
    if ($seller4) {
        echo "  ✅ Seller entry exists (ID: {$seller4->id})\n";
    } else {
        echo "  ❌ No seller entry with this email\n";
    }
    echo "\n";
}

// Show all sellers
echo "All Sellers:\n";
$sellers = App\Models\Seller::all();
foreach ($sellers as $s) {
    echo "  Seller ID {$s->id}: {$s->store_name} - {$s->name} ({$s->email})\n";
}

echo "\nDone!\n";

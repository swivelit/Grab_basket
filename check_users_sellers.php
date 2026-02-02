<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking if Missing Sellers Exist in Users Table\n";
echo "=================================================\n\n";

$missingSellers = [13, 14, 18];

foreach ($missingSellers as $sellerId) {
    $user = DB::table('users')->where('id', $sellerId)->first();
    
    if ($user) {
        echo "✓ User ID {$sellerId} EXISTS in users table\n";
        echo "  Name: {$user->name}\n";
        echo "  Email: {$user->email}\n";
        echo "  Role: " . ($user->role ?? 'N/A') . "\n";
        
        // Check if this user has seller record
        $seller = DB::table('sellers')->where('id', $sellerId)->orWhere('email', $user->email)->first();
        if ($seller) {
            echo "  ✓ Has seller record (ID: {$seller->id})\n";
        } else {
            echo "  ✗ NO seller record found\n";
        }
        echo "\n";
    } else {
        echo "✗ User ID {$sellerId} NOT FOUND in users table\n\n";
    }
}

// Show current sellers
echo "Current Sellers in sellers table:\n";
$sellers = DB::table('sellers')->get(['id', 'name', 'email', 'store_name']);
foreach ($sellers as $seller) {
    echo "  - ID {$seller->id}: {$seller->store_name} ({$seller->name}, {$seller->email})\n";
}

echo "\nDone!\n";

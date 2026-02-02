<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Fixing Product Seller ID Mismatches\n";
echo "====================================\n\n";

// Mapping of user_id to seller_id based on email match
$mapping = [
    13 => 3,  // samytheni79@gmail.com
    14 => 4,  // maltrix.nutrition@gmail.com
    18 => 5,  // ragulapn@gmail.com
];

foreach ($mapping as $oldId => $newId) {
    $count = DB::table('products')->where('seller_id', $oldId)->count();
    
    if ($count > 0) {
        echo "Updating {$count} products from seller_id {$oldId} to {$newId}...\n";
        
        $affected = DB::table('products')
            ->where('seller_id', $oldId)
            ->update(['seller_id' => $newId]);
            
        echo "  ✓ Updated {$affected} products\n";
    }
}

echo "\n✅ Fix complete!\n";

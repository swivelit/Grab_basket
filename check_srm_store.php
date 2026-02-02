<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Seller;
use App\Models\User;
use App\Models\Product;

echo "=== Checking for 'SRM' Store ===\n\n";

// Search for sellers with 'srm' in name or store_name
$sellers = Seller::where('name', 'like', '%srm%')
    ->orWhere('store_name', 'like', '%srm%')
    ->get();

echo "Found " . $sellers->count() . " seller(s) matching 'srm':\n\n";

foreach ($sellers as $seller) {
    echo "Seller ID: " . $seller->id . "\n";
    echo "Name: " . $seller->name . "\n";
    echo "Store Name: " . ($seller->store_name ?? 'N/A') . "\n";
    echo "Email: " . $seller->email . "\n";
    
    // Find corresponding user
    $user = User::where('email', $seller->email)->first();
    if ($user) {
        echo "User ID: " . $user->id . "\n";
        
        // Count products
        $productCount = Product::where('seller_id', $user->id)->count();
        echo "Product Count: " . $productCount . "\n";
        
        if ($productCount > 0) {
            echo "\nSample Products:\n";
            $products = Product::where('seller_id', $user->id)->take(5)->get();
            foreach ($products as $product) {
                echo "  - " . $product->name . " (ID: " . $product->id . ")\n";
            }
        }
    } else {
        echo "User ID: NOT FOUND (email doesn't match any user)\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

if ($sellers->isEmpty()) {
    echo "No sellers found with 'srm' in name or store name.\n";
    echo "\nSearching all sellers:\n";
    $allSellers = Seller::all();
    echo "Total sellers in database: " . $allSellers->count() . "\n\n";
    
    if ($allSellers->count() > 0) {
        echo "Sample sellers:\n";
        foreach ($allSellers->take(10) as $seller) {
            echo "  - " . $seller->name . " (Store: " . ($seller->store_name ?? 'N/A') . ")\n";
        }
    }
}

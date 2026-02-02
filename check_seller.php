<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Seller;
use App\Models\Product;

echo "=== Checking Seller Information ===\n";

// Check if seller exists
$seller = Seller::where('email', 'swivel.training@gmail.com')->first();

if ($seller) {
    echo "✅ Seller found!\n";
    echo "ID: {$seller->id}\n";
    echo "Name: {$seller->name}\n";
    echo "Email: {$seller->email}\n";
    echo "Phone: {$seller->phone}\n";
    echo "Status: {$seller->status}\n";
    
    // Check current products assigned to this seller
    $currentProducts = Product::where('seller_id', $seller->id)->count();
    echo "Current products assigned: {$currentProducts}\n";
    
} else {
    echo "❌ Seller not found with email: swivel.training@gmail.com\n";
    echo "\n=== Available sellers ===\n";
    $sellers = Seller::select('id', 'name', 'email')->get();
    foreach ($sellers as $s) {
        echo "ID: {$s->id} | Name: {$s->name} | Email: {$s->email}\n";
    }
}

// Show total products and how many are unassigned
$totalProducts = Product::count();
$unassignedProducts = Product::whereNull('seller_id')->orWhere('seller_id', 0)->count();

echo "\n=== Product Statistics ===\n";
echo "Total products: {$totalProducts}\n";
echo "Unassigned products: {$unassignedProducts}\n";
?>
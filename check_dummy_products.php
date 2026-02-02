<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING USER ROLES AND PRODUCT DISTRIBUTION ===" . PHP_EOL;

// Check user roles
$roles = App\Models\User::select('role')->distinct()->pluck('role')->toArray();
echo "User roles found: " . implode(', ', $roles) . PHP_EOL . PHP_EOL;

// Check products distribution
$productsWithoutSeller = App\Models\Product::whereNull('seller_id')->count();
$productsWithSeller = App\Models\Product::whereNotNull('seller_id')->count();
$totalProducts = App\Models\Product::count();

echo "Total products: $totalProducts" . PHP_EOL;
echo "Products without seller_id (likely dummy/test): $productsWithoutSeller" . PHP_EOL;
echo "Products with seller_id (legitimate): $productsWithSeller" . PHP_EOL . PHP_EOL;

// Get sample dummy products (without seller_id)
echo "Sample dummy products (first 10):" . PHP_EOL;
$dummyProducts = App\Models\Product::whereNull('seller_id')->limit(10)->get(['id', 'name', 'seller_id']);
foreach ($dummyProducts as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Seller ID: " . ($product->seller_id ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . "Sample legitimate products (first 10):" . PHP_EOL;
$realProducts = App\Models\Product::whereNotNull('seller_id')->limit(10)->get(['id', 'name', 'seller_id']);
foreach ($realProducts as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Seller ID: {$product->seller_id}" . PHP_EOL;
}

// Check user types by role
echo PHP_EOL . "User distribution by role:" . PHP_EOL;
foreach ($roles as $role) {
    $count = App\Models\User::where('role', $role)->count();
    echo "$role: $count users" . PHP_EOL;
}

echo PHP_EOL . "=== ANALYSIS COMPLETE ===" . PHP_EOL;
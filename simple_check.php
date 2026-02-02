<?php

use App\Models\Product;
use App\Models\User;

// Check product distribution
$productsWithoutSeller = Product::whereNull('seller_id')->count();
$productsWithSeller = Product::whereNotNull('seller_id')->count();

echo "Products without seller_id: $productsWithoutSeller" . PHP_EOL;
echo "Products with seller_id: $productsWithSeller" . PHP_EOL;

// Sample product names to understand dummy pattern
$dummyProducts = Product::whereNull('seller_id')->limit(5)->pluck('name')->toArray();
echo "Sample dummy product names: " . implode(', ', $dummyProducts) . PHP_EOL;

// Get user roles
$roles = User::distinct('role')->pluck('role')->toArray();
echo "User roles: " . implode(', ', $roles) . PHP_EOL;
?>
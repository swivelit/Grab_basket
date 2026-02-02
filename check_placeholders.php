<?php
// Usage: php artisan tinker --execute="require base_path('check_placeholders.php');"

use App\Models\Product;
use App\Models\ProductImage;

echo "Checking for placeholder URLs in database...\n";

$products = Product::where('image', 'LIKE', '%placeholder%')->get();
echo "Products with placeholder: " . $products->count() . "\n";
foreach($products as $p) {
    echo "ID: {$p->id} - {$p->name} - Image: {$p->image}\n";
}

$images = ProductImage::where('image_path', 'LIKE', '%placeholder%')->get();
echo "\nProductImages with placeholder: " . $images->count() . "\n";
foreach($images as $i) {
    echo "ID: {$i->id} - Product: {$i->product_id} - Path: {$i->image_path}\n";
}

echo "\nNow removing all placeholder references...\n";

// Remove from products table
$removed1 = Product::where('image', 'LIKE', '%placeholder%')->update(['image' => null]);
echo "Removed from products: $removed1\n";

// Remove from product_images table
$removed2 = ProductImage::where('image_path', 'LIKE', '%placeholder%')->delete();
echo "Removed from product_images: $removed2\n";

echo "\n✅ Cleanup complete!\n";

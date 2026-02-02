<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

echo "=== Current Perfume & Deodorant Images ===\n";

$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if ($beautyCategory) {
    $beautyProducts = Product::where('category_id', $beautyCategory->id)->get();
    
    echo "Found {$beautyProducts->count()} perfume/deodorant products:\n\n";
    
    foreach ($beautyProducts as $index => $product) {
        echo ($index + 1) . ". {$product->name}\n";
        echo "   Current Image: {$product->image}\n";
        echo "   Price: ₹{$product->price} | Discount: {$product->discount}%\n\n";
    }
} else {
    echo "❌ BEAUTY & PERSONAL CARE category not found\n";
}
?>
<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if ($beautyCategory) {
    $beautyProducts = Product::where('category_id', $beautyCategory->id)->take(5)->get();
    
    echo "Beauty products images:\n";
    foreach ($beautyProducts as $product) {
        echo "Name: {$product->name}\n";
        echo "Image: {$product->image}\n";
        echo "Contains placeholder: " . (strpos($product->image, 'placeholder') !== false ? 'YES' : 'NO') . "\n\n";
    }
}
?>
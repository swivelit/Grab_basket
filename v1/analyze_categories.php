<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

echo "=== Current Categories and Products Analysis ===\n";

$categories = Category::all();
foreach ($categories as $category) {
    $totalProducts = Product::where('category_id', $category->id)->count();
    $productsWithImages = Product::where('category_id', $category->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->count();
    
    echo "📦 {$category->name}: {$totalProducts} total, {$productsWithImages} with relevant images\n";
    
    if ($productsWithImages > 0) {
        $sampleProducts = Product::where('category_id', $category->id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->where('image', 'NOT LIKE', '%unsplash%')
            ->where('image', 'NOT LIKE', '%placeholder%')
            ->where('image', 'NOT LIKE', '%via.placeholder%')
            ->take(3)
            ->get();
        
        foreach ($sampleProducts as $product) {
            echo "   • {$product->name}\n";
        }
        echo "\n";
    }
}

echo "\n=== Checking for additional product types ===\n";
// Look for products that might be perfumes or other categories
$allProducts = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('image', 'NOT LIKE', '%placeholder%')
    ->where('image', 'NOT LIKE', '%via.placeholder%')
    ->get();

$productTypes = [];
foreach ($allProducts as $product) {
    $name = strtolower($product->name);
    if (strpos($name, 'masala') !== false || strpos($name, 'spice') !== false) {
        $productTypes['Masala/Spice'][] = $product->name;
    } elseif (strpos($name, 'toothpaste') !== false || strpos($name, 'dental') !== false || strpos($name, 'oral') !== false) {
        $productTypes['Dental Care'][] = $product->name;
    } elseif (strpos($name, 'perfume') !== false || strpos($name, 'fragrance') !== false) {
        $productTypes['Perfume/Fragrance'][] = $product->name;
    } else {
        $productTypes['Other'][] = $product->name;
    }
}

foreach ($productTypes as $type => $products) {
    echo "🏷️  {$type}: " . count($products) . " products\n";
    if (count($products) <= 5) {
        foreach ($products as $product) {
            echo "   • {$product}\n";
        }
    } else {
        foreach (array_slice($products, 0, 3) as $product) {
            echo "   • {$product}\n";
        }
        echo "   ... and " . (count($products) - 3) . " more\n";
    }
    echo "\n";
}
?>
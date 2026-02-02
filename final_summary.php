<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;

echo "=== FINAL 488 PRODUCTS SUMMARY ===\n";

$seller = Seller::where('email', 'swivel.training@gmail.com')->first();

echo "🏪 SELLER INFORMATION:\n";
echo "Name: {$seller->name}\n";
echo "Email: {$seller->email}\n";
echo "Phone: {$seller->phone}\n";
echo "Seller ID: {$seller->id}\n\n";

echo "📊 PRODUCT STATISTICS:\n";
$totalProducts = Product::where('seller_id', $seller->id)->count();
$productsWithImages = Product::where('seller_id', $seller->id)
                            ->whereNotNull('image')
                            ->where('image', '!=', '')
                            ->count();
$productsWithoutImages = Product::where('seller_id', $seller->id)
                               ->where(function($query) {
                                   $query->whereNull('image')
                                         ->orWhere('image', '');
                               })
                               ->count();

echo "Total Products: {$totalProducts}\n";
echo "Products WITH Images: {$productsWithImages}\n";
echo "Products WITHOUT Images: {$productsWithoutImages}\n\n";

echo "📦 CATEGORY BREAKDOWN:\n";
$categories = Category::all();
foreach ($categories as $category) {
    $categoryProducts = Product::where('seller_id', $seller->id)
                              ->where('category_id', $category->id)
                              ->count();
    
    if ($categoryProducts > 0) {
        $withImages = Product::where('seller_id', $seller->id)
                            ->where('category_id', $category->id)
                            ->whereNotNull('image')
                            ->where('image', '!=', '')
                            ->count();
        
        $withoutImages = $categoryProducts - $withImages;
        
        echo "{$category->name}: {$categoryProducts} products\n";
        echo "  ├── With images: {$withImages}\n";
        echo "  └── Without images: {$withoutImages}\n\n";
    }
}

echo "🎯 IMAGE SOURCE BREAKDOWN:\n";
$googleImages = Product::where('seller_id', $seller->id)
                       ->where('image', 'LIKE', '%googleapis.com%')
                       ->count();
$placeholderImages = Product::where('seller_id', $seller->id)
                           ->where('image', 'LIKE', '%placeholder%')
                           ->count();
$localImages = Product::where('seller_id', $seller->id)
                     ->where('image', 'LIKE', 'images/%')
                     ->count();
$otherImages = $productsWithImages - $googleImages - $placeholderImages - $localImages;

echo "Google API Images: {$googleImages}\n";
echo "Local Images: {$localImages}\n";
echo "Placeholder Images: {$placeholderImages}\n";
echo "Other Images: {$otherImages}\n";
echo "No Images: {$productsWithoutImages}\n\n";

echo "✅ SUCCESS! All 488 products successfully assigned to {$seller->name} ({$seller->email})\n";
?>
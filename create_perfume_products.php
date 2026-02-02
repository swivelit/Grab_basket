<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Seller;

echo "=== Creating Perfume & Additional Categories ===\n";

// Get the seller
$seller = Seller::where('email', 'swivel.training@gmail.com')->first();
if (!$seller) {
    echo "❌ Seller not found\n";
    exit(1);
}

// Find or create BEAUTY & PERSONAL CARE category for perfumes
$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if (!$beautyCategory) {
    echo "❌ BEAUTY & PERSONAL CARE category not found\n";
    exit(1);
}

// Create perfume subcategory if it doesn't exist
$perfumeSubcat = Subcategory::where('category_id', $beautyCategory->id)
                           ->where('name', 'PERFUMES & FRAGRANCES')
                           ->first();

if (!$perfumeSubcat) {
    $perfumeSubcat = Subcategory::create([
        'name' => 'PERFUMES & FRAGRANCES',
        'category_id' => $beautyCategory->id,
        'unique_id' => 'PF' . rand(100, 999)
    ]);
    echo "✅ Created PERFUMES & FRAGRANCES subcategory\n";
}

// Perfume products to create
$perfumeProducts = [
    ['name' => 'Fogg Fresh Aqua Body Spray 150ml', 'price' => 195, 'discount' => 20],
    ['name' => 'Wild Stone Thunder Deodorant 150ml', 'price' => 220, 'discount' => 25],
    ['name' => 'Axe Apollo Bodyspray 150ml', 'price' => 230, 'discount' => 18],
    ['name' => 'Denver Pride Deodorant 165ml', 'price' => 180, 'discount' => 22],
    ['name' => 'Park Avenue Storm Deodorant 130ml', 'price' => 200, 'discount' => 15],
    ['name' => 'Engage M1 Perfume Spray 120ml', 'price' => 250, 'discount' => 30],
    ['name' => 'Set Wet Charm Avatar Deodorant', 'price' => 165, 'discount' => 20],
    ['name' => 'Nivea Fresh Active Deodorant 150ml', 'price' => 210, 'discount' => 16],
    ['name' => 'Garnier Men Turbo Light Deodorant', 'price' => 185, 'discount' => 24],
    ['name' => 'Fa Men Xtreme Cool Deodorant 150ml', 'price' => 175, 'discount' => 19],
    ['name' => 'Rexona Men Ice Cool Deodorant', 'price' => 190, 'discount' => 21],
    ['name' => 'Adidas Dynamic Pulse Deodorant', 'price' => 240, 'discount' => 28],
    ['name' => 'Old Spice Original Deodorant 150ml', 'price' => 220, 'discount' => 17],
    ['name' => 'Denim Black Deodorant 150ml', 'price' => 160, 'discount' => 23],
    ['name' => 'Yardley Gentleman Deodorant 150ml', 'price' => 205, 'discount' => 26],
];

echo "Creating perfume/fragrance products...\n";
$createdPerfumes = 0;

foreach ($perfumeProducts as $perfumeData) {
    $product = Product::create([
        'name' => $perfumeData['name'],
        'unique_id' => 'P' . rand(1000, 9999),
        'category_id' => $beautyCategory->id,
        'subcategory_id' => $perfumeSubcat->id,
        'seller_id' => $seller->id,
        'image' => 'https://via.placeholder.com/200?text=' . urlencode(substr($perfumeData['name'], 0, 20)),
        'description' => "Premium {$perfumeData['name']} with long-lasting fragrance. Perfect for daily use and special occasions.",
        'price' => $perfumeData['price'],
        'discount' => $perfumeData['discount'],
        'delivery_charge' => rand(0, 1) ? 0 : rand(20, 50),
        'gift_option' => rand(0, 1),
        'stock' => rand(15, 80),
    ]);
    
    $createdPerfumes++;
}

echo "✅ Created {$createdPerfumes} perfume products\n\n";

// Now update the index route to show shuffled products from multiple categories
echo "📊 Updated Product Statistics:\n";
$cookingProducts = Product::where('category_id', Category::where('name', 'COOKING')->first()->id)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->count();

$dentalProducts = Product::where('category_id', Category::where('name', 'DENTAL CARE')->first()->id)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->count();

$perfumeProductsCount = Product::where('category_id', $beautyCategory->id)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->count();

echo "🌶️  COOKING (Masala/Spices): {$cookingProducts} products with images\n";
echo "🦷 DENTAL CARE: {$dentalProducts} products with images\n";
echo "🌸 PERFUMES & FRAGRANCES: {$perfumeProductsCount} products with images\n";

$totalWithImages = $cookingProducts + $dentalProducts + $perfumeProductsCount;
echo "📦 Total products with relevant images: {$totalWithImages}\n";

echo "\n✅ Ready to update index page with shuffled multi-category products!\n";
?>
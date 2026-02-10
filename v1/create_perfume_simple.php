<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Seller;

echo "=== Creating Perfume Products Directly ===\n";

// Get the seller
$seller = Seller::where('email', 'swivel.training@gmail.com')->first();
if (!$seller) {
    echo "❌ Seller not found\n";
    exit(1);
}

// Find BEAUTY & PERSONAL CARE category for perfumes
$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if (!$beautyCategory) {
    echo "❌ BEAUTY & PERSONAL CARE category not found\n";
    exit(1);
}

// Use existing subcategory or find any subcategory in beauty category
$existingSubcat = Subcategory::where('category_id', $beautyCategory->id)->first();
if (!$existingSubcat) {
    echo "❌ No subcategories found in BEAUTY & PERSONAL CARE\n";
    exit(1);
}

echo "✅ Using subcategory: {$existingSubcat->name} (ID: {$existingSubcat->id})\n";

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
];

echo "Creating perfume/fragrance products...\n";
$createdPerfumes = 0;

foreach ($perfumeProducts as $index => $perfumeData) {
    $product = Product::create([
        'name' => $perfumeData['name'],
        'unique_id' => 'PR' . ($index + 1000),
        'category_id' => $beautyCategory->id,
        'subcategory_id' => $existingSubcat->id,
        'seller_id' => $seller->id,
        'image' => 'https://via.placeholder.com/200?text=' . urlencode(substr($perfumeData['name'], 0, 15)),
        'description' => "Premium {$perfumeData['name']} with long-lasting fragrance. Perfect for daily use and special occasions.",
        'price' => $perfumeData['price'],
        'discount' => $perfumeData['discount'],
        'delivery_charge' => rand(0, 1) ? 0 : rand(20, 50),
        'gift_option' => rand(0, 1),
        'stock' => rand(15, 80),
    ]);
    
    $createdPerfumes++;
    echo "✅ Created: {$perfumeData['name']}\n";
}

echo "\n✅ Created {$createdPerfumes} perfume products in BEAUTY & PERSONAL CARE category\n";

// Now show product statistics for shuffling
echo "\n📊 Product Categories with Relevant Images:\n";
$categories = ['COOKING', 'DENTAL CARE', 'BEAUTY & PERSONAL CARE'];

foreach ($categories as $categoryName) {
    $category = Category::where('name', $categoryName)->first();
    if ($category) {
        $count = Product::where('category_id', $category->id)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->count();
        echo "🏷️  {$categoryName}: {$count} products with images\n";
    }
}

echo "\n✅ Ready to update index page with shuffled multi-category products!\n";
?>
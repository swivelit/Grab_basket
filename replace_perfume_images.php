<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

echo "=== Replacing Perfume & Deodorant Images ===\n";

$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if (!$beautyCategory) {
    echo "❌ BEAUTY & PERSONAL CARE category not found\n";
    exit(1);
}

$beautyProducts = Product::where('category_id', $beautyCategory->id)->get();

echo "Found {$beautyProducts->count()} perfume/deodorant products to update\n\n";

// New image assignments for perfume and deodorant products
// Using different SRM images and hash-named images
$newImageAssignments = [
    'Fogg Fresh Aqua Body Spray 150ml' => 'images/0Rc193BfOQ4pDAtqAYBc1SLfKm2E9Hoklwo643Fz.jpg',
    'Wild Stone Thunder Deodorant 150ml' => 'images/aczr1zreX0Jw0l4niJD6wGAmX2J3z2J8ge3nNecp.jpg',
    'Axe Apollo Bodyspray 150ml' => 'images/aKeXz8Co4faYkar4I3FGNnt2j4VIxWxqzsDGE7un.jpg',
    'Denver Pride Deodorant 165ml' => 'images/AvirMgWOgURzcWWJqzBtiuRddYcM81QW3NfqTPRP.jpg',
    'Park Avenue Storm Deodorant 130ml' => 'images/cfUCOEbH4RPRYYXI7doocTkff8eKlechZD3cN0lC.jpg',
    'Engage M1 Perfume Spray 120ml' => 'images/fbrlb4SLwvlPY0dcvLnjDusIuV95VxQ3BLecAc6y.jpg',
    'Set Wet Charm Avatar Deodorant' => 'images/Jk8QmsaFMVBNHsezYSGDgfGhHrcnRQIBi9OYe6Cb.jpg',
    'Nivea Fresh Active Deodorant 150ml' => 'images/NgArJLzX4xG2BT7rSLQaRYqG5AncjCWFP7lnbpyL.jpg',
    'Garnier Men Turbo Light Deodorant' => 'images/POfF4zui8d9WjEYzixaP4i9stALknII0PBncTWPS.jpg',
    'Fa Men Xtreme Cool Deodorant 150ml' => 'images/SWOcAL80GqXpEZl1mQmzn9bBzsAXX65WNGIcATwE.jpg',
];

// Alternative: Use higher numbered SRM images (these might be more suitable for beauty products)
$alternativeImages = [
    'Fogg Fresh Aqua Body Spray 150ml' => 'images/SRM750.jpg',
    'Wild Stone Thunder Deodorant 150ml' => 'images/SRM751.jpg',
    'Axe Apollo Bodyspray 150ml' => 'images/SRM752.jpg',
    'Denver Pride Deodorant 165ml' => 'images/SRM753.jpg',
    'Park Avenue Storm Deodorant 130ml' => 'images/SRM754.jpg',
    'Engage M1 Perfume Spray 120ml' => 'images/SRM755.png',
    'Set Wet Charm Avatar Deodorant' => 'images/SRM756.png',
    'Nivea Fresh Active Deodorant 150ml' => 'images/SRM757.png',
    'Garnier Men Turbo Light Deodorant' => 'images/SRM761.jpg',
    'Fa Men Xtreme Cool Deodorant 150ml' => 'images/SRM762.png',
];

echo "🔄 OPTION 1: Replace with hash-named images from images/ directory\n";
echo "🔄 OPTION 2: Replace with higher-numbered SRM images (SRM750+)\n\n";

echo "Using OPTION 1 (hash-named images) for better variety...\n\n";

$updated = 0;
foreach ($beautyProducts as $product) {
    $oldImage = $product->image;
    
    if (isset($newImageAssignments[$product->name])) {
        $newImage = $newImageAssignments[$product->name];
        $product->update(['image' => $newImage]);
        
        echo "✅ Updated: {$product->name}\n";
        echo "   OLD: {$oldImage}\n";
        echo "   NEW: {$newImage}\n\n";
        
        $updated++;
    } else {
        echo "⚠️  No replacement found for: {$product->name}\n";
    }
}

echo "🎯 REPLACEMENT SUMMARY:\n";
echo "Total products updated: {$updated}\n";
echo "New images source: Local hash-named images from images/ directory\n";
echo "Image format: Various (jpg files with unique hash names)\n\n";

echo "📸 NEW IMAGE ASSIGNMENTS:\n";
foreach ($newImageAssignments as $productName => $imagePath) {
    echo "• {$productName} -> {$imagePath}\n";
}

echo "\n✅ All perfume and deodorant images have been replaced!\n";
echo "The products now use different local images for better variety.\n";

// Show final verification
echo "\n🔍 VERIFICATION - Updated Beauty Products:\n";
$updatedProducts = Product::where('category_id', $beautyCategory->id)->get();
foreach ($updatedProducts as $index => $product) {
    echo ($index + 1) . ". {$product->name} -> {$product->image}\n";
}

echo "\n🎉 Image replacement completed successfully!\n";
?>
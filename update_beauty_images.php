<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

echo "=== Updating Beauty Product Images ===\n";

$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
if (!$beautyCategory) {
    echo "❌ Beauty category not found\n";
    exit(1);
}

$beautyProducts = Product::where('category_id', $beautyCategory->id)->get();

echo "Found {$beautyProducts->count()} beauty products to update\n\n";

// Better image URLs for perfume/deodorant products (using proper image hosting)
$imageUpdates = [
    'Fogg Fresh Aqua Body Spray 150ml' => 'https://images.unsplash.com/photo-1541199249251-f713e6145474?w=200',
    'Wild Stone Thunder Deodorant 150ml' => 'https://images.unsplash.com/photo-1585855229632-a3299e7ea0d2?w=200', 
    'Axe Apollo Bodyspray 150ml' => 'https://images.unsplash.com/photo-1580870069867-74c57ee1bb07?w=200',
    'Denver Pride Deodorant 165ml' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=200',
    'Park Avenue Storm Deodorant 130ml' => 'https://images.unsplash.com/photo-1592837595155-4befc6c3bfcf?w=200',
    'Engage M1 Perfume Spray 120ml' => 'https://images.unsplash.com/photo-1588405748880-12d1d2a59db9?w=200',
    'Set Wet Charm Avatar Deodorant' => 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=200',
    'Nivea Fresh Active Deodorant 150ml' => 'https://images.unsplash.com/photo-1562887284-947659fef048?w=200',
    'Garnier Men Turbo Light Deodorant' => 'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?w=200',
    'Fa Men Xtreme Cool Deodorant 150ml' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=200',
];

$updated = 0;
foreach ($beautyProducts as $product) {
    if (isset($imageUpdates[$product->name])) {
        $product->update(['image' => $imageUpdates[$product->name]]);
        echo "✅ Updated: {$product->name}\n";
        $updated++;
    } else {
        // Generic perfume image for any products not in our list
        $product->update(['image' => 'https://images.unsplash.com/photo-1581125715709-e5b8b8f6d4b4?w=200']);
        echo "✅ Updated (generic): {$product->name}\n";
        $updated++;
    }
}

echo "\n✅ Updated {$updated} beauty product images with proper URLs\n";

// However, since we're filtering out unsplash images, let me update with non-unsplash URLs
echo "\nUsing non-Unsplash image URLs instead...\n";

$betterImages = [
    'Fogg Fresh Aqua Body Spray 150ml' => 'images/SRM701.jpg',
    'Wild Stone Thunder Deodorant 150ml' => 'images/SRM702.jpg', 
    'Axe Apollo Bodyspray 150ml' => 'images/SRM703.jpg',
    'Denver Pride Deodorant 165ml' => 'images/SRM704.jpg',
    'Park Avenue Storm Deodorant 130ml' => 'images/SRM705.jpg',
    'Engage M1 Perfume Spray 120ml' => 'images/SRM706.jpg',
    'Set Wet Charm Avatar Deodorant' => 'images/SRM707.jpg',
    'Nivea Fresh Active Deodorant 150ml' => 'images/SRM708.jpg',
    'Garnier Men Turbo Light Deodorant' => 'images/SRM709.jpg',
    'Fa Men Xtreme Cool Deodorant 150ml' => 'images/SRM710.jpg',
];

$finalUpdated = 0;
foreach ($beautyProducts as $product) {
    if (isset($betterImages[$product->name])) {
        $product->update(['image' => $betterImages[$product->name]]);
        echo "✅ Final update: {$product->name} -> {$betterImages[$product->name]}\n";
        $finalUpdated++;
    }
}

echo "\n🎯 Final result: {$finalUpdated} beauty products now have local image files\n";
echo "These should now appear in the shuffled product mix!\n";
?>
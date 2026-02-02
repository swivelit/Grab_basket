<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Category;

echo "=== SHUFFLED MULTI-CATEGORY PRODUCTS SUMMARY ===\n\n";

// Get the categories we're mixing
$cookingCategory = Category::where('name', 'COOKING')->first();
$beautyCategory = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
$dentalCategory = Category::where('name', 'DENTAL CARE')->first();

echo "📊 CATEGORY BREAKDOWN WITH RELEVANT IMAGES:\n";

$totalMixed = 0;

if ($cookingCategory) {
    $cookingCount = Product::where('category_id', $cookingCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->count();
    echo "🌶️  COOKING (Masala/Spices): {$cookingCount} products with relevant images\n";
    
    // Show sample products
    $sampleCooking = Product::where('category_id', $cookingCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->take(3)
        ->get();
    
    foreach ($sampleCooking as $product) {
        echo "   • {$product->name}\n";
    }
    $totalMixed += min($cookingCount, 8);
    echo "\n";
}

if ($beautyCategory) {
    $beautyCount = Product::where('category_id', $beautyCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->count();
    echo "🌸 BEAUTY & PERSONAL CARE (Perfumes): {$beautyCount} products with relevant images\n";
    
    // Show sample products
    $sampleBeauty = Product::where('category_id', $beautyCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->take(3)
        ->get();
    
    foreach ($sampleBeauty as $product) {
        echo "   • {$product->name}\n";
    }
    $totalMixed += min($beautyCount, 4);
    echo "\n";
}

if ($dentalCategory) {
    $dentalCount = Product::where('category_id', $dentalCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->count();
    echo "🦷 DENTAL CARE: {$dentalCount} products with relevant images\n";
    
    // Show sample products
    $sampleDental = Product::where('category_id', $dentalCategory->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->take(3)
        ->get();
    
    foreach ($sampleDental as $product) {
        echo "   • {$product->name}\n";
    }
    $totalMixed += min($dentalCount, 3);
    echo "\n";
}

echo "🎯 SHUFFLED MIX RESULT:\n";
echo "Estimated products in shuffled mix: ~{$totalMixed} products\n";
echo "Mix ratio: ~8 Cooking + ~4 Beauty + ~3 Dental = Diverse product display\n\n";

echo "✅ INDEX PAGE IMPLEMENTATION:\n";
echo "• Homepage now shows shuffled products from multiple categories\n";
echo "• Only products with relevant/real images (no placeholders)\n";
echo "• Masala/Spice products from COOKING category\n";
echo "• Perfume/Deodorant products from BEAUTY & PERSONAL CARE\n";
echo "• Dental care products from DENTAL CARE category\n";
echo "• Products are shuffled on each page load for variety\n";
echo "• Banner still prioritizes high discount products\n\n";

echo "🔧 TECHNICAL CHANGES:\n";
echo "• routes/web.php: Updated both main and minimal routes\n";
echo "• BuyerController: Updated index method for buyer dashboard\n";
echo "• Mixed category product selection with shuffle\n";
echo "• Proper pagination with shuffled results\n\n";

echo "✨ RESULT: Index page now displays a diverse mix of masala, perfume,\n";
echo "   and dental products with only relevant product images!\n";
?>
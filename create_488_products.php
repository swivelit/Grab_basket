<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;

echo "=== Creating 488 Products for Seller ===\n";

// Get seller
$seller = Seller::where('email', 'swivel.training@gmail.com')->first();

if (!$seller) {
    echo "❌ Seller not found with email: swivel.training@gmail.com\n";
    exit(1);
}

echo "✅ Seller found: {$seller->name} (ID: {$seller->id})\n";

// Get categories and subcategories
$cookingCategory = Category::where('name', 'COOKING')->first();
$dentalCategory = Category::where('name', 'DENTAL CARE')->first();

if (!$cookingCategory || !$dentalCategory) {
    echo "❌ Required categories not found\n";
    exit(1);
}

$cookingSubcats = Subcategory::where('category_id', $cookingCategory->id)->get();
$dentalSubcats = Subcategory::where('category_id', $dentalCategory->id)->get();

echo "📦 Found categories: COOKING ({$cookingSubcats->count()} subcategories), DENTAL CARE ({$dentalSubcats->count()} subcategories)\n\n";

// Current products count
$currentCount = Product::count();
$targetCount = 488;
$productsToCreate = $targetCount - $currentCount;

echo "Current products: {$currentCount}\n";
echo "Target products: {$targetCount}\n";
echo "Products to create: {$productsToCreate}\n\n";

if ($productsToCreate <= 0) {
    echo "✅ Already have {$currentCount} products. Assigning all to seller...\n";
    
    // Just assign all existing products to the seller
    $updated = Product::whereNotNull('id')->update(['seller_id' => $seller->id]);
    echo "✅ Updated {$updated} products to be assigned to {$seller->name}\n";
    exit(0);
}

// Extended product lists for variety
$additionalCookingProducts = [
    // More Spice Products
    'Garam Masala Powder', 'Chat Masala', 'Sambhar Powder', 'Rasam Powder', 'Biryani Masala',
    'Chicken Masala', 'Mutton Masala', 'Fish Curry Powder', 'Paneer Masala', 'Rajma Masala',
    'Chhole Masala', 'Pav Bhaji Masala', 'Kitchen King Masala', 'Meat Masala', 'Tandoori Masala',
    'Dry Mango Powder (Amchur)', 'Asafoetida (Hing)', 'Fenugreek Seeds (Methi)', 'Carom Seeds (Ajwain)',
    'Mustard Seeds', 'Fennel Seeds (Saunf)', 'Star Anise', 'Mace (Javitri)', 'Nutmeg (Jaifal)',
    'Poppy Seeds (Khus Khus)', 'Sesame Seeds (Til)', 'Nigella Seeds (Kalonji)', 'Celery Seeds',
    // Dal/Lentils
    'Toor Dal', 'Moong Dal', 'Chana Dal', 'Masoor Dal', 'Urad Dal', 'Arhar Dal', 'Black Gram',
    'Green Moong', 'Split Peas', 'Black Lentils', 'Red Kidney Beans', 'Chickpeas (Kabuli Chana)',
    'Black Chickpeas (Kala Chana)', 'Horse Gram', 'Field Beans',
    // Rice Products
    'Basmati Rice', 'Jasmine Rice', 'Brown Rice', 'Red Rice', 'Black Rice', 'Sona Masoori Rice',
    'Ponni Rice', 'Jeera Rice', 'Pulao Rice', 'Biryani Rice', 'Sticky Rice', 'Wild Rice',
    // Flours
    'Wheat Flour (Atta)', 'All Purpose Flour (Maida)', 'Rice Flour', 'Besan (Gram Flour)',
    'Ragi Flour', 'Jowar Flour', 'Bajra Flour', 'Corn Flour', 'Semolina (Suji)', 'Oats Flour',
    // Oils and Vinegars
    'Coconut Oil', 'Mustard Oil', 'Sesame Oil', 'Groundnut Oil', 'Sunflower Oil', 'Olive Oil',
    'Ghee (Clarified Butter)', 'Apple Cider Vinegar', 'White Vinegar', 'Rice Vinegar',
    // Dried Fruits and Nuts
    'Almonds', 'Cashews', 'Walnuts', 'Pistachios', 'Raisins', 'Dates', 'Figs', 'Prunes',
    'Pine Nuts', 'Hazelnuts', 'Brazil Nuts', 'Pecans',
    // Tea and Beverages
    'Masala Chai', 'Green Tea', 'Black Tea', 'Herbal Tea', 'Oolong Tea', 'White Tea',
    'Coffee Beans', 'Instant Coffee', 'Filter Coffee', 'Cocoa Powder',
    // Pickles and Preserves
    'Mango Pickle', 'Lemon Pickle', 'Mixed Vegetable Pickle', 'Garlic Pickle', 'Ginger Pickle',
    'Tomato Sauce', 'Mint Chutney', 'Tamarind Chutney', 'Coconut Chutney', 'Coriander Chutney',
    // Sweets and Desserts
    'Jaggery (Gur)', 'Rock Sugar', 'Palm Sugar', 'Honey', 'Rose Water', 'Kewra Water',
    'Vanilla Extract', 'Cardamom Powder', 'Saffron', 'Silver Leaf (Chandi Vark)',
    // Ready to Cook
    'Idli Mix', 'Dosa Mix', 'Uttapam Mix', 'Dhokla Mix', 'Upma Mix', 'Poha', 'Maggi Noodles',
    'Pasta', 'Vermicelli', 'Bread Crumbs', 'Corn Flakes', 'Oats',
    // International
    'Soy Sauce', 'Oyster Sauce', 'Fish Sauce', 'Teriyaki Sauce', 'Sriracha', 'Tabasco',
    'Italian Herbs', 'Oregano', 'Basil', 'Thyme', 'Rosemary', 'Paprika', 'Cayenne Pepper'
];

$additionalDentalProducts = [
    // Electric Toothbrushes
    'Oral-B Electric Toothbrush Pro 1000', 'Philips Sonicare Electric Toothbrush', 'Colgate Electric Toothbrush',
    'Braun Oral-B Vitality', 'Oral-B Genius X Smart Toothbrush', 'Philips Sonicare DiamondClean',
    // Toothpaste Varieties
    'Sensodyne Pronamel Toothpaste', 'Colgate Optic White', 'Crest 3D White Toothpaste',
    'Himalaya Complete Care Toothpaste', 'Dabur Red Toothpaste', 'Patanjali Dant Kanti',
    'Close-Up Red Hot Toothpaste', 'Pepsodent Expert Protection', 'Aquafresh Triple Protection',
    'Arm & Hammer Baking Soda Toothpaste', 'Tom\'s of Maine Natural Toothpaste',
    // Mouthwash
    'Listerine Cool Mint Mouthwash', 'Colgate Plax Mouthwash', 'Oral-B Pro-Expert Mouthwash',
    'Sensodyne Pronamel Mouthwash', 'TheraBreath Fresh Breath Mouthwash', 'ACT Anticavity Mouthwash',
    'Crest Pro-Health Mouthwash', 'Himalaya HiOra Mouthwash',
    // Dental Floss
    'Oral-B Essential Floss', 'Johnson & Johnson Reach Floss', 'Colgate Total Dental Floss',
    'Sensodyne Gentle Care Floss', 'Plackers Dental Floss Picks', 'Oral-B Glide Pro Health',
    'Water Flosser Cordless', 'Waterpik Water Flosser',
    // Toothbrushes (Manual)
    'Oral-B CrossAction Toothbrush', 'Colgate 360 Toothbrush', 'Sensodyne Gentle Care Toothbrush',
    'Himalaya Gum Care Toothbrush', 'Jordan Classic Toothbrush', 'Aquafresh Flex Toothbrush',
    'Pepsodent Germicheck Toothbrush', 'Close-Up White Attraction Toothbrush',
    // Teeth Whitening
    'Crest Whitestrips', 'Oral-B 3D White Strips', 'Colgate Optic White Pen',
    'Himalaya Whitening Toothpaste', 'Sensodyne Whitening Toothpaste',
    // Dental Care Accessories
    'Tongue Cleaner Copper', 'Tongue Cleaner Stainless Steel', 'Oral-B Tongue Cleaner',
    'Dental Mirror', 'Dental Pick Set', 'Orthodontic Wax', 'Denture Cleaner Tablets',
    'Denture Adhesive', 'Retainer Cleaner', 'Night Guard for Teeth Grinding',
    // Kids Dental Care
    'Colgate Kids Toothpaste Strawberry', 'Oral-B Kids Electric Toothbrush',
    'Aquafresh Kids Toothpaste', 'Himalaya Kids Toothpaste', 'Pepsodent Kids Toothpaste',
    'Kids Soft Toothbrush', 'Disney Character Toothbrush Set',
    // Specialized Care
    'Sensodyne Rapid Relief Gel', 'Orajel Toothache Relief', 'Anbesol Oral Pain Relief',
    'Chlorhexidine Mouthwash', 'Corsodyl Gum Care', 'Paradontax Gum Care Toothpaste',
    'Oral-B Gum Care Toothpaste', 'Biotene Dry Mouth Mouthwash',
    // Professional Products
    'Professional Teeth Whitening Kit', 'Dental Scaler Tool', 'Professional Dental Mirror',
    'Oral Surgery Recovery Kit', 'TMJ Pain Relief Gel', 'Dental Impression Kit'
];

$createdCount = 0;
$withImageCount = 0;
$withoutImageCount = 0;

// Determine how many should have images vs no images
$targetWithImages = (int)($productsToCreate * 0.3); // 30% with images
$targetWithoutImages = $productsToCreate - $targetWithImages; // 70% without images

echo "Plan: {$targetWithImages} products with images, {$targetWithoutImages} products without images\n\n";

// Create additional cooking products
$allCookingProducts = $additionalCookingProducts;
$cookingIndex = 0;

// Create additional dental products  
$allDentalProducts = $additionalDentalProducts;
$dentalIndex = 0;

while ($createdCount < $productsToCreate) {
    $shouldHaveImage = $withImageCount < $targetWithImages;
    
    // Alternate between cooking and dental products
    if ($createdCount % 2 == 0 && $cookingIndex < count($allCookingProducts)) {
        // Create cooking product
        $productName = $allCookingProducts[$cookingIndex];
        $category = $cookingCategory;
        $subcategory = $cookingSubcats->random();
        $cookingIndex++;
    } elseif ($dentalIndex < count($allDentalProducts)) {
        // Create dental product
        $productName = $allDentalProducts[$dentalIndex];
        $category = $dentalCategory;
        $subcategory = $dentalSubcats->random();
        $dentalIndex++;
    } else {
        // Generate generic product if we run out of predefined names
        $isGenericCooking = $createdCount % 2 == 0;
        if ($isGenericCooking) {
            $productName = "Spice Product " . ($cookingIndex + 1);
            $category = $cookingCategory;
            $subcategory = $cookingSubcats->random();
            $cookingIndex++;
        } else {
            $productName = "Dental Product " . ($dentalIndex + 1);
            $category = $dentalCategory;
            $subcategory = $dentalSubcats->random();
            $dentalIndex++;
        }
    }
    
    $product = Product::create([
        'name' => $productName,
        'unique_id' => 'PROD-' . time() . '-' . $createdCount,
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'seller_id' => $seller->id,
        'image' => $shouldHaveImage ? 'https://via.placeholder.com/200?text=' . urlencode($productName) : null,
        'description' => "High quality {$productName} available at best price. Premium product for your needs.",
        'price' => rand(20, 500),
        'discount' => rand(0, 30),
        'delivery_charge' => rand(0, 1) ? 0 : rand(20, 100),
        'gift_option' => rand(0, 1),
        'stock' => rand(10, 100),
    ]);
    
    $createdCount++;
    if ($shouldHaveImage) {
        $withImageCount++;
    } else {
        $withoutImageCount++;
    }
    
    if ($createdCount % 50 == 0) {
        echo "Created {$createdCount}/{$productsToCreate} products...\n";
    }
}

echo "\n✅ Successfully created {$createdCount} new products!\n";
echo "📊 Final Statistics:\n";
echo "Products with images: {$withImageCount}\n";
echo "Products without images: {$withoutImageCount}\n";
echo "All products assigned to: {$seller->name} ({$seller->email})\n\n";

// Final verification
$finalCount = Product::count();
$sellerProductCount = Product::where('seller_id', $seller->id)->count();

echo "🎯 Database Final Status:\n";
echo "Total products in database: {$finalCount}\n";
echo "Products assigned to {$seller->name}: {$sellerProductCount}\n";

if ($finalCount >= 488) {
    echo "✅ Successfully reached target of 488+ products!\n";
} else {
    echo "⚠️  Current total: {$finalCount} (target was 488)\n";
}
?>
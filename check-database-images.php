<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Checking Database Image Storage ===\n\n";

// Check products with database image storage
$dbImageProducts = Product::whereNotNull('image_data')->get();
echo "📊 Products with database image storage: " . $dbImageProducts->count() . "\n\n";

if ($dbImageProducts->count() > 0) {
    echo "✅ Products using database storage (will display correctly):\n";
    foreach ($dbImageProducts->take(5) as $product) {
        echo "- Product {$product->id}: {$product->name}\n";
        echo "  Image data size: " . strlen($product->image_data) . " chars\n";
        echo "  MIME type: {$product->image_mime_type}\n";
    }
    if ($dbImageProducts->count() > 5) {
        echo "  ... and " . ($dbImageProducts->count() - 5) . " more\n";
    }
} else {
    echo "❌ No products found with database image storage\n";
}

// Check products with products/ paths that might need conversion
$missingProducts = Product::where('image', 'LIKE', 'products/%')
    ->whereNull('image_data')
    ->get();

echo "\n📊 Products with products/ paths but NO database backup: " . $missingProducts->count() . "\n";

if ($missingProducts->count() > 0) {
    echo "\n🔧 SOLUTION OPTIONS:\n";
    echo "1. Convert these products to use database image storage\n";
    echo "2. Set placeholder images for missing ones\n";
    echo "3. Ask users to re-upload their images\n\n";
    
    echo "Would you like me to:\n";
    echo "A) Convert all to use placeholder images (quick fix)\n";
    echo "B) Set them to use database storage if possible\n";
    echo "C) Show detailed report for manual handling\n";
}

echo "\nDone!\n";
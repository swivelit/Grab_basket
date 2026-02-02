<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🔧 FIXING MISSING PRODUCTIMAGE RECORDS\n";
echo "======================================\n\n";

// Find products that have image field but no ProductImage records
$productsWithImageButNoRecord = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->whereDoesntHave('productImages')
    ->get();

echo "📋 Products with image field but no ProductImage records: " . $productsWithImageButNoRecord->count() . "\n\n";

$fixed = 0;
$errors = 0;

foreach($productsWithImageButNoRecord as $product) {
    try {
        $imagePath = $product->image;
        $imageFullPath = storage_path('app/public/' . $imagePath);
        
        // Check if image file exists
        if (file_exists($imageFullPath)) {
            $fileSize = filesize($imageFullPath);
            
            // Create ProductImage record
            \App\Models\ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'original_name' => basename($imagePath),
                'mime_type' => null,
                'file_size' => $fileSize,
                'sort_order' => 1,
                'is_primary' => true,
            ]);
            
            $fixed++;
            echo "✅ Fixed: {$product->name}\n";
            
        } else {
            echo "⚠️  Missing file for: {$product->name} (Image: {$imagePath})\n";
        }
        
    } catch (\Exception $e) {
        $errors++;
        echo "❌ Error fixing {$product->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "ProductImage records created: {$fixed}\n";
echo "Errors: {$errors}\n";

// Verify the fix
$stillMissing = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->whereDoesntHave('productImages')
    ->count();

echo "Products still missing ProductImage records: {$stillMissing}\n";

if ($stillMissing === 0) {
    echo "\n🎉 SUCCESS: All products with images now have ProductImage records!\n";
}
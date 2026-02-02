<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "\n=== FIX PRODUCT #1556 IMAGE PATH ===\n\n";

$product = Product::find(1556);

if ($product) {
    echo "Product: {$product->name}\n";
    echo "Current legacy image: {$product->image}\n";
    echo "ProductImages count: " . $product->productImages->count() . "\n\n";
    
    // The correct file in R2
    $correctPath = 'products/seller-2/srm341-1760335961.jpg';
    
    echo "Correct file in R2: $correctPath\n";
    echo "R2 exists: " . (Storage::disk('r2')->exists($correctPath) ? 'YES ✅' : 'NO ❌') . "\n\n";
    
    if (Storage::disk('r2')->exists($correctPath)) {
        // Sync to public disk
        echo "Syncing to public disk...\n";
        $content = Storage::disk('r2')->get($correctPath);
        Storage::disk('public')->put($correctPath, $content);
        echo "Public disk: " . (Storage::disk('public')->exists($correctPath) ? 'SYNCED ✅' : 'FAILED ❌') . "\n\n";
        
        // Update product
        echo "Updating product legacy image field...\n";
        $product->update(['image' => $correctPath]);
        echo "Updated ✅\n\n";
        
        // Update or create ProductImage
        $productImage = $product->productImages->first();
        if ($productImage) {
            echo "Updating ProductImage record...\n";
            $productImage->update([
                'image_path' => $correctPath,
                'original_name' => 'SRM341.jpg',
                'file_size' => Storage::disk('r2')->size($correctPath)
            ]);
            echo "Updated ✅\n";
        } else {
            echo "Creating ProductImage record...\n";
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $correctPath,
                'original_name' => 'SRM341.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => Storage::disk('r2')->size($correctPath),
                'sort_order' => 1,
                'is_primary' => true
            ]);
            echo "Created ✅\n";
        }
        
        echo "\n✅ Product #1556 fixed!\n";
    } else {
        echo "❌ Correct file not found in R2\n";
    }
} else {
    echo "❌ Product #1556 not found\n";
}

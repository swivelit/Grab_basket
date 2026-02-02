<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "\n=== SYNC MISSING FILES FROM R2 TO PUBLIC ===\n\n";

// Find all products where files exist in R2 but not in public
$products = Product::with('productImages')->get();
$synced = 0;
$missing = 0;

foreach ($products as $product) {
    foreach ($product->productImages as $img) {
        $existsR2 = Storage::disk('r2')->exists($img->image_path);
        $existsPublic = Storage::disk('public')->exists($img->image_path);
        
        if ($existsR2 && !$existsPublic) {
            echo "Syncing: {$img->image_path}\n";
            
            try {
                // Get file from R2
                $content = Storage::disk('r2')->get($img->image_path);
                
                // Save to public disk
                Storage::disk('public')->put($img->image_path, $content);
                
                // Verify
                if (Storage::disk('public')->exists($img->image_path)) {
                    echo "   ✅ SUCCESS\n";
                    $synced++;
                } else {
                    echo "   ❌ FAILED (file not found after save)\n";
                    $missing++;
                }
            } catch (\Exception $e) {
                echo "   ❌ ERROR: " . $e->getMessage() . "\n";
                $missing++;
            }
        } elseif (!$existsR2 && !$existsPublic) {
            echo "Missing everywhere: {$img->image_path} ❌\n";
            $missing++;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files synced: $synced ✅\n";
echo "Files missing: $missing ❌\n";
echo "\n=== END SYNC ===\n";

<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "\n=== CHECKING PRODUCT #1556 (FOR EDIT) ===\n\n";

$product = Product::with('productImages')->find(1556);

if ($product) {
    echo "Product: {$product->name}\n";
    echo "ID: {$product->id}\n";
    echo "Created: {$product->created_at}\n\n";
    
    echo "Legacy Image Field:\n";
    echo "  Path: " . ($product->image ?: 'NULL') . "\n";
    if ($product->image) {
        echo "  R2: " . (Storage::disk('r2')->exists($product->image) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        echo "  Public: " . (Storage::disk('public')->exists($product->image) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        
        // Check actual file path
        $publicFullPath = storage_path('app/public/' . $product->image);
        echo "  Full path: $publicFullPath\n";
        echo "  File exists: " . (file_exists($publicFullPath) ? 'YES ✅' : 'NO ❌') . "\n";
        if (file_exists($publicFullPath)) {
            echo "  File size: " . filesize($publicFullPath) . " bytes\n";
        }
    }
    
    echo "\nProductImages:\n";
    echo "  Count: " . $product->productImages->count() . "\n";
    foreach ($product->productImages as $img) {
        echo "\n  Image #{$img->id}:\n";
        echo "    Path: {$img->image_path}\n";
        echo "    Original: {$img->original_name}\n";
        echo "    Primary: " . ($img->is_primary ? 'YES' : 'NO') . "\n";
        echo "    R2: " . (Storage::disk('r2')->exists($img->image_path) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        echo "    Public: " . (Storage::disk('public')->exists($img->image_path) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        
        $publicFullPath = storage_path('app/public/' . $img->image_path);
        echo "    Full path: $publicFullPath\n";
        echo "    File exists: " . (file_exists($publicFullPath) ? 'YES ✅' : 'NO ❌') . "\n";
        
        // Get the image URL from model accessor
        echo "    Model URL: " . ($img->image_url ?: 'NULL') . "\n";
    }
    
    echo "\nImage URL from Product Model:\n";
    echo "  URL: " . ($product->image_url ?: 'NULL') . "\n";
    
    echo "\nPublic Storage Symlink:\n";
    $symlinkPath = public_path('storage');
    echo "  Path: $symlinkPath\n";
    echo "  Exists: " . (file_exists($symlinkPath) ? 'YES' : 'NO') . "\n";
    echo "  Is Link: " . (is_link($symlinkPath) ? 'YES' : 'NO') . "\n";
    echo "  Real Path: " . realpath($symlinkPath) . "\n";
    
    // Check if we can access via symlink
    if ($product->image) {
        $symlinkFilePath = public_path('storage/' . $product->image);
        echo "\nAccess via symlink:\n";
        echo "  Path: $symlinkFilePath\n";
        echo "  Exists: " . (file_exists($symlinkFilePath) ? 'YES ✅' : 'NO ❌') . "\n";
    }
} else {
    echo "Product #1556 not found!\n";
}

echo "\n=== END CHECK ===\n";

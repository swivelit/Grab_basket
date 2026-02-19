<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "\n=== CHECKING IMAGE AVAILABILITY ===\n\n";

// Get last 5 products
$products = Product::with('productImages')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($products as $p) {
    echo "Product #{$p->id}: {$p->name}\n";
    echo "   Created: {$p->created_at}\n";
    echo "   Legacy Image: " . ($p->image ?: 'NONE') . "\n";
    
    if ($p->image) {
        $r2Exists = Storage::disk('r2')->exists($p->image);
        $publicExists = Storage::disk('public')->exists($p->image);
        echo "   R2: " . ($r2Exists ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        echo "   Public: " . ($publicExists ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        
        // Check URL generation
        $imageUrl = $p->image_url;
        echo "   URL: " . ($imageUrl ?: 'NULL') . "\n";
    }
    
    echo "   ProductImages: " . $p->productImages->count() . "\n";
    
    if ($p->productImages->count() > 0) {
        foreach ($p->productImages as $img) {
            $r2Exists = Storage::disk('r2')->exists($img->image_path);
            $publicExists = Storage::disk('public')->exists($img->image_path);
            echo "      Path: {$img->image_path}\n";
            echo "      R2: " . ($r2Exists ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
            echo "      Public: " . ($publicExists ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
            echo "      URL: " . ($img->image_url ?: 'NULL') . "\n";
        }
    }
    echo "\n";
}

echo "=== CHECKING SERVE-IMAGE ROUTE ===\n\n";

// Check if we can manually construct URLs
$testPath = 'products/seller-2/srm341-1760335961.jpg';
echo "Test path: $testPath\n";
echo "R2 exists: " . (Storage::disk('r2')->exists($testPath) ? 'YES ✅' : 'NO ❌') . "\n";
echo "Public exists: " . (Storage::disk('public')->exists($testPath) ? 'YES ✅' : 'NO ❌') . "\n";
echo "Serve-image URL: " . url('serve-image/' . $testPath) . "\n";
$r2Url = config('filesystems.disks.r2.url') . '/' . $testPath;
echo "R2 URL: " . $r2Url . "\n";

echo "\n=== END CHECK ===\n";

<?php

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "Syncing R2 images to local public disk\n";
echo "========================================\n\n";

// Get all images that are in R2 but not in public disk
$imagesToSync = ProductImage::where('image_path', 'LIKE', 'products/seller-%')
    ->get();

$synced = 0;
$alreadySynced = 0;
$failed = 0;

foreach ($imagesToSync as $img) {
    $publicExists = Storage::disk('public')->exists($img->image_path);
    $r2Exists = Storage::disk('r2')->exists($img->image_path);
    
    if ($r2Exists && !$publicExists) {
        echo "Syncing: {$img->image_path}...";
        
        try {
            // Get content from R2
            $content = Storage::disk('r2')->get($img->image_path);
            
            // Store in public disk
            Storage::disk('public')->put($img->image_path, $content);
            
            echo " ✅ DONE\n";
            $synced++;
        } catch (\Throwable $e) {
            echo " ❌ FAILED: {$e->getMessage()}\n";
            $failed++;
        }
    } elseif ($publicExists && $r2Exists) {
        $alreadySynced++;
    } elseif (!$r2Exists) {
        echo "⚠️  Missing in R2: {$img->image_path}\n";
        $failed++;
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Synced: {$synced}\n";
echo "  Already synced: {$alreadySynced}\n";
echo "  Failed: {$failed}\n";
echo "========================================\n";

if ($synced > 0) {
    echo "\n✅ Images synced successfully!\n";
    echo "Both R2 and public disk now have the images.\n";
}

<?php

/**
 * Update Existing Product Images in Dashboard
 * This script helps migrate images to the new naming convention (without timestamps)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

echo "====================================\n";
echo "Update Existing Product Images\n";
echo "====================================\n\n";

$localDisk = Storage::disk('public');
$r2Disk = Storage::disk('r2');

// Get all products with images
$products = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->orderBy('id')
    ->get();

echo "Found " . $products->count() . " products with images\n\n";

$updated = 0;
$skipped = 0;
$failed = 0;
$alreadyClean = 0;

foreach ($products as $product) {
    echo "Product #{$product->id}: {$product->name}\n";
    echo "  Current image: {$product->image}\n";
    
    // Skip if it's an external URL
    if (str_starts_with($product->image, 'http://') || str_starts_with($product->image, 'https://')) {
        echo "  ⏭️  External URL, skipping...\n\n";
        $skipped++;
        continue;
    }
    
    // Skip if it's a static image
    if (str_starts_with($product->image, 'images/')) {
        echo "  ⏭️  Static image, skipping...\n\n";
        $skipped++;
        continue;
    }
    
    $imagePath = ltrim($product->image, '/');
    
    // Check if image has timestamp pattern (ends with -[digits].ext)
    if (!preg_match('/-\d+\.([a-z]+)$/i', $imagePath)) {
        echo "  ✅ Already has clean filename (no timestamp)\n\n";
        $alreadyClean++;
        continue;
    }
    
    // Extract parts
    $pathInfo = pathinfo($imagePath);
    $dirname = $pathInfo['dirname'];
    $filename = $pathInfo['filename'];
    $extension = $pathInfo['extension'];
    
    // Remove timestamp from filename (last -NNNN part)
    $cleanFilename = preg_replace('/-\d+$/', '', $filename);
    $newPath = $dirname . '/' . $cleanFilename . '.' . $extension;
    
    echo "  📝 Proposed new path: {$newPath}\n";
    
    try {
        // Check if old file exists
        $oldExists = false;
        $oldDisk = null;
        
        if ($r2Disk->exists($imagePath)) {
            $oldExists = true;
            $oldDisk = 'r2';
            echo "  📁 Found on R2\n";
        } elseif ($localDisk->exists($imagePath)) {
            $oldExists = true;
            $oldDisk = 'public';
            echo "  📁 Found on local storage\n";
        } else {
            echo "  ❌ Old file not found on any disk, skipping...\n\n";
            $failed++;
            continue;
        }
        
        // Check if new file already exists
        if ($r2Disk->exists($newPath)) {
            echo "  ⚠️  New filename already exists on R2, using existing\n";
            // Just update database
            $product->image = $newPath;
            $product->save();
            echo "  ✅ Database updated to point to existing file\n\n";
            $updated++;
            continue;
        }
        
        // Copy to new filename
        if ($oldDisk === 'r2') {
            // Copy within R2
            $content = $r2Disk->get($imagePath);
            $r2Disk->put($newPath, $content, 'public');
            echo "  📤 Copied on R2: {$imagePath} → {$newPath}\n";
        } else {
            // Copy from local to R2
            $content = $localDisk->get($imagePath);
            $r2Disk->put($newPath, $content, 'public');
            echo "  📤 Uploaded to R2: {$newPath}\n";
        }
        
        // Verify new file exists
        if ($r2Disk->exists($newPath)) {
            // Update database
            $product->image = $newPath;
            $product->save();
            
            echo "  ✅ Successfully updated!\n";
            echo "     Old: {$imagePath}\n";
            echo "     New: {$newPath}\n\n";
            $updated++;
            
            // Optional: Delete old file (commented out for safety)
            // if ($oldDisk === 'r2' && $imagePath !== $newPath) {
            //     $r2Disk->delete($imagePath);
            //     echo "  🗑️  Deleted old file from R2\n";
            // }
        } else {
            echo "  ❌ Failed to verify new file\n\n";
            $failed++;
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo "\n====================================\n";
echo "Update Complete!\n";
echo "====================================\n";
echo "✅ Updated: {$updated}\n";
echo "✨ Already clean: {$alreadyClean}\n";
echo "⏭️  Skipped: {$skipped}\n";
echo "❌ Failed: {$failed}\n";
echo "📁 Total products: " . $products->count() . "\n\n";

if ($updated > 0) {
    echo "🎉 Successfully updated {$updated} product image(s)!\n";
    echo "Images now have clean filenames without timestamps.\n\n";
    echo "📋 Next steps:\n";
    echo "1. Clear Laravel cache: php artisan cache:clear\n";
    echo "2. Test images on seller dashboard\n";
    echo "3. Verify images display correctly\n\n";
}

if ($failed > 0) {
    echo "⚠️  Some updates failed. Check the errors above.\n";
}

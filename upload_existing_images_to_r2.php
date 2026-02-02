<?php

/**
 * Upload existing product images to R2
 * This will ensure all images (old and new) are served from R2
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "====================================\n";
echo "Upload Existing Images to R2\n";
echo "====================================\n\n";

// Get all files from local storage
$localDisk = Storage::disk('public');
$r2Disk = Storage::disk('r2');

// Get all product images
$localFiles = $localDisk->allFiles('products');

echo "Found " . count($localFiles) . " images in local storage\n\n";

$uploaded = 0;
$skipped = 0;
$failed = 0;

foreach ($localFiles as $file) {
    echo "Processing: {$file}\n";
    
    // Check if already exists on R2
    if ($r2Disk->exists($file)) {
        echo "  ⏭️  Already exists on R2, skipping...\n";
        $skipped++;
        continue;
    }
    
    try {
        // Get file content from local
        $content = $localDisk->get($file);
        
        // Upload to R2
        $r2Disk->put($file, $content, 'public');
        
        // Verify upload
        if ($r2Disk->exists($file)) {
            $size = strlen($content);
            echo "  ✅ Uploaded successfully ({$size} bytes)\n";
            $uploaded++;
        } else {
            echo "  ❌ Upload failed - file not found after upload\n";
            $failed++;
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $failed++;
        Log::error('R2 upload failed', [
            'file' => $file,
            'error' => $e->getMessage()
        ]);
    }
    
    // Small delay to avoid rate limiting
    usleep(100000); // 0.1 second
}

echo "\n====================================\n";
echo "Upload Complete!\n";
echo "====================================\n";
echo "✅ Uploaded: {$uploaded}\n";
echo "⏭️  Skipped (already exists): {$skipped}\n";
echo "❌ Failed: {$failed}\n";
echo "📁 Total files: " . count($localFiles) . "\n";

if ($uploaded > 0) {
    echo "\n🎉 All new images uploaded to R2!\n";
    echo "Images are now accessible at:\n";
    echo env('AWS_URL') . "/products/...\n";
}

if ($failed > 0) {
    echo "\n⚠️  Some uploads failed. Check storage/logs/laravel.log for details.\n";
}

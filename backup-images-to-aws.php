<?php
/**
 * Backup Product Images to Laravel Cloud Storage (AWS)
 * 
 * This script uploads all product images from storage/app/public
 * to Laravel Cloud managed storage as a backup.
 * 
 * Primary: GitHub CDN (fast, version-controlled)
 * Backup: Laravel Cloud Storage (reliable, persistent)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

echo "🔄 Backing up images to Laravel Cloud Storage\n";
echo "===============================================\n\n";

// Check if AWS is configured
if (empty(config('filesystems.disks.r2.bucket'))) {
    echo "❌ AWS/R2 storage not configured!\n";
    exit(1);
}

echo "📦 AWS Configuration:\n";
echo "   Bucket: " . config('filesystems.disks.r2.bucket') . "\n";
echo "   Endpoint: " . config('filesystems.disks.r2.endpoint') . "\n";
echo "   URL: " . config('filesystems.disks.r2.url') . "\n\n";

// Get all images from local storage
$localPath = storage_path('app/public');
if (!File::exists($localPath)) {
    echo "❌ Local storage path not found: $localPath\n";
    exit(1);
}

$files = File::allFiles($localPath);
$totalFiles = count($files);
$uploaded = 0;
$skipped = 0;
$errors = 0;

echo "📸 Found $totalFiles files to backup\n\n";

foreach ($files as $file) {
    $relativePath = str_replace($localPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relativePath = str_replace('\\', '/', $relativePath);
    
    echo "Processing: $relativePath ... ";
    
    try {
        // Check if file already exists in cloud storage
        if (Storage::disk('r2')->exists($relativePath)) {
            echo "⏭️  Already exists (skipped)\n";
            $skipped++;
            continue;
        }
        
        // Upload to cloud storage
        $contents = File::get($file->getPathname());
        Storage::disk('r2')->put($relativePath, $contents, 'public');
        
        echo "✅ Uploaded\n";
        $uploaded++;
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "===============================================\n";
echo "📊 Backup Summary:\n";
echo "   Total files: $totalFiles\n";
echo "   Uploaded: $uploaded\n";
echo "   Skipped (already exists): $skipped\n";
echo "   Errors: $errors\n";
echo "\n";

if ($errors === 0) {
    echo "✅ Backup completed successfully!\n";
    echo "\n";
    echo "🌐 Primary CDN: GitHub\n";
    echo "   https://raw.githubusercontent.com/grabbaskets-hash/grabbaskets/main/storage/app/public/\n";
    echo "\n";
    echo "💾 Backup: Laravel Cloud Storage\n";
    echo "   " . config('filesystems.disks.r2.url') . "\n";
} else {
    echo "⚠️  Backup completed with errors. Check the output above.\n";
}

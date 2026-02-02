<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

echo "=== Cloud Image Upload Script ===\n\n";

$imagesDir = public_path('images');
$uploadedCount = 0;
$errorCount = 0;
$skippedCount = 0;

if (!is_dir($imagesDir)) {
    echo "❌ Images directory not found: {$imagesDir}\n";
    exit(1);
}

echo "📁 Scanning images directory: {$imagesDir}\n";

$files = File::allFiles($imagesDir);
echo "📊 Found " . count($files) . " files to process\n\n";

foreach ($files as $file) {
    $relativePath = str_replace($imagesDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relativePath = str_replace('\\', '/', $relativePath); // Normalize for storage
    $cloudPath = 'images/' . $relativePath;
    
    echo "Processing: {$relativePath}";
    
    try {
        // Check if file already exists in cloud storage
        if (Storage::exists($cloudPath)) {
            echo " → ⚠️  Already exists, skipping\n";
            $skippedCount++;
            continue;
        }
        
        // Read file content
        $fileContent = file_get_contents($file->getPathname());
        
        // Upload to cloud storage
        $success = Storage::put($cloudPath, $fileContent, 'public');
        
        if ($success) {
            echo " → ✅ Uploaded successfully\n";
            $uploadedCount++;
        } else {
            echo " → ❌ Upload failed\n";
            $errorCount++;
        }
        
    } catch (\Exception $e) {
        echo " → ❌ Error: " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n=== Upload Summary ===\n";
echo "✅ Uploaded: {$uploadedCount} files\n";
echo "⚠️  Skipped: {$skippedCount} files\n";
echo "❌ Errors: {$errorCount} files\n";
echo "📊 Total processed: " . ($uploadedCount + $skippedCount + $errorCount) . " files\n";

if ($uploadedCount > 0) {
    echo "\n🎉 Images have been uploaded to cloud storage!\n";
    echo "📱 The images should now display on https://grabbaskets.laravel.cloud/\n";
} else {
    echo "\n⚠️  No new images were uploaded.\n";
}

echo "\nDone!\n";
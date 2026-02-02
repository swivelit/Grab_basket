<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Finding New Images Not Yet in GitHub\n";
echo "===============================================\n\n";

// Get all local images
$localPath = storage_path('app/public/products');
$localFiles = [];

if (is_dir($localPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
            $relativePath = str_replace($localPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize to forward slashes
            $localFiles[] = $relativePath;
        }
    }
}

echo "📊 Found " . count($localFiles) . " images locally\n\n";

// Check which ones are not in git
echo "🔍 Checking which images are not yet in GitHub...\n\n";

$newImages = [];
foreach ($localFiles as $file) {
    // Check if file is tracked in git
    $gitCheck = shell_exec("git ls-files storage/app/public/products/" . escapeshellarg($file) . " 2>&1");
    if (empty(trim($gitCheck))) {
        $newImages[] = $file;
        echo "  ❌ NOT in GitHub: {$file}\n";
    }
}

echo "\n===============================================\n";
echo "📈 Summary:\n";
echo "   Total local images: " . count($localFiles) . "\n";
echo "   Already in GitHub: " . (count($localFiles) - count($newImages)) . "\n";
echo "   NEW (not in GitHub): " . count($newImages) . "\n\n";

if (count($newImages) > 0) {
    echo "💡 To add these new images to GitHub:\n\n";
    echo "   1. Run this command:\n";
    echo "      git add storage/app/public/products/\n\n";
    echo "   2. Then commit:\n";
    echo "      git commit -m \"Add " . count($newImages) . " new product images\"\n\n";
    echo "   3. Finally push:\n";
    echo "      git push origin main\n\n";
    
    // Show first 10 new images
    echo "📋 First 10 new images:\n";
    foreach (array_slice($newImages, 0, 10) as $img) {
        echo "   - {$img}\n";
    }
    
    if (count($newImages) > 10) {
        echo "   ... and " . (count($newImages) - 10) . " more\n";
    }
} else {
    echo "✅ All local images are already in GitHub!\n";
}

echo "\n";

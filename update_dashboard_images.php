<?php

/**
 * Force Refresh Product Images on Dashboard
 * Re-uploads all product images to R2 with clean filenames
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "====================================\n";
echo "Force Refresh Dashboard Images\n";
echo "====================================\n\n";

$action = $argv[1] ?? 'check';

if (!in_array($action, ['check', 'update'])) {
    echo "Usage: php update_dashboard_images.php [check|update]\n\n";
    echo "Commands:\n";
    echo "  check  - Show what will be updated (dry run)\n";
    echo "  update - Actually update the images\n\n";
    exit(1);
}

$localDisk = Storage::disk('public');
$r2Disk = Storage::disk('r2');

// Get all products
$products = Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->orderBy('id')
    ->get();

echo "Found " . $products->count() . " products with images\n";
echo "Mode: " . strtoupper($action) . "\n\n";

if ($action === 'check') {
    echo "⚠️  DRY RUN MODE - No changes will be made\n\n";
}

$needsUpdate = 0;
$alreadyGood = 0;
$external = 0;
$missing = 0;

foreach ($products as $product) {
    $imagePath = ltrim($product->image, '/');
    
    // Skip external URLs
    if (str_starts_with($product->image, 'http://') || str_starts_with($product->image, 'https://')) {
        $external++;
        continue;
    }
    
    // Skip static images
    if (str_starts_with($product->image, 'images/')) {
        $alreadyGood++;
        continue;
    }
    
    // Check if on R2
    $onR2 = $r2Disk->exists($imagePath);
    $onLocal = $localDisk->exists($imagePath);
    
    if (!$onR2 && !$onLocal) {
        echo "❌ #{$product->id}: {$product->name}\n";
        echo "   Missing: {$imagePath}\n\n";
        $missing++;
        continue;
    }
    
    if ($onR2) {
        echo "✅ #{$product->id}: {$product->name}\n";
        echo "   Already on R2: {$imagePath}\n\n";
        $alreadyGood++;
    } else {
        echo "📤 #{$product->id}: {$product->name}\n";
        echo "   Needs upload to R2: {$imagePath}\n";
        
        if ($action === 'update') {
            try {
                $content = $localDisk->get($imagePath);
                $r2Disk->put($imagePath, $content, 'public');
                
                if ($r2Disk->exists($imagePath)) {
                    echo "   ✅ Uploaded successfully!\n\n";
                    $needsUpdate++;
                } else {
                    echo "   ❌ Upload failed!\n\n";
                    $missing++;
                }
            } catch (\Exception $e) {
                echo "   ❌ Error: " . $e->getMessage() . "\n\n";
                $missing++;
            }
        } else {
            echo "   📋 Will be uploaded when you run with 'update'\n\n";
            $needsUpdate++;
        }
    }
}

echo "\n====================================\n";
echo "Summary\n";
echo "====================================\n";
echo "✅ Already on R2: {$alreadyGood}\n";
echo "🌐 External URLs: {$external}\n";
echo "📤 Need upload: {$needsUpdate}\n";
echo "❌ Missing files: {$missing}\n";
echo "📁 Total: " . $products->count() . "\n\n";

if ($action === 'check' && $needsUpdate > 0) {
    echo "💡 To actually update the images, run:\n";
    echo "   php update_dashboard_images.php update\n\n";
} elseif ($action === 'update' && $needsUpdate > 0) {
    echo "🎉 Successfully uploaded {$needsUpdate} images to R2!\n\n";
    echo "📋 Next steps:\n";
    echo "1. Clear cache: php artisan config:clear\n";
    echo "2. Check seller dashboard\n";
    echo "3. Verify all images display correctly\n\n";
}

<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Product;

echo "=== Finding Missing Product Images ===\n\n";

// Get all products with products/ path images
$products = Product::where('image', 'LIKE', 'products/%')->get();

echo "📊 Found " . $products->count() . " products with products/ path images\n\n";

$missingCount = 0;
$foundCount = 0;

foreach ($products as $product) {
    echo "Product {$product->id}: {$product->name}\n";
    echo "Image path: {$product->image}\n";
    
    $exists = Storage::exists($product->image);
    if ($exists) {
        echo "✅ EXISTS in cloud storage\n";
        $foundCount++;
    } else {
        echo "❌ MISSING from cloud storage\n";
        $missingCount++;
        
        // Check if file exists in storage/app/public
        $localPath = storage_path('app/public/' . $product->image);
        if (file_exists($localPath)) {
            echo "📁 Found locally: {$localPath}\n";
            
            try {
                // Upload to cloud storage
                $fileContent = file_get_contents($localPath);
                $uploaded = Storage::put($product->image, $fileContent, 'public');
                
                if ($uploaded) {
                    echo "✅ UPLOADED to cloud storage\n";
                } else {
                    echo "❌ FAILED to upload\n";
                }
            } catch (\Exception $e) {
                echo "❌ Upload error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "❌ Not found locally either\n";
        }
    }
    
    echo "---\n\n";
}

echo "📈 Summary:\n";
echo "✅ Found in cloud: {$foundCount}\n";
echo "❌ Missing from cloud: {$missingCount}\n";

echo "\nDone!\n";
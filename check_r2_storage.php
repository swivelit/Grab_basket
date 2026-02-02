<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Product;

echo "🔍 Checking R2 Storage for Images\n";
echo str_repeat("=", 70) . "\n\n";

// Get the product
$product = Product::where('unique_id', '996')->first();

if ($product) {
    $imagePath = $product->image;
    
    echo "Product: {$product->product_name}\n";
    echo "Image Path (DB): {$imagePath}\n\n";
    
    // Check if file exists in R2
    echo "Checking R2 storage...\n";
    try {
        $exists = Storage::disk('r2')->exists($imagePath);
        
        if ($exists) {
            echo "✅ File EXISTS in R2: {$imagePath}\n";
            
            // Get file size
            $size = Storage::disk('r2')->size($imagePath);
            echo "   File size: " . number_format($size / 1024, 2) . " KB\n";
            
            // Try to get the file
            echo "\n   Attempting to retrieve file from R2...\n";
            $contents = Storage::disk('r2')->get($imagePath);
            echo "   ✅ Successfully retrieved file (" . strlen($contents) . " bytes)\n";
            
        } else {
            echo "❌ File NOT FOUND in R2: {$imagePath}\n";
            
            // Try alternative paths
            echo "\n   Trying alternative paths...\n";
            $alternatives = [
                'seller-2/srm340-1760342455.jpg',
                'products/seller-2/srm340-1760342455.jpg',
                '/products/seller-2/srm340-1760342455.jpg'
            ];
            
            foreach ($alternatives as $alt) {
                if (Storage::disk('r2')->exists($alt)) {
                    echo "   ✅ FOUND at: {$alt}\n";
                }
            }
        }
        
    } catch (\Exception $e) {
        echo "❌ Error checking R2: " . $e->getMessage() . "\n";
    }
    
    // List some files in R2 to see what's there
    echo "\n" . str_repeat("-", 70) . "\n";
    echo "Sample of files in R2 products/seller-2/ directory:\n\n";
    
    try {
        $files = Storage::disk('r2')->files('products/seller-2');
        if (count($files) > 0) {
            echo "Found " . count($files) . " files. Showing first 10:\n";
            foreach (array_slice($files, 0, 10) as $file) {
                echo "  - {$file}\n";
            }
        } else {
            echo "  (No files found in products/seller-2/)\n";
        }
    } catch (\Exception $e) {
        echo "  Error listing files: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Product with unique_id 996 not found\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

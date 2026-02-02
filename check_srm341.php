<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Product with srm341 Image\n";
echo "===============================================\n\n";

// Find products with srm341 in the image path
$products = \App\Models\Product::where('image', 'LIKE', '%srm341%')->get();

foreach ($products as $product) {
    echo "Product ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Image field: {$product->image}\n";
    
    // Check if file exists
    $imagePath = ltrim($product->image, '/');
    $fullPath = storage_path('app/public/' . $imagePath);
    
    echo "Full path: {$fullPath}\n";
    echo "File exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
    
    if (!file_exists($fullPath)) {
        // Check for similar files
        $directory = dirname($fullPath);
        $filename = basename($fullPath);
        $pattern = preg_replace('/-\d+\.jpg$/', '-*.jpg', $filename);
        
        echo "\nLooking for similar files in: {$directory}\n";
        echo "Pattern: {$pattern}\n";
        
        if (is_dir($directory)) {
            $files = glob($directory . '/' . $pattern);
            if (!empty($files)) {
                echo "\n📁 Found similar files:\n";
                foreach ($files as $file) {
                    $relPath = str_replace(storage_path('app/public/'), '', $file);
                    echo "   - {$relPath}\n";
                    echo "     Size: " . filesize($file) . " bytes\n";
                    echo "     Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
                }
            }
        }
    }
    
    echo "\n";
}

if ($products->isEmpty()) {
    echo "❌ No products found with 'srm341' in image path\n";
}

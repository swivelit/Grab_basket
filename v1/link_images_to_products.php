<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "=== Linking Existing Images to Products ===\n\n";

// Get all image files from local storage
$localFiles = Storage::disk('public')->files('products');
echo "Found " . count($localFiles) . " image files in local storage\n";

// Get products that don't have images
$productsWithoutImages = Product::whereNull('image')
    ->whereDoesntHave('productImages')
    ->take(count($localFiles))
    ->get();

echo "Found " . $productsWithoutImages->count() . " products without images\n\n";

$linked = 0;
$uploaded = 0;

foreach ($localFiles as $index => $imageFile) {
    if (!isset($productsWithoutImages[$index])) {
        break;
    }
    
    $product = $productsWithoutImages[$index];
    
    echo "Linking: {$imageFile} → Product {$product->id} ({$product->name})\n";
    
    try {
        // Update the product's legacy image field
        $product->update(['image' => $imageFile]);
        
        // Create a ProductImage record
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $imageFile,
            'original_name' => basename($imageFile),
            'mime_type' => 'image/jpeg', // Assume jpeg for now
            'file_size' => Storage::disk('public')->size($imageFile) ?? 0,
            'sort_order' => 1,
            'is_primary' => true,
        ]);
        
        // Try to upload the file to R2 as well
        try {
            $fileContent = Storage::disk('public')->get($imageFile);
            Storage::disk('r2')->put($imageFile, $fileContent);
            echo "  ✓ Uploaded to R2\n";
            $uploaded++;
        } catch (\Exception $e) {
            echo "  ⚠ R2 upload failed: " . $e->getMessage() . "\n";
        }
        
        $linked++;
        
    } catch (\Exception $e) {
        echo "  ✗ Failed to link: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Images linked to products: {$linked}\n";
echo "Images uploaded to R2: {$uploaded}\n";

// Test a few products now
echo "\n=== Testing Updated Products ===\n";
$testProducts = Product::whereNotNull('image')->take(3)->get();

foreach ($testProducts as $product) {
    echo "\nProduct: {$product->name}\n";
    echo "Image URL: {$product->image_url}\n";
    
    // Test if the URL works
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $product->image_url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$httpCode} " . ($httpCode == 200 ? "(✓ Working)" : "(✗ Not working)") . "\n";
}
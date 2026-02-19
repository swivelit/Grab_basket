<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Log;

echo "=== Fixing Missing Product Images ===\n\n";

// Get all products with missing cloud images
$missingProducts = Product::where('image', 'LIKE', 'products/%')
    ->whereNull('image_data')
    ->get();

echo "📊 Found " . $missingProducts->count() . " products with missing images\n\n";

$fixedCount = 0;
$errorCount = 0;

echo "🔧 Applying fixes...\n\n";

foreach ($missingProducts as $product) {
    try {
        // Clear the invalid image path and set a flag for re-upload needed
        $product->update([
            'image' => null,
            'description' => $product->description . "\n\n⚠️ Image needs to be re-uploaded by seller."
        ]);
        
        echo "✅ Fixed Product {$product->id}: {$product->name}\n";
        $fixedCount++;
        
        // Log for seller notification
        Log::info('Product image missing - needs re-upload', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'seller_id' => $product->seller_id,
            'missing_path' => $product->getOriginal('image')
        ]);
        
    } catch (\Exception $e) {
        echo "❌ Error fixing Product {$product->id}: " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n📈 Summary:\n";
echo "✅ Fixed products: {$fixedCount}\n";
echo "❌ Errors: {$errorCount}\n";

if ($fixedCount > 0) {
    echo "\n🎯 What happens now:\n";
    echo "- Products will show placeholder image instead of broken links\n";
    echo "- Product descriptions updated with re-upload notice\n";
    echo "- Sellers can re-upload images through edit product page\n";
    echo "- New uploads will work correctly with cloud storage\n";
}

echo "\nDone!\n";
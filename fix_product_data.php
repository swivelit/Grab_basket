<?php

/**
 * Fix Product Data Script
 * Ensures all products have proper seller_id, status, and is_active fields
 * Run this to fix existing products that may have missing data
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "==================================\n";
echo "Product Data Fix Script\n";
echo "==================================\n\n";

try {
    // Get all products
    $products = Product::all();
    $totalProducts = $products->count();
    
    echo "Total products found: {$totalProducts}\n\n";
    
    $fixed = 0;
    $errors = 0;
    $skipped = 0;
    
    foreach ($products as $product) {
        echo "Checking Product ID: {$product->id} - {$product->name}\n";
        
        $needsUpdate = false;
        $updates = [];
        
        // Check seller_id
        if (!$product->seller_id || $product->seller_id == 0) {
            echo "  ❌ Missing seller_id\n";
            
            // Try to find a valid seller (first seller in database)
            $firstSeller = Seller::first();
            if ($firstSeller) {
                $updates['seller_id'] = $firstSeller->id;
                echo "  ✅ Assigned to seller: {$firstSeller->id} ({$firstSeller->name})\n";
                $needsUpdate = true;
            } else {
                echo "  ⚠️  No sellers found in database - cannot fix\n";
                $errors++;
                continue;
            }
        }
        
        // Check status
        if (!isset($product->status) || $product->status === null) {
            $updates['status'] = 'active';
            echo "  ✅ Set status to 'active'\n";
            $needsUpdate = true;
        }
        
        // Check is_active
        if (!isset($product->is_active)) {
            $updates['is_active'] = true;
            echo "  ✅ Set is_active to true\n";
            $needsUpdate = true;
        }
        
        // Apply updates
        if ($needsUpdate) {
            try {
                $product->update($updates);
                echo "  ✅ Product updated successfully\n";
                $fixed++;
            } catch (\Exception $e) {
                echo "  ❌ Error updating: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "  ✓ Product data is correct\n";
            $skipped++;
        }
        
        echo "\n";
    }
    
    echo "==================================\n";
    echo "Summary:\n";
    echo "==================================\n";
    echo "Total Products: {$totalProducts}\n";
    echo "Fixed: {$fixed}\n";
    echo "Skipped (Already correct): {$skipped}\n";
    echo "Errors: {$errors}\n";
    echo "==================================\n";
    
    if ($fixed > 0) {
        echo "\n✅ Successfully fixed {$fixed} products!\n";
        echo "All products should now have proper seller_id, status, and is_active fields.\n";
    }
    
    if ($errors > 0) {
        echo "\n⚠️  Warning: {$errors} products could not be fixed. Check the output above for details.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Script completed!\n";

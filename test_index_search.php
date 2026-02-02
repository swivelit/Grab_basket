<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;

echo "=== Testing Index Page Search Functionality ===\n\n";

try {
    // Test 1: Empty search (should return products)
    echo "Test 1: Empty search query\n";
    $query1 = Product::with(['category', 'subcategory'])
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%');
    
    $count1 = $query1->count();
    echo "✅ Products found: {$count1}\n\n";
    
    // Test 2: Search by product name
    echo "Test 2: Search by product name ('honey')\n";
    $search = 'honey';
    $query2 = Product::with(['category', 'subcategory'])
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    
    $count2 = $query2->count();
    echo "✅ Products found: {$count2}\n";
    if ($count2 > 0) {
        $sample = $query2->first();
        echo "   Sample: {$sample->name}\n";
    }
    echo "\n";
    
    // Test 3: Search by store name (the problematic one)
    echo "Test 3: Search by store name ('Maltrix')\n";
    $search = 'Maltrix';
    
    // First, check if Seller model exists and has data
    echo "   Step 3a: Checking Seller model...\n";
    $sellerEmails = Seller::where('name', 'like', "%{$search}%")
        ->orWhere('store_name', 'like', "%{$search}%")
        ->pluck('email');
    
    echo "   ✅ Seller emails found: " . $sellerEmails->count() . "\n";
    if ($sellerEmails->isNotEmpty()) {
        echo "   Sample email: " . $sellerEmails->first() . "\n";
    }
    
    if ($sellerEmails->isNotEmpty()) {
        echo "   Step 3b: Getting User IDs from emails...\n";
        $userIds = User::whereIn('email', $sellerEmails)->pluck('id');
        echo "   ✅ User IDs found: " . $userIds->count() . "\n";
        if ($userIds->isNotEmpty()) {
            echo "   Sample User ID: " . $userIds->first() . "\n";
        }
        
        if ($userIds->isNotEmpty()) {
            echo "   Step 3c: Finding products by seller_id...\n";
            $query3 = Product::with(['category', 'subcategory'])
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%')
                ->whereIn('seller_id', $userIds);
            
            $count3 = $query3->count();
            echo "   ✅ Products found: {$count3}\n";
            if ($count3 > 0) {
                $sample = $query3->first();
                echo "   Sample: {$sample->name}\n";
            }
        }
    }
    echo "\n";
    
    // Test 4: Complete search query (as used in controller)
    echo "Test 4: Complete search with all conditions\n";
    $search = 'oil';
    
    $query4 = Product::with(['category', 'subcategory'])
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%');

    $query4->where(function ($q) use ($search) {
        // Search in product fields
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%")
          ->orWhere('unique_id', 'like', "%{$search}%")
          // Search in category
          ->orWhereHas('category', function($query) use ($search) {
              $query->where('name', 'like', "%{$search}%");
          })
          // Search in subcategory
          ->orWhereHas('subcategory', function($query) use ($search) {
              $query->where('name', 'like', "%{$search}%");
          });
          
        // Search in sellers table
        $sellerEmails = Seller::where('name', 'like', "%{$search}%")
            ->orWhere('store_name', 'like', "%{$search}%")
            ->pluck('email');
            
        if ($sellerEmails->isNotEmpty()) {
            $userIds = User::whereIn('email', $sellerEmails)->pluck('id');
            if ($userIds->isNotEmpty()) {
                $q->orWhereIn('seller_id', $userIds);
            }
        }
    });
    
    $count4 = $query4->count();
    echo "✅ Products found: {$count4}\n";
    if ($count4 > 0) {
        $sample = $query4->first();
        echo "   Sample: {$sample->name}\n";
    }
    
    echo "\n✅ ALL SEARCH TESTS PASSED!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";

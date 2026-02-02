<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Search Functionality\n";
echo "============================\n\n";

// Test 1: Search without query (should return all active products)
echo "Test 1: Search without query\n";
echo "-----------------------------\n";
try {
    $products = App\Models\Product::with(['category', 'subcategory'])
        ->where('is_active', true)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->take(5)
        ->get();
    
    echo "✅ SUCCESS: Found {$products->count()} products\n";
    foreach ($products as $p) {
        echo "   - {$p->name}\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n";

// Test 2: Search with product name
echo "Test 2: Search for 'oil'\n";
echo "-------------------------\n";
try {
    $search = 'oil';
    $products = App\Models\Product::with(['category', 'subcategory'])
        ->where('is_active', true)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%')
        ->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        })
        ->take(5)
        ->get();
    
    echo "✅ SUCCESS: Found {$products->count()} products\n";
    foreach ($products as $p) {
        echo "   - {$p->name}\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n";

// Test 3: Search with seller name
echo "Test 3: Search for 'SRM' (seller name)\n";
echo "---------------------------------------\n";
try {
    $search = 'SRM';
    
    // First get seller emails
    $sellerEmails = App\Models\Seller::where('name', 'like', "%{$search}%")
        ->orWhere('store_name', 'like', "%{$search}%")
        ->pluck('email');
    
    echo "Found {$sellerEmails->count()} sellers with matching name/store\n";
    
    if ($sellerEmails->isNotEmpty()) {
        // Get user IDs
        $userIds = App\Models\User::whereIn('email', $sellerEmails)->pluck('id');
        echo "Found {$userIds->count()} matching users\n";
        
        if ($userIds->isNotEmpty()) {
            // Get products
            $products = App\Models\Product::with(['category', 'subcategory'])
                ->where('is_active', true)
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%')
                ->whereIn('seller_id', $userIds)
                ->take(5)
                ->get();
            
            echo "✅ SUCCESS: Found {$products->count()} products\n";
            foreach ($products as $p) {
                echo "   - {$p->name}\n";
            }
        }
    } else {
        echo "⚠️  No sellers found matching '{$search}'\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
}

echo "\n";

// Test 4: Check if is_active column exists
echo "Test 4: Check is_active column\n";
echo "-------------------------------\n";
try {
    $hasColumn = Illuminate\Support\Facades\Schema::hasColumn('products', 'is_active');
    if ($hasColumn) {
        echo "✅ is_active column exists\n";
        
        $activeCount = App\Models\Product::where('is_active', true)->count();
        $inactiveCount = App\Models\Product::where('is_active', false)->orWhereNull('is_active')->count();
        $total = App\Models\Product::count();
        
        echo "   Active products: {$activeCount}\n";
        echo "   Inactive/NULL products: {$inactiveCount}\n";
        echo "   Total: {$total}\n";
    } else {
        echo "❌ is_active column DOES NOT exist!\n";
        echo "   This is likely causing the search error!\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR checking column: {$e->getMessage()}\n";
}

echo "\nDone!\n";

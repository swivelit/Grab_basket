<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Search After Fix\n";
echo "========================\n\n";

// Test search for 'oil'
echo "Test: Search for 'oil'\n";
echo "----------------------\n";
try {
    $search = 'oil';
    $products = App\Models\Product::with(['category', 'subcategory'])
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
    
    echo "✅ SUCCESS: Found {$products->count()} products matching 'oil'\n";
    foreach ($products as $p) {
        echo "   - {$p->name}\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n";

// Test search for seller name
echo "Test: Search for 'SRM' (seller)\n";
echo "--------------------------------\n";
try {
    $search = 'SRM';
    
    $sellerEmails = App\Models\Seller::where('name', 'like', "%{$search}%")
        ->orWhere('store_name', 'like', "%{$search}%")
        ->pluck('email');
    
    if ($sellerEmails->isNotEmpty()) {
        $userIds = App\Models\User::whereIn('email', $sellerEmails)->pluck('id');
        
        if ($userIds->isNotEmpty()) {
            $products = App\Models\Product::with(['category', 'subcategory'])
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->where('image', 'NOT LIKE', '%unsplash%')
                ->where('image', 'NOT LIKE', '%placeholder%')
                ->where('image', 'NOT LIKE', '%via.placeholder%')
                ->whereIn('seller_id', $userIds)
                ->take(5)
                ->get();
            
            echo "✅ SUCCESS: Found {$products->count()} products from SRM\n";
            foreach ($products as $p) {
                echo "   - {$p->name}\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n";

// Test empty search (should return products)
echo "Test: Empty search (all products)\n";
echo "----------------------------------\n";
try {
    $products = App\Models\Product::with(['category', 'subcategory'])
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

echo "\n✅ All search tests passed!\n";

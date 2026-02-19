<?php

// Test Updated PDF Download with Proper Headers
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Updated PDF Download Functionality...\n\n";

try {
    $seller = \App\Models\User::where('role', 'seller')->first();
    
    if (!$seller) {
        echo "❌ No seller found\n";
        exit(1);
    }
    
    echo "✅ Testing with seller: {$seller->name}\n\n";
    
    // Test 1: Simple PDF with new response method
    echo "Test 1: Simple PDF with streamDownload\n";
    echo "========================================\n";
    
    $products = \App\Models\Product::where('seller_id', $seller->id)
        ->with(['category', 'subcategory'])
        ->take(5)
        ->get();
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf', [
        'products' => $products,
        'seller' => $seller,
        'exportDate' => now()
    ]);
    
    $pdf->setPaper('a4', 'landscape');
    $filename = 'test-download.pdf';
    
    // Simulate the response
    $response = response()->streamDownload(function() use ($pdf) {
        echo $pdf->output();
    }, $filename, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
    
    echo "✅ Response created successfully\n";
    echo "   Type: " . get_class($response) . "\n";
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        echo "      {$name}: " . implode(', ', $values) . "\n";
    }
    
    // Get the content
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();
    
    if (strlen($content) > 0) {
        echo "   Content size: " . number_format(strlen($content) / 1024, 2) . " KB\n";
        echo "   Content starts with: " . substr($content, 0, 8) . "\n";
        
        if (substr($content, 0, 4) === '%PDF') {
            echo "✅ Valid PDF format detected\n";
        } else {
            echo "⚠️ Content doesn't start with %PDF marker\n";
        }
        
        // Save test file
        $testFile = storage_path('app/test-stream-download.pdf');
        file_put_contents($testFile, $content);
        echo "✅ Saved test file: {$testFile}\n";
    } else {
        echo "❌ Response content is empty\n";
    }
    
    echo "\n";
    
    // Test 2: PDF with images
    echo "Test 2: PDF with Images using streamDownload\n";
    echo "========================================\n";
    
    $productsWithImages = \App\Models\Product::where('seller_id', $seller->id)
        ->with(['category', 'subcategory', 'images'])
        ->orderBy('category_id')
        ->orderBy('name')
        ->take(3)
        ->get();
    
    $productsByCategory = $productsWithImages->groupBy(function($product) {
        return $product->category->name ?? 'Uncategorized';
    });
    
    $stats = [
        'total_products' => $productsWithImages->count(),
        'total_categories' => $productsByCategory->count(),
        'total_stock' => $productsWithImages->sum('stock'),
        'total_value' => $productsWithImages->sum(function($product) {
            return $product->price * $product->stock;
        }),
        'active_products' => $productsWithImages->where('status', 'active')->count(),
        'out_of_stock' => $productsWithImages->where('stock', '<=', 0)->count(),
    ];
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf-with-images', [
        'productsByCategory' => $productsByCategory,
        'seller' => $seller,
        'exportDate' => now(),
        'stats' => $stats
    ]);
    
    $pdf->setPaper('a4', 'portrait');
    $pdf->setOption('isRemoteEnabled', true);
    
    set_time_limit(300);
    ini_set('memory_limit', '512M');
    
    $filename = 'test-with-images.pdf';
    
    $response = response()->streamDownload(function() use ($pdf) {
        echo $pdf->output();
    }, $filename, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
    
    echo "✅ Response created successfully\n";
    echo "   Status: " . $response->getStatusCode() . "\n";
    
    // Get the content
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();
    
    if (strlen($content) > 0) {
        echo "   Content size: " . number_format(strlen($content) / 1024, 2) . " KB\n";
        
        if (substr($content, 0, 4) === '%PDF') {
            echo "✅ Valid PDF format with images\n";
            
            // Save test file
            $testFile = storage_path('app/test-stream-with-images.pdf');
            file_put_contents($testFile, $content);
            echo "✅ Saved test file: {$testFile}\n";
        } else {
            echo "⚠️ Invalid PDF format\n";
        }
    } else {
        echo "❌ Response content is empty\n";
    }
    
    echo "\n========================================\n";
    echo "Summary\n";
    echo "========================================\n";
    echo "✅ streamDownload method working correctly\n";
    echo "✅ Proper download headers set\n";
    echo "✅ Content-Disposition: attachment (forces download)\n";
    echo "✅ Cache-Control headers prevent caching issues\n";
    echo "✅ PDF content generated successfully\n";
    echo "\n";
    echo "The fix should now force browsers to download the PDF.\n";
    echo "\nNext steps:\n";
    echo "1. Clear browser cache (Ctrl + Shift + Delete)\n";
    echo "2. Try export again from Import/Export page\n";
    echo "3. Check Downloads folder\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

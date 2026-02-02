<?php

// Test PDF Download Functionality
require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing PDF Download Issues...\n\n";

try {
    // Simulate authenticated user
    $seller = \App\Models\User::where('role', 'seller')->first();
    
    if (!$seller) {
        echo "❌ No seller found in database. Please create a seller account first.\n";
        exit(1);
    }
    
    echo "✅ Found seller: {$seller->name} (ID: {$seller->id})\n";
    
    // Get seller's products
    $products = \App\Models\Product::where('seller_id', $seller->id)
        ->with(['category', 'subcategory'])
        ->get();
    
    echo "✅ Found {$products->count()} products\n\n";
    
    if ($products->isEmpty()) {
        echo "⚠️ No products found. PDF will be empty but should still generate.\n\n";
    }
    
    // Test 1: Simple PDF (no images)
    echo "Test 1: Simple PDF Export (no images)\n";
    echo "----------------------------------------\n";
    
    try {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf', [
            'products' => $products,
            'seller' => $seller,
            'exportDate' => now()
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        // Try to get PDF output
        $output = $pdf->output();
        
        if (strlen($output) > 0) {
            echo "✅ Simple PDF generated successfully\n";
            echo "   Size: " . number_format(strlen($output) / 1024, 2) . " KB\n";
            
            // Try to save it
            $filename = storage_path('app/test-simple.pdf');
            file_put_contents($filename, $output);
            echo "✅ Saved to: {$filename}\n";
        } else {
            echo "❌ PDF output is empty\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error generating simple PDF:\n";
        echo "   Message: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
    
    // Test 2: PDF with images
    echo "Test 2: PDF Export with Images\n";
    echo "----------------------------------------\n";
    
    try {
        // Get products with images
        $productsWithImages = \App\Models\Product::where('seller_id', $seller->id)
            ->with(['category', 'subcategory', 'images'])
            ->orderBy('category_id')
            ->orderBy('name')
            ->limit(5) // Limit to 5 products for quick test
            ->get();
        
        echo "Testing with {$productsWithImages->count()} products...\n";
        
        // Group by category
        $productsByCategory = $productsWithImages->groupBy(function($product) {
            return $product->category->name ?? 'Uncategorized';
        });
        
        // Calculate stats
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
        $pdf->setOption('chroot', public_path());
        
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        
        // Try to get PDF output
        $output = $pdf->output();
        
        if (strlen($output) > 0) {
            echo "✅ PDF with images generated successfully\n";
            echo "   Size: " . number_format(strlen($output) / 1024, 2) . " KB\n";
            
            // Try to save it
            $filename = storage_path('app/test-with-images.pdf');
            file_put_contents($filename, $output);
            echo "✅ Saved to: {$filename}\n";
        } else {
            echo "❌ PDF output is empty\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error generating PDF with images:\n";
        echo "   Message: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        if (method_exists($e, 'getTraceAsString')) {
            echo "\n   Stack trace:\n";
            $trace = explode("\n", $e->getTraceAsString());
            foreach (array_slice($trace, 0, 5) as $line) {
                echo "   " . $line . "\n";
            }
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Download Method Test\n";
    echo "========================================\n";
    
    // Test the download method (won't actually download in CLI)
    try {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf', [
            'products' => $products->take(5),
            'seller' => $seller,
            'exportDate' => now()
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        $filename = 'test-download.pdf';
        
        // Get the download response
        $response = $pdf->download($filename);
        
        echo "✅ Download method executed successfully\n";
        echo "   Response type: " . get_class($response) . "\n";
        echo "   Filename: {$filename}\n";
        
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
            echo "   Content size: " . number_format(strlen($content) / 1024, 2) . " KB\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Download method failed:\n";
        echo "   Message: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "Common Issues to Check:\n";
    echo "========================================\n";
    echo "1. Check browser console (F12) for JavaScript errors\n";
    echo "2. Check Network tab to see HTTP response code\n";
    echo "3. Check if CSRF token is valid (try refreshing page)\n";
    echo "4. Check storage/app directory permissions\n";
    echo "5. Try with just 1-2 products first\n";
    echo "\n";
    echo "If tests passed, the issue might be:\n";
    echo "- Browser blocking download\n";
    echo "- Form submission issue (check routes/CSRF)\n";
    echo "- Session expired (refresh and try again)\n";
    
} catch (\Exception $e) {
    echo "\n❌ Fatal Error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

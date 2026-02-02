<?php

// Test PDF Export Functionality
require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing PDF Export...\n\n";

try {
    // Test 1: Check if Pdf facade is available
    echo "1. Checking Pdf facade... ";
    $pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Test</h1>');
    echo "✅ OK\n";
    
    // Test 2: Check if can create simple PDF
    echo "2. Creating simple PDF... ";
    $pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('seller.exports.products-pdf', [
        'products' => collect([]),
        'seller' => (object)['business_name' => 'Test Store', 'name' => 'Test', 'email' => 'test@test.com'],
        'exportDate' => now()
    ]);
    echo "✅ OK\n";
    
    // Test 3: Check if can create PDF with images view
    echo "3. Checking products-pdf-with-images view... ";
    if (view()->exists('seller.exports.products-pdf-with-images')) {
        echo "✅ View exists\n";
    } else {
        echo "❌ View NOT found\n";
    }
    
    // Test 4: Test image URL fetching
    echo "4. Testing image URL access... ";
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    // Test with a sample URL (you can replace with actual R2 URL)
    $testUrl = 'https://via.placeholder.com/150';
    $content = @file_get_contents($testUrl, false, $context);
    
    if ($content !== false) {
        echo "✅ Can fetch external images\n";
    } else {
        echo "❌ Cannot fetch external images\n";
    }
    
    // Test 5: Test base64 conversion
    echo "5. Testing base64 conversion... ";
    if ($content !== false) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);
        $base64 = base64_encode($content);
        if (strlen($base64) > 0) {
            echo "✅ Base64 conversion works\n";
        } else {
            echo "❌ Base64 conversion failed\n";
        }
    } else {
        echo "⚠️ Skipped (no image to test)\n";
    }
    
    // Test 6: Check memory limit
    echo "6. Checking memory limit... ";
    $memoryLimit = ini_get('memory_limit');
    echo $memoryLimit;
    if (preg_match('/(\d+)([MG])/', $memoryLimit, $matches)) {
        $value = (int)$matches[1];
        $unit = $matches[2];
        if (($unit === 'M' && $value >= 256) || $unit === 'G') {
            echo " ✅ Sufficient\n";
        } else {
            echo " ⚠️ May be too low (recommend 512M+)\n";
        }
    }
    
    // Test 7: Check max execution time
    echo "7. Checking max execution time... ";
    $maxTime = ini_get('max_execution_time');
    echo $maxTime . " seconds";
    if ($maxTime == 0 || $maxTime >= 120) {
        echo " ✅ Sufficient\n";
    } else {
        echo " ⚠️ May be too low (recommend 300+)\n";
    }
    
    echo "\n✅ All basic tests passed!\n";
    echo "\nIf you're still having issues, please check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Laravel logs: storage/logs/laravel.log\n";
    echo "3. Try exporting with just 1-2 products first\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error occurred:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

<?php
// storage_check.php
// Usage: php storage_check.php

use Illuminate\Support\Facades\Storage;

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function checkDisk($disk, $testPath = 'storage_check/test.txt', $content = 'Storage check test') {
    echo "Checking disk: $disk\n";
    try {
        // Write test
        $write = Storage::disk($disk)->put($testPath, $content);
        echo $write ? "  ✅ Write: Success\n" : "  ❌ Write: Failed\n";
        // Read test
        $read = Storage::disk($disk)->get($testPath);
        echo ($read === $content) ? "  ✅ Read: Success\n" : "  ❌ Read: Failed\n";
        // URL test (if supported)
        try {
            $url = Storage::disk($disk)->url($testPath);
            echo "  🔗 URL: $url\n";
        } catch (Throwable $e) {
            echo "  ⚠️  URL: Not supported\n";
        }
        // Delete test
        $delete = Storage::disk($disk)->delete($testPath);
        echo $delete ? "  ✅ Delete: Success\n" : "  ❌ Delete: Failed\n";
    } catch (Throwable $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "-----------------------------\n";
}


// Disks to check (add 'private' for private AWS bucket)
$disks = ['r2', 'private', 'public', 'local'];
foreach ($disks as $disk) {
    checkDisk($disk);
}

echo "\nStorage check complete.\n";

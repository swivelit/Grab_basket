<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "\n=== SELLER PROFILE IMAGE DIAGNOSTIC ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check storage configuration
echo "1. STORAGE CONFIGURATION:\n";
echo "   Default disk: " . config('filesystems.default') . "\n";
echo "   AWS Endpoint: " . env('AWS_ENDPOINT') . "\n";
echo "   AWS URL: " . env('AWS_URL') . "\n";
echo "   AWS Bucket: " . env('AWS_BUCKET') . "\n\n";

// Check sellers uploaded today
echo "2. SELLERS WITH IMAGES UPLOADED TODAY:\n";
$today = date('Y-m-d');
$sellers = DB::table('users')
    ->where('role', 'seller')
    ->whereNotNull('profile_picture')
    ->whereDate('updated_at', $today)
    ->get(['id', 'name', 'email', 'profile_picture', 'created_at', 'updated_at']);

if ($sellers->isEmpty()) {
    echo "   No sellers with images uploaded today.\n\n";
} else {
    foreach ($sellers as $seller) {
        echo "   Seller: {$seller->name} ({$seller->email})\n";
        echo "   Profile Picture: {$seller->profile_picture}\n";
        echo "   Updated: {$seller->updated_at}\n";
        
        // Check if image URL is accessible
        if ($seller->profile_picture) {
            $ch = curl_init($seller->profile_picture);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                echo "   ✓ Image accessible (HTTP $httpCode)\n";
            } else {
                echo "   ✗ Image NOT accessible (HTTP $httpCode)\n";
            }
        }
        echo "\n";
    }
}

// Check all sellers with images
echo "3. ALL SELLERS WITH PROFILE IMAGES:\n";
$allSellersWithImages = DB::table('users')
    ->where('role', 'seller')
    ->whereNotNull('profile_picture')
    ->orderBy('updated_at', 'desc')
    ->limit(10)
    ->get(['id', 'name', 'email', 'profile_picture', 'updated_at']);

if ($allSellersWithImages->isEmpty()) {
    echo "   No sellers have profile images.\n\n";
} else {
    echo "   Found " . $allSellersWithImages->count() . " sellers with images (showing last 10):\n\n";
    foreach ($allSellersWithImages as $seller) {
        echo "   {$seller->name} - {$seller->email}\n";
        echo "   Image: " . (strlen($seller->profile_picture) > 80 ? substr($seller->profile_picture, 0, 80) . '...' : $seller->profile_picture) . "\n";
        echo "   Last Updated: {$seller->updated_at}\n\n";
    }
}

// Check R2 storage connectivity
echo "4. R2 STORAGE TEST:\n";
try {
    $testFile = 'test_' . time() . '.txt';
    Storage::disk('r2')->put($testFile, 'Test file created at ' . date('Y-m-d H:i:s'));
    
    if (Storage::disk('r2')->exists($testFile)) {
        echo "   ✓ Successfully wrote test file to R2\n";
        
        $url = Storage::disk('r2')->url($testFile);
        echo "   Test file URL: $url\n";
        
        // Clean up
        Storage::disk('r2')->delete($testFile);
        echo "   ✓ Test file deleted\n";
    } else {
        echo "   ✗ Failed to write test file to R2\n";
    }
} catch (\Exception $e) {
    echo "   ✗ R2 Storage Error: " . $e->getMessage() . "\n";
}

echo "\n5. RECENT PROFILE UPDATES IN LOGS:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $profileLines = [];
    foreach (array_reverse($lines) as $line) {
        if (stripos($line, 'profile') !== false || stripos($line, 'avatar') !== false) {
            $profileLines[] = $line;
            if (count($profileLines) >= 10) break;
        }
    }
    
    if (empty($profileLines)) {
        echo "   No profile-related logs found.\n";
    } else {
        foreach (array_reverse($profileLines) as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   Log file not found.\n";
}

echo "\n=== END DIAGNOSTIC ===\n";

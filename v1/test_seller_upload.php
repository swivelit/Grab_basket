<?php

/**
 * Test Script: Simulate Seller Profile Image Upload
 * Purpose: Identify why uploaded images today are not visible
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "\n=== SELLER PROFILE IMAGE UPLOAD TEST ===\n";
echo "Testing upload functionality...\n\n";

// Step 1: Check if any sellers exist
echo "1. CHECKING SELLERS:\n";
$sellers = DB::table('users')->where('role', 'seller')->get(['id', 'name', 'email', 'profile_picture']);
echo "   Found " . $sellers->count() . " sellers\n\n";

if ($sellers->isEmpty()) {
    echo "   ❌ No sellers found. Cannot test upload.\n";
    exit(1);
}

// Step 2: Check storage disk configuration
echo "2. STORAGE CONFIGURATION:\n";
try {
    $r2Config = config('filesystems.disks.r2');
    echo "   ✓ R2 disk configured\n";
    echo "   Endpoint: " . ($r2Config['endpoint'] ?? 'NOT SET') . "\n";
    echo "   Bucket: " . ($r2Config['bucket'] ?? 'NOT SET') . "\n";
    echo "   URL: " . ($r2Config['url'] ?? 'NOT SET') . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ R2 configuration error: " . $e->getMessage() . "\n\n";
}

// Step 3: Test R2 storage write
echo "3. TESTING R2 STORAGE WRITE:\n";
try {
    $testFilename = 'profile_photos/test_upload_' . time() . '.txt';
    $testContent = 'Test upload at ' . date('Y-m-d H:i:s');
    
    Storage::disk('r2')->put($testFilename, $testContent);
    echo "   ✓ Successfully wrote file: $testFilename\n";
    
    // Construct URL like the controller does
    $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
    $testUrl = $r2PublicUrl . '/' . $testFilename;
    echo "   URL would be: $testUrl\n";
    
    // Check if file exists
    if (Storage::disk('r2')->exists($testFilename)) {
        echo "   ✓ File exists in R2\n";
        
        // Try to get URL using Storage facade
        $storageUrl = Storage::disk('r2')->url($testFilename);
        echo "   Storage URL: $storageUrl\n";
    }
    
    // Clean up
    Storage::disk('r2')->delete($testFilename);
    echo "   ✓ Test file deleted\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ Storage test failed: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Step 4: Check recent database updates
echo "4. RECENT PROFILE UPDATES:\n";
$recentUpdates = DB::table('users')
    ->where('role', 'seller')
    ->whereDate('updated_at', '>=', date('Y-m-d', strtotime('-7 days')))
    ->orderBy('updated_at', 'desc')
    ->get(['id', 'name', 'email', 'profile_picture', 'updated_at']);

if ($recentUpdates->isEmpty()) {
    echo "   No profile updates in the last 7 days\n\n";
} else {
    foreach ($recentUpdates as $update) {
        echo "   Seller: {$update->name}\n";
        echo "   Email: {$update->email}\n";
        echo "   Profile Picture: " . ($update->profile_picture ?: 'NULL') . "\n";
        echo "   Last Updated: {$update->updated_at}\n\n";
    }
}

// Step 5: Simulate upload (create a test image)
echo "5. SIMULATING IMAGE UPLOAD:\n";
try {
    // Create a simple test image
    $testImage = imagecreatetruecolor(200, 200);
    $bgColor = imagecolorallocate($testImage, 255, 0, 0); // Red background
    imagefill($testImage, 0, 0, $bgColor);
    
    // Save to temporary file
    $tempFile = sys_get_temp_dir() . '/test_profile_' . time() . '.jpg';
    imagejpeg($testImage, $tempFile, 90);
    imagedestroy($testImage);
    
    echo "   ✓ Created test image: $tempFile\n";
    echo "   File size: " . filesize($tempFile) . " bytes\n";
    
    // Upload like the controller does
    $filename = 'profile_photos/test_seller_' . time() . '.jpg';
    Storage::disk('r2')->put($filename, file_get_contents($tempFile));
    
    echo "   ✓ Uploaded to R2: $filename\n";
    
    // Construct URL
    $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
    $photoUrl = $r2PublicUrl . '/' . $filename;
    
    echo "   Public URL: $photoUrl\n";
    
    // Test URL accessibility
    $ch = curl_init($photoUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "   ✓ Image is accessible (HTTP $httpCode)\n";
    } else {
        echo "   ⚠ Image returned HTTP $httpCode (may take time to propagate)\n";
    }
    
    // Clean up
    unlink($tempFile);
    Storage::disk('r2')->delete($filename);
    echo "   ✓ Cleaned up test files\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ Upload simulation failed: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Step 6: Check if CSRF token or session issues
echo "6. CHECKING POTENTIAL ISSUES:\n";
echo "   Session driver: " . config('session.driver') . "\n";
echo "   Session lifetime: " . config('session.lifetime') . " minutes\n";
echo "   CSRF protection: " . (class_exists('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken') ? 'ENABLED' : 'DISABLED') . "\n\n";

// Step 7: Recommendations
echo "7. RECOMMENDATIONS:\n";

if ($sellers->count() > 0 && $sellers->where('profile_picture', null)->count() == $sellers->count()) {
    echo "   ⚠ No sellers have profile pictures yet\n";
    echo "   → This is expected if no one has uploaded\n";
    echo "   → Ask a seller to try uploading today and check logs\n\n";
}

echo "   To debug real upload attempts:\n";
echo "   1. Enable logging in browser DevTools (Network tab)\n";
echo "   2. Watch Laravel logs: tail -f storage/logs/laravel.log\n";
echo "   3. Check for JavaScript errors in Console tab\n";
echo "   4. Verify CSRF token is present in form\n\n";

echo "=== END TEST ===\n";

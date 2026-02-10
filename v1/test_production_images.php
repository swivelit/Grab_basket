<?php
/**
 * Test Production Image Serving
 * 
 * This script tests if the /serve-image/ route is working on production
 */

$baseUrl = 'https://grabbaskets.laravel.cloud';
$testImagePath = 'products/seller-2/srm340-1760342455.jpg';
$testUrl = $baseUrl . '/serve-image/' . $testImagePath;

echo "🧪 Testing Production Image Serving\n";
echo str_repeat("=", 70) . "\n\n";

echo "Test URL: {$testUrl}\n\n";

// Test with cURL
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_VERBOSE, false);

echo "Making request to production...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
curl_close($ch);

echo "\nResponse Details:\n";
echo "  HTTP Status: {$httpCode}\n";
echo "  Content-Type: {$contentType}\n";
echo "  Time Taken: " . round($totalTime * 1000) . "ms\n\n";

if ($httpCode == 200) {
    echo "✅ SUCCESS! Image is being served correctly!\n";
    echo "\n";
    echo "The /serve-image/ route is working on production.\n";
    echo "Images should now display in the browser.\n\n";
    
    // Check if it's actually an image
    if (strpos($contentType, 'image') !== false) {
        echo "✅ Content-Type confirms it's an image: {$contentType}\n";
    } else {
        echo "⚠️  Warning: Content-Type is not an image type\n";
    }
    
} elseif ($httpCode == 404) {
    echo "❌ ERROR: Route returned 404 Not Found\n\n";
    echo "Possible causes:\n";
    echo "  1. Route not deployed yet (wait a few minutes)\n";
    echo "  2. Route cache needs clearing on production\n";
    echo "  3. Image file doesn't exist in R2 storage\n\n";
    
    echo "Solutions:\n";
    echo "  1. Wait 2-3 minutes for deployment to complete\n";
    echo "  2. Clear route cache via Laravel Cloud dashboard\n";
    echo "  3. Check if the image exists locally:\n";
    echo "     php check_r2_storage.php\n\n";
    
} elseif ($httpCode == 500) {
    echo "❌ ERROR: Server error (500)\n\n";
    echo "Possible causes:\n";
    echo "  1. Route code has an error\n";
    echo "  2. R2 credentials not configured correctly\n";
    echo "  3. Storage::disk('r2') not working\n\n";
    
} elseif ($httpCode == 0) {
    echo "❌ ERROR: Could not connect to server\n\n";
    echo "Check your internet connection and try again.\n\n";
    
} else {
    echo "⚠️  Unexpected HTTP status: {$httpCode}\n\n";
}

// Test dashboard page to ensure site is up
echo str_repeat("-", 70) . "\n";
echo "Testing main site accessibility...\n\n";

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
$siteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($siteHttpCode == 200 || $siteHttpCode == 302) {
    echo "✅ Main site is accessible (HTTP {$siteHttpCode})\n";
} else {
    echo "⚠️  Main site returned HTTP {$siteHttpCode}\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Test complete!\n\n";

if ($httpCode == 200) {
    echo "✅ Images should be displaying now!\n";
    echo "   Visit: {$baseUrl}/seller/dashboard\n";
} else {
    echo "❌ Fix required. See error details above.\n";
}
echo "\n";

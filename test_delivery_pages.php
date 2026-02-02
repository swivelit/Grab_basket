<?php
/**
 * Test delivery partner pages with existing ID
 */

echo "Testing Admin Delivery Partner Pages\n";
echo "=====================================\n\n";

$baseUrl = 'https://grabbaskets.com';

$pages = [
    'Show Page (Partner 1)' => '/admin/delivery-partners/1',
    'Track Page (Partner 1)' => '/admin/delivery-partners/1/track',
];

foreach ($pages as $name => $path) {
    $url = $baseUrl . $path;
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ Status: HTTP $httpCode - PASS\n";
    } elseif ($httpCode == 302) {
        echo "⚠️  Status: HTTP $httpCode - Redirect (needs login)\n";
    } else {
        echo "❌ Status: HTTP $httpCode - FAIL\n";
    }
    echo "\n";
}

echo "Note: Partner ID 3 doesn't exist. Only partner ID 1 exists in database.\n";

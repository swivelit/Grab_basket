<?php
/**
 * Test script for admin delivery partner pages
 * Run: php test_admin_delivery_pages.php
 */

$baseUrl = 'https://grabbaskets.com';

$pages = [
    'Index Page' => '/admin/delivery-partners',
    'Show Page (Partner 1)' => '/admin/delivery-partners/1',
    'Track Page (Partner 1)' => '/admin/delivery-partners/1/track',
    'Dashboard' => '/admin/delivery-partners/dashboard',
];

echo "Testing Admin Delivery Partner Pages\n";
echo "=====================================\n\n";

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
    
    $status = $httpCode == 200 ? '✓ PASS' : '✗ FAIL';
    echo "Status: HTTP $httpCode - $status\n\n";
}

echo "=====================================\n";
echo "Test complete!\n";

<?php
/**
 * Test SMS Management page
 */

// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing SMS Management Page...\n\n";

// Test with cURL
$url = 'https://grabbaskets.com/admin/sms-management';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

if ($httpCode == 500) {
    echo "\nResponse Headers and Body:\n";
    echo "=========================\n";
    echo substr($response, 0, 2000) . "\n";
}

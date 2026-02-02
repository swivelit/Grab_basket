<?php
/**
 * Quick Razorpay Credential Validator
 */

// Direct credential check (bypass Laravel config for testing)
$keyId = 'rzp_live_RZLX30zmmnhHum';
$keySecret = 'XKmsdH5PbR49EiT74CgehYYi';

echo "<!DOCTYPE html><html><head><title>Razorpay Credential Test</title>
<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .box { background: white; padding: 20px; border-radius: 5px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
</head><body>";

echo "<h1>🔐 Razorpay Credential Validation</h1>";

// Check key format
echo "<div class='box'><h2>1. Key Format Check</h2>";
if (preg_match('/^rzp_(test|live)_[A-Za-z0-9]+$/', $keyId)) {
    echo "<p class='success'>✅ Key ID format is valid: $keyId</p>";
    $keyType = strpos($keyId, 'rzp_live_') === 0 ? 'LIVE' : 'TEST';
    echo "<p><strong>Key Type:</strong> $keyType</p>";
    if ($keyType === 'LIVE') {
        echo "<p style='color: orange;'>⚠️ Using LIVE credentials - real payments will be processed!</p>";
    }
} else {
    echo "<p class='error'>❌ Key ID format is invalid</p>";
}

if (strlen($keySecret) >= 20) {
    echo "<p class='success'>✅ Key Secret length is valid (" . strlen($keySecret) . " characters)</p>";
} else {
    echo "<p class='error'>❌ Key Secret appears too short</p>";
}
echo "</div>";

// Test API connection
echo "<div class='box'><h2>2. API Connection Test</h2>";

$ch = curl_init();
$testAmount = 100; // ₹1.00 in paise

$orderData = json_encode([
    'amount' => $testAmount,
    'currency' => 'INR',
    'receipt' => 'test_' . time()
]);

curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.razorpay.com/v1/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $orderData,
    CURLOPT_USERPWD => "$keyId:$keySecret",
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Status Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p class='error'>❌ cURL Error: $error</p>";
} else {
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['id'])) {
        echo "<p class='success'>✅ SUCCESS! Razorpay API is working</p>";
        echo "<p><strong>Order ID:</strong> {$data['id']}</p>";
        echo "<p><strong>Status:</strong> {$data['status']}</p>";
        echo "<p><strong>Amount:</strong> ₹" . ($data['amount'] / 100) . "</p>";
        echo "<div style='background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0;'>
            <strong>✅ Credentials are VALID</strong><br>
            Your Razorpay integration is working correctly. The issue is likely in your Laravel application code or frontend JavaScript.
        </div>";
    } elseif ($httpCode === 401) {
        echo "<p class='error'>❌ Authentication Failed (401)</p>";
        echo "<div style='background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0;'>
            <strong>⚠️ INVALID CREDENTIALS</strong><br>
            Your Razorpay Key ID or Key Secret is incorrect.<br><br>
            <strong>How to fix:</strong><br>
            1. Go to Razorpay Dashboard → Settings → API Keys<br>
            2. Generate new keys if needed<br>
            3. Update your .env file with correct credentials<br>
            4. Run: php artisan config:clear
        </div>";
    } elseif ($httpCode === 400) {
        echo "<p class='error'>❌ Bad Request (400)</p>";
        echo "<p>Response: <pre>" . print_r($data, true) . "</pre></p>";
    } else {
        echo "<p class='error'>❌ Unexpected response</p>";
        echo "<p>Response: <pre>$response</pre></p>";
    }
}

echo "</div>";

// Check if Razorpay SDK is available
echo "<div class='box'><h2>3. Razorpay PHP SDK</h2>";
$vendorPath = __DIR__ . '/vendor/razorpay/razorpay/src/Api.php';
if (file_exists($vendorPath)) {
    echo "<p class='success'>✅ Razorpay PHP SDK is installed</p>";
} else {
    echo "<p class='error'>❌ Razorpay PHP SDK not found</p>";
    echo "<p>Run: <code>composer require razorpay/razorpay</code></p>";
}
echo "</div>";

// Recommendations
echo "<div class='box'><h2>4. Common Issues & Solutions</h2>
<ol>
    <li><strong>\"Payment initialization failed\"</strong> - Usually means:
        <ul>
            <li>Credentials not loaded (run: php artisan config:clear)</li>
            <li>Cart is empty</li>
            <li>Session expired</li>
            <li>JavaScript error on frontend</li>
        </ul>
    </li>
    <li><strong>Check browser console (F12)</strong> for JavaScript errors</li>
    <li><strong>Check Laravel logs:</strong> storage/logs/laravel.log</li>
    <li><strong>Ensure HTTPS</strong> in production (Razorpay requirement)</li>
    <li><strong>Test mode first:</strong> Use rzp_test_... keys before going live</li>
</ol>
</div>";

echo "</body></html>";

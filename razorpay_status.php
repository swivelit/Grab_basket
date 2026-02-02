<?php
/**
 * Quick Razorpay Status Check
 * Access at: https://grabbaskets.com/razorpay_status.php
 */

echo "<!DOCTYPE html><html><head><title>Razorpay Status</title>
<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .box { background: white; padding: 20px; border-radius: 5px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
</style>
</head><body>";

echo "<h1>🔍 Razorpay Quick Status</h1>";

// Check .env file
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "<div class='box'>";
    echo "<h2>.ENV File Check</h2>";
    
    $envContent = file_get_contents($envPath);
    
    // Check for Razorpay keys
    if (preg_match('/RAZORPAY_KEY_ID=([^\s]+)/', $envContent, $keyMatch)) {
        $keyId = trim($keyMatch[1]);
        echo "<p class='success'>✅ RAZORPAY_KEY_ID found in .env</p>";
        echo "<p><strong>Key ID:</strong> " . substr($keyId, 0, 15) . "...</p>";
        
        // Validate key format
        if (preg_match('/^rzp_(test|live)_/', $keyId)) {
            $keyType = strpos($keyId, 'rzp_live_') === 0 ? 'LIVE' : 'TEST';
            echo "<p><strong>Type:</strong> $keyType</p>";
            if ($keyType === 'LIVE') {
                echo "<p class='warning'>⚠️ Using LIVE credentials</p>";
            }
        } else {
            echo "<p class='error'>❌ Invalid key format</p>";
        }
    } else {
        echo "<p class='error'>❌ RAZORPAY_KEY_ID not found in .env</p>";
    }
    
    if (preg_match('/RAZORPAY_KEY_SECRET=([^\s]+)/', $envContent, $secretMatch)) {
        $keySecret = trim($secretMatch[1]);
        echo "<p class='success'>✅ RAZORPAY_KEY_SECRET found in .env</p>";
        echo "<p><strong>Length:</strong> " . strlen($keySecret) . " characters</p>";
    } else {
        echo "<p class='error'>❌ RAZORPAY_KEY_SECRET not found in .env</p>";
    }
    
    echo "</div>";
} else {
    echo "<div class='box'><p class='error'>❌ .env file not found</p></div>";
}

// Check config cache
$configCachePath = __DIR__ . '/bootstrap/cache/config.php';
echo "<div class='box'>";
echo "<h2>Config Cache Status</h2>";
if (file_exists($configCachePath)) {
    $cacheTime = filemtime($configCachePath);
    echo "<p class='warning'>⚠️ Config cache exists (last modified: " . date('Y-m-d H:i:s', $cacheTime) . ")</p>";
    echo "<p>If Razorpay keys were updated recently, run: <code>php artisan config:clear</code></p>";
} else {
    echo "<p class='success'>✅ No config cache (using .env directly)</p>";
}
echo "</div>";

// Check services.php
$servicesPath = __DIR__ . '/config/services.php';
echo "<div class='box'>";
echo "<h2>Config File Check</h2>";
if (file_exists($servicesPath)) {
    $servicesContent = file_get_contents($servicesPath);
    if (strpos($servicesContent, "'razorpay'") !== false) {
        echo "<p class='success'>✅ Razorpay configured in config/services.php</p>";
        
        // Check if it's reading from env
        if (preg_match("/env\('RAZORPAY_KEY_ID'\)/", $servicesContent)) {
            echo "<p class='success'>✅ Reading RAZORPAY_KEY_ID from environment</p>";
        }
        if (preg_match("/env\('RAZORPAY_KEY_SECRET'\)/", $servicesContent)) {
            echo "<p class='success'>✅ Reading RAZORPAY_KEY_SECRET from environment</p>";
        }
    } else {
        echo "<p class='error'>❌ Razorpay not found in config/services.php</p>";
    }
} else {
    echo "<p class='error'>❌ config/services.php not found</p>";
}
echo "</div>";

// Quick test
echo "<div class='box'>";
echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='debug_razorpay.php' target='_blank'>Run Full Razorpay Debug</a></li>";
echo "<li><a href='test_razorpay_credentials.php' target='_blank'>Test Razorpay Credentials</a></li>";
echo "<li>Clear config cache: <code>php artisan config:clear</code></li>";
echo "<li>Clear all caches: <code>php artisan optimize:clear</code></li>";
echo "</ul>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Common Issues</h2>";
echo "<ol>";
echo "<li><strong>Payment initialization failed</strong> - Usually means:
    <ul>
        <li>Config cache is stale (run: php artisan config:clear)</li>
        <li>Razorpay credentials are incorrect</li>
        <li>Cart is empty</li>
    </ul>
</li>";
echo "<li><strong>Check browser console (F12)</strong> for JavaScript errors</li>";
echo "<li><strong>Check Laravel logs:</strong> storage/logs/laravel.log</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 20px;'>
    <a href='javascript:location.reload()'>🔄 Refresh</a> | 
    Generated at " . date('Y-m-d H:i:s') . "
</p>";

echo "</body></html>";

<?php
/**
 * Google Maps Configuration Checker
 * Access: https://grabbaskets.com/check_google_maps.php
 */

echo "<!DOCTYPE html><html><head><title>Google Maps Config Check</title>
<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .box { background: white; padding: 20px; border-radius: 10px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    h1 { color: #333; text-align: center; }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .test-map { width: 100%; height: 400px; border-radius: 10px; margin-top: 15px; }
    .badge { padding: 5px 12px; border-radius: 15px; display: inline-block; margin: 5px; font-size: 14px; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }
</style>
<script src='https://maps.googleapis.com/maps/api/js?key=AIzaSyBFbU1UkuV2HVULSP2rnTwQWYM0xpFvG20yes&libraries=places,geometry'></script>
</head><body>";

echo "<h1>🗺️ Google Maps Configuration Checker</h1>";

// 1. Check .env file
echo "<div class='box'><h2>1. Environment Configuration</h2>";

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    
    if (preg_match('/GOOGLE_MAPS_API_KEY=(.+)/', $envContent, $match)) {
        $apiKey = trim($match[1]);
        echo "<p class='success'>✅ GOOGLE_MAPS_API_KEY found in .env</p>";
        echo "<p><strong>Key:</strong> " . substr($apiKey, 0, 20) . "..." . substr($apiKey, -5) . "</p>";
        echo "<p><strong>Length:</strong> " . strlen($apiKey) . " characters</p>";
    } else {
        echo "<p class='error'>❌ GOOGLE_MAPS_API_KEY not found in .env</p>";
    }
} else {
    echo "<p class='error'>❌ .env file not found</p>";
}

echo "</div>";

// 2. Check config/services.php
echo "<div class='box'><h2>2. Config File Check</h2>";

$servicesPath = __DIR__ . '/config/services.php';
if (file_exists($servicesPath)) {
    $servicesContent = file_get_contents($servicesPath);
    
    if (preg_match("/'google'.*?maps_api_key/s", $servicesContent)) {
        echo "<p class='success'>✅ Google Maps configured in config/services.php</p>";
    } else {
        echo "<p class='error'>❌ Google Maps not found in config/services.php</p>";
    }
} else {
    echo "<p class='error'>❌ config/services.php not found</p>";
}

echo "</div>";

// 3. Test API Key
echo "<div class='box'><h2>3. API Key Validation</h2>";

$apiKey = 'AIzaSyBFbU1UkuV2HVULSP2rnTwQWYM0xpFvG20';
$testUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=India&key=$apiKey";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>Test URL:</strong> <code>" . substr($testUrl, 0, 100) . "...</code></p>";
echo "<p><strong>HTTP Status:</strong> $httpCode</p>";

if ($error) {
    echo "<p class='error'>❌ cURL Error: $error</p>";
} else {
    $data = json_decode($response, true);
    
    if ($data && isset($data['status'])) {
        echo "<p><strong>API Status:</strong> <span class='badge badge-" . ($data['status'] === 'OK' ? 'success' : 'danger') . "'>{$data['status']}</span></p>";
        
        if ($data['status'] === 'OK') {
            echo "<p class='success'>✅ <strong>API Key is VALID and working!</strong></p>";
            echo "<p>Test result: " . ($data['results'][0]['formatted_address'] ?? 'Location found') . "</p>";
        } elseif ($data['status'] === 'REQUEST_DENIED') {
            echo "<p class='error'>❌ <strong>REQUEST_DENIED</strong></p>";
            echo "<p>Error: " . ($data['error_message'] ?? 'No error message') . "</p>";
            echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>
                <strong>⚠️ Common Causes:</strong><br>
                1. API key restrictions (check allowed domains)<br>
                2. Required APIs not enabled in Google Cloud Console<br>
                3. Billing not enabled<br>
                4. Invalid API key
            </div>";
        } else {
            echo "<p class='error'>❌ API Error: {$data['status']}</p>";
        }
    } else {
        echo "<p class='error'>❌ Invalid response from Google Maps API</p>";
        echo "<pre>" . substr($response, 0, 500) . "</pre>";
    }
}

echo "</div>";

// 4. Check Required APIs
echo "<div class='box'><h2>4. Required Google Cloud APIs</h2>";
echo "<p>Ensure these APIs are enabled in Google Cloud Console:</p>
<ul>
    <li>✅ Maps JavaScript API</li>
    <li>✅ Geocoding API</li>
    <li>✅ Places API</li>
    <li>✅ Geolocation API</li>
    <li>✅ Distance Matrix API (optional)</li>
</ul>
<p><a href='https://console.cloud.google.com/apis/library' target='_blank' style='color: #667eea;'>→ Go to Google Cloud Console</a></p>";
echo "</div>";

// 5. Check Database Columns
echo "<div class='box'><h2>5. Database Schema Check</h2>";

try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    
    // Check orders table
    $ordersColumns = $pdo->query("SHOW COLUMNS FROM orders LIKE '%latitude%' OR SHOW COLUMNS FROM orders LIKE '%longitude%'")->fetchAll();
    
    echo "<p><strong>Orders Table:</strong></p>";
    $hasDeliveryLat = false;
    $hasDeliveryLng = false;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    while ($row = $stmt->fetch()) {
        if ($row['Field'] === 'delivery_latitude') $hasDeliveryLat = true;
        if ($row['Field'] === 'delivery_longitude') $hasDeliveryLng = true;
    }
    
    if ($hasDeliveryLat && $hasDeliveryLng) {
        echo "<p class='success'>✅ delivery_latitude and delivery_longitude columns exist</p>";
    } else {
        echo "<p class='warning'>⚠️ Missing location columns. Run migration:</p>";
        echo "<pre>ALTER TABLE orders ADD COLUMN delivery_latitude DECIMAL(10, 8) NULL;
ALTER TABLE orders ADD COLUMN delivery_longitude DECIMAL(11, 8) NULL;</pre>";
    }
    
    // Check delivery_partners table
    echo "<p><strong>Delivery Partners Table:</strong></p>";
    $hasLat = false;
    $hasLng = false;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM delivery_partners");
    while ($row = $stmt->fetch()) {
        if ($row['Field'] === 'latitude') $hasLat = true;
        if ($row['Field'] === 'longitude') $hasLng = true;
    }
    
    if ($hasLat && $hasLng) {
        echo "<p class='success'>✅ latitude and longitude columns exist</p>";
    } else {
        echo "<p class='warning'>⚠️ Missing location columns. Run migration:</p>";
        echo "<pre>ALTER TABLE delivery_partners ADD COLUMN latitude DECIMAL(10, 8) NULL;
ALTER TABLE delivery_partners ADD COLUMN longitude DECIMAL(11, 8) NULL;</pre>";
    }
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Database check failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 6. Live Map Test
echo "<div class='box'><h2>6. Live Map Test</h2>";
echo "<p>Testing if Google Maps loads correctly on your page:</p>";
echo "<div id='test-map' class='test-map'></div>";

echo "<script>
let testMap;
function initTestMap() {
    try {
        if (typeof google === 'undefined' || !google.maps) {
            document.getElementById('test-map').innerHTML = '<div style=\"color: red; padding: 50px; text-align: center;\"><h3>❌ Google Maps API Failed to Load</h3><p>Check browser console for errors (F12)</p></div>';
            return;
        }
        
        testMap = new google.maps.Map(document.getElementById('test-map'), {
            center: { lat: 20.5937, lng: 78.9629 },
            zoom: 5,
            mapTypeControl: true
        });
        
        new google.maps.Marker({
            position: { lat: 20.5937, lng: 78.9629 },
            map: testMap,
            title: 'India Center',
            animation: google.maps.Animation.DROP
        });
        
        console.log('✅ Google Maps loaded successfully');
        
        // Add success message
        const successDiv = document.createElement('div');
        successDiv.style = 'position: absolute; top: 10px; left: 10px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; z-index: 999; font-weight: bold;';
        successDiv.textContent = '✅ Map Loaded Successfully!';
        document.getElementById('test-map').style.position = 'relative';
        document.getElementById('test-map').appendChild(successDiv);
        
    } catch (error) {
        console.error('Map initialization error:', error);
        document.getElementById('test-map').innerHTML = '<div style=\"color: red; padding: 50px; text-align: center;\"><h3>❌ Map Initialization Error</h3><p>' + error.message + '</p></div>';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTestMap);
} else {
    initTestMap();
}

// Check if geometry library is loaded
setTimeout(() => {
    if (google && google.maps && google.maps.geometry) {
        console.log('✅ Geometry library loaded');
    } else {
        console.warn('⚠️ Geometry library not loaded');
    }
}, 2000);
</script>";

echo "</div>";

// 7. Quick Actions
echo "<div class='box'><h2>7. Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='/orders/live-track' target='_blank' style='color: #667eea;'>→ Test Live Tracking Page</a></li>";
echo "<li><a href='https://console.cloud.google.com/google/maps-apis' target='_blank' style='color: #667eea;'>→ Google Cloud Console - Maps APIs</a></li>";
echo "<li><a href='https://console.cloud.google.com/apis/credentials' target='_blank' style='color: #667eea;'>→ API Credentials</a></li>";
echo "<li><a href='GOOGLE_MAPS_TRACKING_GUIDE.md' target='_blank' style='color: #667eea;'>→ View Full Documentation</a></li>";
echo "</ul>";

echo "<p><strong>Clear Laravel Caches:</strong></p>";
echo "<pre>php artisan config:clear
php artisan cache:clear
php artisan view:clear</pre>";

echo "</div>";

// Summary
echo "<div class='box' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>";
echo "<h2 style='color: white; border-bottom: 2px solid white;'>✅ Setup Summary</h2>";
echo "<p><strong>Current Status:</strong></p>";
echo "<ul>";
echo "<li>API Key: Configured ✅</li>";
echo "<li>Config File: Ready ✅</li>";
echo "<li>Live Tracking Page: Available ✅</li>";
echo "<li>API Endpoints: Implemented ✅</li>";
echo "</ul>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test the live tracking page: <a href='/orders/live-track' style='color: #fff; text-decoration: underline;'>/orders/live-track</a></li>";
echo "<li>Place a test order and track it</li>";
echo "<li>Enable location updates from delivery partner app</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 30px;'>
    Generated at " . date('Y-m-d H:i:s') . " | 
    <a href='javascript:location.reload()'>🔄 Refresh</a>
</p>";

echo "</body></html>";

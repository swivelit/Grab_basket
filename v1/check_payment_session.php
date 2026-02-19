<?php
/**
 * Payment & Session Diagnostic Tool
 * Check payment verification flow and session configuration
 * Access: https://grabbaskets.com/check_payment_session.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Payment & Session Check</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
h1 { color: #333; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; }
h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
table th { background: #f8f9fa; font-weight: 600; }
</style></head><body>";

echo "<h1>🔍 Payment & Session Diagnostic</h1>";

// 1. Check PHP Session Configuration
echo "<div class='box'><h2>📋 1. PHP Session Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$sessionSettings = [
    'session.save_handler' => session_save_handler(),
    'session.save_path' => session_save_path(),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite') ?: 'Not set',
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime') . ' seconds',
];

foreach ($sessionSettings as $key => $value) {
    $status = '✅';
    $class = 'success';
    
    if ($key === 'session.cookie_secure' && $value == '0' && $_SERVER['HTTPS'] ?? false) {
        $status = '⚠️ Should be 1 on HTTPS';
        $class = 'warning';
    }
    
    echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td><td class='{$class}'>{$status}</td></tr>";
}

echo "</table></div>";

// 2. Check Laravel .env Configuration
echo "<div class='box'><h2>⚙️ 2. Laravel Configuration (.env)</h2>";
$envPath = __DIR__ . '/.env';

if (file_exists($envPath)) {
    echo "<p class='success'>✅ .env file exists</p>";
    $envContent = file_get_contents($envPath);
    
    $checks = [
        'APP_ENV' => '/APP_ENV=(.+)/',
        'APP_DEBUG' => '/APP_DEBUG=(.+)/',
        'APP_URL' => '/APP_URL=(.+)/',
        'SESSION_DRIVER' => '/SESSION_DRIVER=(.+)/',
        'SESSION_LIFETIME' => '/SESSION_LIFETIME=(.+)/',
        'SESSION_SECURE_COOKIE' => '/SESSION_SECURE_COOKIE=(.+)/',
        'RAZORPAY_KEY_ID' => '/RAZORPAY_KEY_ID=(.+)/',
        'RAZORPAY_SECRET' => '/RAZORPAY_SECRET=(.+)/',
    ];
    
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    foreach ($checks as $key => $pattern) {
        if (preg_match($pattern, $envContent, $match)) {
            $value = trim($match[1]);
            if (strpos($key, 'SECRET') !== false || strpos($key, 'KEY') !== false) {
                $displayValue = substr($value, 0, 15) . '...';
            } else {
                $displayValue = $value;
            }
            
            $status = '✅';
            $class = 'success';
            
            if ($key === 'APP_DEBUG' && $value === 'true') {
                $status = '⚠️ Should be false in production';
                $class = 'warning';
            }
            if ($key === 'SESSION_SECURE_COOKIE' && $value !== 'true' && ($_SERVER['HTTPS'] ?? false)) {
                $status = '⚠️ Should be true on HTTPS';
                $class = 'warning';
            }
            if ($key === 'APP_URL' && strpos($value, 'http://') === 0 && ($_SERVER['HTTPS'] ?? false)) {
                $status = '⚠️ Should use https://';
                $class = 'warning';
            }
            
            echo "<tr><td>{$key}</td><td>{$displayValue}</td><td class='{$class}'>{$status}</td></tr>";
        } else {
            echo "<tr><td>{$key}</td><td class='error'>Not set</td><td class='error'>❌</td></tr>";
        }
    }
    
    echo "</table>";
} else {
    echo "<p class='error'>❌ .env file not found</p>";
}

echo "</div>";

// 3. Check Session Functionality
echo "<div class='box'><h2>🔐 3. Session Functionality Test</h2>";

if (!session_id()) {
    session_start();
}

$_SESSION['test_key'] = 'test_value_' . time();

if (isset($_SESSION['test_key'])) {
    echo "<p class='success'>✅ Session is working</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Test Value:</strong> " . $_SESSION['test_key'] . "</p>";
} else {
    echo "<p class='error'>❌ Session not working</p>";
}

echo "</div>";

// 4. Check Database Session Table
echo "<div class='box'><h2>💾 4. Database Session Storage</h2>";

try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $sessionDriver = config('session.driver');
    echo "<p><strong>Session Driver:</strong> {$sessionDriver}</p>";
    
    if ($sessionDriver === 'database') {
        $table = config('session.table', 'sessions');
        $connection = config('session.connection');
        
        echo "<p><strong>Session Table:</strong> {$table}</p>";
        
        $db = DB::connection($connection);
        
        // Check if table exists
        $tableExists = DB::select("SHOW TABLES LIKE '{$table}'");
        
        if ($tableExists) {
            echo "<p class='success'>✅ Session table exists</p>";
            
            // Count sessions
            $count = $db->table($table)->count();
            echo "<p><strong>Active Sessions:</strong> {$count}</p>";
            
            // Check table structure
            $columns = DB::select("DESCRIBE {$table}");
            echo "<details><summary>View Table Structure</summary><pre>";
            foreach ($columns as $col) {
                echo "{$col->Field} - {$col->Type}\n";
            }
            echo "</pre></details>";
        } else {
            echo "<p class='error'>❌ Session table does not exist</p>";
            echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Fix:</strong> Run: <code>php artisan session:table && php artisan migrate</code>";
            echo "</div>";
        }
    } else {
        echo "<p class='warning'>⚠️ Not using database sessions (using {$sessionDriver})</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// 5. Check HTTPS and Security
echo "<div class='box'><h2>🔒 5. HTTPS & Security</h2>";

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$protocol = $isHttps ? 'https://' : 'http://';
$currentUrl = $protocol . $_SERVER['HTTP_HOST'];

if ($isHttps) {
    echo "<p class='success'>✅ HTTPS is enabled</p>";
    echo "<p><strong>Current URL:</strong> {$currentUrl}</p>";
} else {
    echo "<p class='error'>❌ HTTPS is NOT enabled</p>";
    echo "<p><strong>Current URL:</strong> {$currentUrl}</p>";
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Warning:</strong> Payment processing and sessions require HTTPS in production";
    echo "</div>";
}

echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";

echo "</div>";

// 6. Check Razorpay Configuration
echo "<div class='box'><h2>💳 6. Razorpay Configuration</h2>";

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    
    if (preg_match('/RAZORPAY_KEY_ID=(.+)/', $envContent, $match)) {
        $key = trim($match[1]);
        echo "<p class='success'>✅ Razorpay Key configured</p>";
        echo "<p><strong>Key:</strong> " . substr($key, 0, 15) . "...</p>";
    } else {
        echo "<p class='error'>❌ Razorpay Key not configured</p>";
    }
    
    if (preg_match('/RAZORPAY_SECRET=(.+)/', $envContent, $match)) {
        $secret = trim($match[1]);
        echo "<p class='success'>✅ Razorpay Secret configured</p>";
        echo "<p><strong>Secret:</strong> " . substr($secret, 0, 10) . "...</p>";
    } else {
        echo "<p class='error'>❌ Razorpay Secret not configured</p>";
    }
}

echo "</div>";

// 7. Recommendations
echo "<div class='box'><h2>💡 7. Recommendations</h2>";
echo "<ul>";

if (!$isHttps) {
    echo "<li class='error'>❌ <strong>Enable HTTPS</strong> - Required for secure payments and sessions</li>";
}

if ($sessionDriver !== 'database') {
    echo "<li class='warning'>⚠️ Consider using database session driver for better reliability</li>";
}

echo "<li>✅ Clear caches after config changes: <code>php artisan config:clear && php artisan cache:clear</code></li>";
echo "<li>✅ Make sure storage/framework/sessions directory is writable</li>";
echo "<li>✅ Verify .env SESSION_LIFETIME is adequate (current: " . (ini_get('session.gc_maxlifetime') / 60) . " minutes)</li>";
echo "</ul>";

echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 20px;'>
    Generated at " . date('Y-m-d H:i:s') . " | 
    <a href='javascript:location.reload()' style='color: #667eea;'>🔄 Refresh</a>
</p>";

echo "</body></html>";

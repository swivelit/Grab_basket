<?php
// Hostinger Debug File - Upload this as debug.php in public_html/

echo "<h1>Hostinger Laravel Deployment Debug</h1>";

echo "<h2>1. File Path Information</h2>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "<br>";

echo "<h2>2. Laravel File Structure Check</h2>";

$checks = [
    'vendor/autoload.php' => __DIR__ . '/../vendor/autoload.php',
    'bootstrap/app.php' => __DIR__ . '/../bootstrap/app.php',
    '.env file' => __DIR__ . '/../.env',
    'storage directory' => __DIR__ . '/../storage',
    'app directory' => __DIR__ . '/../app',
    'config directory' => __DIR__ . '/../config',
];

foreach ($checks as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    $color = $exists ? 'green' : 'red';
    echo "<div style='color: $color;'><strong>$name:</strong> $status ($path)</div>";
}

echo "<h2>3. PHP Information</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h2>4. Laravel Bootstrap Test</h2>";
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "<div style='color: green;'>✅ Composer autoloader loaded successfully</div>";
        
        if (file_exists(__DIR__ . '/../bootstrap/app.php')) {
            $app = require_once __DIR__ . '/../bootstrap/app.php';
            echo "<div style='color: green;'>✅ Laravel application bootstrapped successfully</div>";
            echo "<strong>Laravel Version:</strong> " . app()->version() . "<br>";
        } else {
            echo "<div style='color: red;'>❌ Cannot find bootstrap/app.php</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ Cannot find vendor/autoload.php</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Laravel Bootstrap Error: " . $e->getMessage() . "</div>";
}

echo "<h2>5. Environment Check</h2>";
if (file_exists(__DIR__ . '/../.env')) {
    echo "<div style='color: green;'>✅ .env file exists</div>";
    
    // Check if we can read basic config
    try {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        if (strpos($envContent, 'APP_NAME') !== false) {
            echo "<div style='color: green;'>✅ .env file contains APP_NAME</div>";
        }
        if (strpos($envContent, 'DB_DATABASE') !== false) {
            echo "<div style='color: green;'>✅ .env file contains database config</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: orange;'>⚠️ Cannot read .env file contents</div>";
    }
} else {
    echo "<div style='color: red;'>❌ .env file missing</div>";
}

echo "<h2>6. Directory Permissions</h2>";
$permissionChecks = [
    'storage' => __DIR__ . '/../storage',
    'bootstrap/cache' => __DIR__ . '/../bootstrap/cache',
];

foreach ($permissionChecks as $name => $path) {
    if (file_exists($path)) {
        $writable = is_writable($path);
        $status = $writable ? '✅ WRITABLE' : '❌ NOT WRITABLE';
        $color = $writable ? 'green' : 'red';
        echo "<div style='color: $color;'><strong>$name:</strong> $status</div>";
    }
}

echo "<h2>7. Recommendations</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #007acc;'>";
echo "<strong>If you see this page, your files are in the correct location!</strong><br><br>";
echo "Next steps:<br>";
echo "1. Replace this debug.php with your Laravel index.php<br>";
echo "2. Ensure all files marked as ✅ EXISTS are present<br>";
echo "3. Fix any permission issues marked as ❌ NOT WRITABLE<br>";
echo "4. Clear Hostinger cache and try accessing your domain again<br>";
echo "</div>";

echo "<hr>";
echo "<small>Debug completed at: " . date('Y-m-d H:i:s') . "</small>";
?>
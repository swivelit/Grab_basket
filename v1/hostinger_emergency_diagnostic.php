<?php
// EMERGENCY HOSTINGER DIAGNOSTIC - Upload this as diagnostic.php to public_html/
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Hostinger Emergency Diagnostic</title>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} .info{background:#f0f8ff;padding:15px;margin:10px 0;border-left:4px solid #007acc;}</style></head><body>";

echo "<h1>🔧 Hostinger Laravel Emergency Diagnostic</h1>";

// 1. Basic PHP Info
echo "<h2>1. Server Environment</h2>";
echo "<strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "<strong>Current Directory:</strong> " . __DIR__ . "<br>";
echo "<strong>Script Path:</strong> " . __FILE__ . "<br>";

// 2. File Structure Analysis
echo "<h2>2. File Structure Check</h2>";

$currentDir = __DIR__;
echo "<div class='info'><strong>Scanning from:</strong> $currentDir</div>";

// Check current directory contents
echo "<h3>Files in current directory (public_html):</h3>";
$files = scandir($currentDir);
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        $fullPath = $currentDir . '/' . $file;
        $type = is_dir($fullPath) ? 'DIR' : 'FILE';
        $size = is_file($fullPath) ? ' (' . filesize($fullPath) . ' bytes)' : '';
        echo "- $file [$type]$size<br>";
    }
}

// Check Laravel structure
echo "<h3>Laravel Structure Check:</h3>";
$laravelChecks = [
    'index.php' => $currentDir . '/index.php',
    '.htaccess' => $currentDir . '/.htaccess',
    'Laravel autoload' => $currentDir . '/../vendor/autoload.php',
    'Laravel bootstrap' => $currentDir . '/../bootstrap/app.php',
    'Environment file' => $currentDir . '/../.env',
    'App directory' => $currentDir . '/../app',
    'Config directory' => $currentDir . '/../config',
    'Storage directory' => $currentDir . '/../storage',
];

foreach($laravelChecks as $name => $path) {
    $exists = file_exists($path);
    $class = $exists ? 'ok' : 'error';
    $status = $exists ? '✓ Found' : '✗ Missing';
    echo "<span class='$class'><strong>$name:</strong> $status</span> ($path)<br>";
}

// 3. Laravel Bootstrap Test
echo "<h2>3. Laravel Bootstrap Test</h2>";
try {
    // Test autoloader
    if (file_exists($currentDir . '/../vendor/autoload.php')) {
        require_once $currentDir . '/../vendor/autoload.php';
        echo "<span class='ok'>✓ Autoloader loaded successfully</span><br>";
        
        // Test Laravel bootstrap
        if (file_exists($currentDir . '/../bootstrap/app.php')) {
            $app = require_once $currentDir . '/../bootstrap/app.php';
            echo "<span class='ok'>✓ Laravel application created successfully</span><br>";
            
            // Get Laravel version
            if (method_exists($app, 'version')) {
                echo "<strong>Laravel Version:</strong> " . $app->version() . "<br>";
            }
            
            // Test basic config
            try {
                if (function_exists('config')) {
                    echo "<strong>App Name:</strong> " . (config('app.name') ?? 'Not set') . "<br>";
                    echo "<strong>App URL:</strong> " . (config('app.url') ?? 'Not set') . "<br>";
                    echo "<strong>Environment:</strong> " . (config('app.env') ?? 'Not set') . "<br>";
                }
            } catch (Exception $e) {
                echo "<span class='warning'>⚠ Could not load config: " . $e->getMessage() . "</span><br>";
            }
            
        } else {
            echo "<span class='error'>✗ Laravel bootstrap file missing</span><br>";
        }
    } else {
        echo "<span class='error'>✗ Composer autoloader missing</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Bootstrap Error: " . $e->getMessage() . "</span><br>";
    echo "<span class='error'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</span><br>";
}

// 4. Permission Check
echo "<h2>4. Directory Permissions</h2>";
$permissionChecks = [
    'Current directory' => $currentDir,
    'Storage directory' => $currentDir . '/../storage',
    'Bootstrap cache' => $currentDir . '/../bootstrap/cache',
];

foreach($permissionChecks as $name => $path) {
    if (file_exists($path)) {
        $readable = is_readable($path);
        $writable = is_writable($path);
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        
        echo "<strong>$name:</strong> ";
        if ($readable && $writable) {
            echo "<span class='ok'>✓ Read/Write OK</span>";
        } elseif ($readable) {
            echo "<span class='warning'>⚠ Read Only</span>";
        } else {
            echo "<span class='error'>✗ No Access</span>";
        }
        echo " (Permissions: $perms)<br>";
    } else {
        echo "<strong>$name:</strong> <span class='error'>✗ Not Found</span><br>";
    }
}

// 5. Environment File Check
echo "<h2>5. Environment Configuration</h2>";
$envPath = $currentDir . '/../.env';
if (file_exists($envPath)) {
    echo "<span class='ok'>✓ .env file exists</span><br>";
    
    try {
        $envContent = file_get_contents($envPath);
        $envLines = explode("\n", $envContent);
        
        $importantKeys = ['APP_NAME', 'APP_URL', 'APP_ENV', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];
        echo "<strong>Key Environment Variables:</strong><br>";
        
        foreach($importantKeys as $key) {
            $found = false;
            foreach($envLines as $line) {
                if (strpos($line, $key . '=') === 0) {
                    $value = substr($line, strlen($key) + 1);
                    // Hide sensitive values
                    if (strpos($key, 'PASSWORD') !== false || strpos($key, 'KEY') !== false) {
                        $value = str_repeat('*', strlen($value));
                    }
                    echo "- $key = $value<br>";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "- <span class='warning'>$key not found</span><br>";
            }
        }
    } catch (Exception $e) {
        echo "<span class='error'>Could not read .env file: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span class='error'>✗ .env file missing</span><br>";
}

// 6. Recommendations
echo "<h2>6. 🎯 Action Plan</h2>";
echo "<div class='info'>";

$issues = [];
if (!file_exists($currentDir . '/index.php')) {
    $issues[] = "Upload Laravel's public/index.php to this directory";
}
if (!file_exists($currentDir . '/.htaccess')) {
    $issues[] = "Upload the optimized .htaccess file to this directory";
}
if (!file_exists($currentDir . '/../vendor/autoload.php')) {
    $issues[] = "Upload vendor/ directory one level up from public_html";
}
if (!file_exists($currentDir . '/../.env')) {
    $issues[] = "Create .env file one level up from public_html";
}

if (empty($issues)) {
    echo "<strong>🎉 GREAT NEWS!</strong> All required files are in place!<br><br>";
    echo "<strong>Next steps:</strong><br>";
    echo "1. Delete this diagnostic.php file<br>";
    echo "2. Replace any test.html with your actual Laravel index.php<br>";
    echo "3. Clear Hostinger cache (Website → Cache → Purge Cache)<br>";
    echo "4. Test your domain again<br>";
} else {
    echo "<strong>⚠️ ISSUES FOUND:</strong><br>";
    foreach($issues as $issue) {
        echo "• $issue<br>";
    }
}

echo "</div>";

echo "<hr><small>Diagnostic completed: " . date('Y-m-d H:i:s') . "</small>";
echo "</body></html>";
?>
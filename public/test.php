<?php
// Simple PHP test file to check if basic PHP execution works
echo "PHP is working - " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . $_SERVER['SERVER_NAME'] ?? 'unknown' . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Test if we can include Laravel bootstrap
try {
    $appPath = __DIR__ . '/../bootstrap/app.php';
    if (file_exists($appPath)) {
        echo "Bootstrap file exists\n";
        require_once $appPath;
        echo "Bootstrap loaded successfully\n";
    } else {
        echo "Bootstrap file not found\n";
    }
} catch (Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
}
?>
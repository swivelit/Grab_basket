<?php
// Emergency fallback for debugging 500 errors
// This file will help us understand what's happening

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== EMERGENCY DEBUG MODE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . __DIR__ . "\n";

// Check if Laravel files exist
$laravelFiles = [
    'bootstrap/app.php',
    'vendor/autoload.php',
    '.env',
    'artisan'
];

echo "\n=== FILE CHECK ===\n";
foreach ($laravelFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    echo $file . ": " . (file_exists($fullPath) ? "EXISTS" : "MISSING") . "\n";
}

// Try to check .env content
echo "\n=== ENV CHECK ===\n";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    echo "ENV file size: " . strlen($envContent) . " bytes\n";
    
    // Check for problematic lines
    $lines = explode("\n", $envContent);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'AWS_BUCKET') !== false || strpos($line, 'REDIS') !== false) {
            echo "Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo ".env file not found!\n";
}

// Try to bootstrap Laravel
echo "\n=== LARAVEL BOOTSTRAP TEST ===\n";
try {
    $bootstrapPath = __DIR__ . '/../bootstrap/app.php';
    if (file_exists($bootstrapPath)) {
        echo "Attempting Laravel bootstrap...\n";
        
        // Capture any output/errors during bootstrap
        ob_start();
        $app = require_once $bootstrapPath;
        $bootstrapOutput = ob_get_clean();
        
        if ($bootstrapOutput) {
            echo "Bootstrap output: " . $bootstrapOutput . "\n";
        }
        
        echo "Laravel app created successfully!\n";
        echo "App class: " . get_class($app) . "\n";
        
        // Try to boot the application
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        echo "Kernel created: " . get_class($kernel) . "\n";
        
    } else {
        echo "Bootstrap file not found!\n";
    }
} catch (Throwable $e) {
    echo "Bootstrap ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>
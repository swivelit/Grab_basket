<?php
// Simple standalone test - doesn't require Laravel bootstrap
echo "=== QUICK PHP SYNTAX CHECK ===\n\n";

// Check if the routes file has syntax errors
echo "Checking routes/web.php syntax...\n";
$output = [];
$return_var = 0;
exec('php -l routes/web.php 2>&1', $output, $return_var);
if ($return_var === 0) {
    echo "✓ routes/web.php: No syntax errors\n";
} else {
    echo "✗ routes/web.php: SYNTAX ERROR FOUND!\n";
    echo implode("\n", $output) . "\n";
}

echo "\nChecking index.blade.php existence...\n";
if (file_exists('resources/views/index.blade.php')) {
    echo "✓ index.blade.php exists\n";
    echo "  Size: " . filesize('resources/views/index.blade.php') . " bytes\n";
} else {
    echo "✗ index.blade.php NOT FOUND!\n";
}

echo "\nChecking Banner model...\n";
if (file_exists('app/Models/Banner.php')) {
    echo "✓ Banner.php exists\n";
    $output = [];
    exec('php -l app/Models/Banner.php 2>&1', $output, $return_var);
    if ($return_var === 0) {
        echo "✓ Banner.php: No syntax errors\n";
    } else {
        echo "✗ Banner.php: SYNTAX ERROR!\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "✗ Banner.php NOT FOUND!\n";
}

echo "\nChecking ProductImportExportController.php...\n";
$output = [];
exec('php -l app/Http/Controllers/ProductImportExportController.php 2>&1', $output, $return_var);
if ($return_var === 0) {
    echo "✓ ProductImportExportController.php: No syntax errors\n";
} else {
    echo "✗ ProductImportExportController.php: SYNTAX ERROR!\n";
    echo implode("\n", $output) . "\n";
}

echo "\n=== SYNTAX CHECK COMPLETE ===\n";

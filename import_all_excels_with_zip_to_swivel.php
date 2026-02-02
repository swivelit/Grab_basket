<?php

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

// Usage: php artisan tinker --execute="require base_path('import_all_excels_with_zip_to_swivel.php');"

$seller = User::where('email', 'swivel.training@gmail.com')->where('role', 'seller')->first();
if (!$seller) {
    echo "❌ Seller not found: swivel.training@gmail.com\n";
    return;
}

$excelDir = base_path('Excel');
$zipFile = base_path('soap.zip'); // Change this if your ZIP file has a different name
if (!is_dir($excelDir)) {
    echo "❌ Excel directory not found: $excelDir\n";
    return;
}
if (!file_exists($zipFile)) {
    echo "❌ ZIP file not found: $zipFile\n";
    return;
}

$excelFiles = glob($excelDir . '/*.xlsx');
if (!$excelFiles) {
    echo "❌ No Excel files found in $excelDir\n";
    return;
}

foreach ($excelFiles as $excelFile) {
    echo "\n--- Importing $excelFile with images from $zipFile ---\n";
    $import = new \App\Imports\ProductsImport($zipFile, $seller->id);
    try {
        Excel::import($import, $excelFile);
        $imported = $import->getSuccessCount();
        $errors = $import->getErrors();
        echo "✅ Imported $imported products from $excelFile\n";
        if ($errors && count($errors)) {
            echo "Errors:\n" . implode("\n", $errors) . "\n";
        }
    } catch (Throwable $e) {
        echo "❌ Error importing $excelFile: " . $e->getMessage() . "\n";
    }
}

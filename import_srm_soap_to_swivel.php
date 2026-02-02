<?php

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

// Run with: php artisan tinker --execute="require base_path('import_srm_soap_to_swivel.php');"

$seller = User::where('email', 'swivel.training@gmail.com')->where('role', 'seller')->first();
if (!$seller) {
    echo "❌ Seller not found: swivel.training@gmail.com\n";
    return;
}

$excelFile = base_path('srm  soap.xlsx');
$zipFile = base_path('soa.zip');

if (!file_exists($excelFile)) {
    echo "❌ Excel file not found: $excelFile\n";
    return;
}
if (!file_exists($zipFile)) {
    echo "❌ ZIP file not found: $zipFile\n";
    return;
}

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

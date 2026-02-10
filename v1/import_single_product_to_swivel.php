<?php

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

// Usage: php artisan tinker --execute="require base_path('import_single_product_to_swivel.php');"

$seller = User::where('email', 'swivel.training@gmail.com')->where('role', 'seller')->first();
if (!$seller) {
    echo "❌ Seller not found: swivel.training@gmail.com\n";
    return;
}

// Define your product data here. Adjust fields as needed.
$productRow = [
    'name' => 'Sample Soap', // Product name (must match image name in ZIP if assigning image)
    'unique_id' => 'SAMPLE_SOAP_001',
    'price' => 99,
    'description' => 'A sample soap product.',
    // Add other fields as required by your ProductsImport
];

// Path to ZIP file containing images (optional, can be null if not assigning image)
$zipFile = base_path('soap.zip');
if (!file_exists($zipFile)) {
    $zipFile = null;
}

$import = new \App\Imports\ProductsImport($zipFile, $seller->id);
$import->importSingleRow($productRow);

$imported = $import->getSuccessCount();
$errors = $import->getErrors();
echo "✅ Imported $imported product(s)\n";
if ($errors && count($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
}

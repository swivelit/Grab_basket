<?php

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

// Run with: php artisan tinker --execute="require base_path('import_all_excels_to_swivel.php');"

$seller = User::where('email', 'swivel.training@gmail.com')->where('role', 'seller')->first();
if (!$seller) {
    echo "❌ Seller not found: swivel.training@gmail.com\n";
    return;
}

$excelDir = base_path('Excel');
$files = glob($excelDir . '/*.xlsx');
if (!$files) {
    echo "❌ No Excel files found in /Excel/\n";
    return;
}

$count = 0;
foreach ($files as $file) {
    echo "\n📥 Importing: $file\n";
    $import = new \App\Imports\ProductsImport(null, $seller->id);
    try {
        Excel::import($import, $file);
        $imported = $import->getSuccessCount();
        $errors = $import->getErrors();
        echo "✅ Imported $imported products from $file\n";
        if ($errors && count($errors)) {
            echo "Errors:\n" . implode("\n", $errors) . "\n";
        }
        $count += $imported;
    } catch (Throwable $e) {
        echo "❌ Error importing $file: " . $e->getMessage() . "\n";
    }
}
echo "\n🎉 Total products imported: $count\n";

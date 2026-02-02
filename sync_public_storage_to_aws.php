<?php

use Illuminate\Support\Facades\Storage;

// Run with: php artisan tinker --execute="require base_path('sync_public_storage_to_aws.php');"

$localFiles = Storage::disk('public')->allFiles();

$count = 0;
$failed = [];
foreach ($localFiles as $file) {
    $content = Storage::disk('public')->get($file);
    try {
        Storage::disk('r2')->put($file, $content);
        $count++;
        echo "Uploaded: $file\n";
    } catch (Throwable $e) {
        $failed[] = $file;
        echo "Failed: $file - " . $e->getMessage() . "\n";
    }
}
echo "\nTotal uploaded: $count\n";
if (count($failed)) {
    echo "Failed files:\n" . implode("\n", $failed) . "\n";
}

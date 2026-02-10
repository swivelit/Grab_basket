<?php

use Illuminate\Support\Facades\Storage;

// Run with: php artisan tinker --execute="require base_path('sync_public_folder_to_aws.php');"

$publicPath = base_path('public');

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicPath));

$count = 0;
$failed = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $filePath = $file->getPathname();
    $relativePath = ltrim(str_replace($publicPath, '', $filePath), DIRECTORY_SEPARATOR);
    $content = @file_get_contents($filePath);
    if ($content === false) {
        $failed[] = $relativePath;
        echo "Failed to read: $relativePath\n";
        continue;
    }
    try {
        Storage::disk('r2')->put($relativePath, $content);
        $count++;
        echo "Uploaded: $relativePath\n";
    } catch (Throwable $e) {
        $failed[] = $relativePath;
        echo "Failed: $relativePath - " . $e->getMessage() . "\n";
    }
}
echo "\nTotal uploaded: $count\n";
if (count($failed)) {
    echo "Failed files:\n" . implode("\n", $failed) . "\n";
}

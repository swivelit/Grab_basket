<?php

// Import category images from provided ZIP files and assign them to categories (non-destructive)

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

/** @var \Illuminate\Database\Eloquent\Model $model */
/** @var \App\Models\Category $Category */
$Category = app(\App\Models\Category::class);

function slugify_folder(string $name): string {
    // Normalize and slugify folder names for filesystem
    $slug = preg_replace('~[^\\pL\\d]+~u', '-', strtolower(trim($name)));
    $slug = trim($slug, '-');
    $slug = preg_replace('~[^-a-z0-9]+~', '', $slug);
    return $slug ?: 'cat';
}

function ensure_dir(string $path): void {
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

function extract_zip_non_destructive(string $zipPath, string $destDir): array {
    $extractedFiles = [];
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        ensure_dir($destDir);
        // Extract entries if they do not already exist
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];
            if (substr($name, -1) === '/') {
                continue; // directory
            }
            $targetPath = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            $targetDir = dirname($targetPath);
            ensure_dir($targetDir);
            if (!file_exists($targetPath)) {
                $stream = $zip->getStream($name);
                if ($stream) {
                    $out = fopen($targetPath, 'w');
                    if ($out) {
                        while (!feof($stream)) {
                            fwrite($out, fread($stream, 8192));
                        }
                        fclose($out);
                    }
                    fclose($stream);
                }
            }
            $extractedFiles[] = $targetPath;
        }
        $zip->close();
    } else {
        echo "Failed to open ZIP: {$zipPath}\n";
    }
    return $extractedFiles;
}

function find_first_image(string $dir): ?string {
    $allowed = ['jpg','jpeg','png','webp'];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if ($file->isFile()) {
            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                return $file->getPathname();
            }
        }
    }
    return null;
}

// Map each provided ZIP basename to target Category name and default emoji
$zipToCategoryMap = [
    'masala' => ['category' => 'COOKING', 'unique_id' => 'COOK', 'emoji' => '🌶️'],
    'perfume' => ['category' => 'BEAUTY & PERSONAL CARE', 'unique_id' => 'BEA', 'emoji' => '💄'],
    'oral care zip' => ['category' => 'DENTAL CARE', 'unique_id' => 'DEN', 'emoji' => '🦷'],
    'oral care' => ['category' => 'DENTAL CARE', 'unique_id' => 'DEN', 'emoji' => '🦷'],
    'detergent' => ['category' => 'HOME & KITCHEN', 'unique_id' => 'HOM', 'emoji' => '🧼'],
    'soap' => ['category' => 'BEAUTY & PERSONAL CARE', 'unique_id' => 'BEA', 'emoji' => '🧼'],
    'shampoo' => ['category' => 'BEAUTY & PERSONAL CARE', 'unique_id' => 'BEA', 'emoji' => '🧴'],
    'cooking oil' => ['category' => 'COOKING', 'unique_id' => 'COOK', 'emoji' => '🫒'],
    'chocolate' => ['category' => 'GROCERY & FOOD', 'unique_id' => 'GRO', 'emoji' => '🍫'],
    'poojaitem' => ['category' => 'POOJA ITEMS', 'unique_id' => 'POO', 'emoji' => '🪔'],
];

$root = __DIR__;
$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';
$targetBase = $publicDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'categories';
ensure_dir($targetBase);

// Collect zip files from root and from public/asset/images
$zipPaths = [];
foreach (glob($root . DIRECTORY_SEPARATOR . '*.zip') as $z) { $zipPaths[] = $z; }
foreach (glob($publicDir . DIRECTORY_SEPARATOR . 'asset' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . '*.zip') as $z) { $zipPaths[] = $z; }

if (empty($zipPaths)) {
    echo "No ZIP files found to import. Place ZIPs in project root or public/asset/images.\n";
    exit(0);
}

// Normalize duplicates
$zipPaths = array_values(array_unique($zipPaths));

$summary = [];

foreach ($zipPaths as $zipFile) {
    $base = strtolower(pathinfo($zipFile, PATHINFO_FILENAME));
    // Remove trailing duplicates like " (1)"
    $base = preg_replace('/\s*\(\d+\)$/', '', $base);
    $key = trim($base);

    if (!isset($zipToCategoryMap[$key])) {
        // Try fuzzy keys: collapse multiple spaces
        $key2 = preg_replace('/\s+/', ' ', $key);
        if (!isset($zipToCategoryMap[$key2])) {
            echo "Skipping ZIP without mapping: {$zipFile}\n";
            $summary[] = [
                'zip' => basename($zipFile),
                'category' => '-',
                'status' => 'skipped (no mapping)'
            ];
            continue;
        }
        $key = $key2;
    }

    $map = $zipToCategoryMap[$key];
    $catName = $map['category'];
    $uniqueId = $map['unique_id'] ?? strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($catName)), 0, 3)) ?: 'CAT';
    $emoji = $map['emoji'] ?? '🛍️';

    $folderSlug = slugify_folder($key);
    $destDir = $targetBase . DIRECTORY_SEPARATOR . $folderSlug;

    echo "\n=== Processing {$zipFile} → Category: {$catName} ===\n";
    $files = extract_zip_non_destructive($zipFile, $destDir);
    $img = find_first_image($destDir);

    // Create or update category
    /** @var \App\Models\Category $category */
    $category = \App\Models\Category::where('name', $catName)->first();
    $created = false;
    if (!$category) {
        $category = new \App\Models\Category();
        $category->name = $catName;
        $category->unique_id = $uniqueId;
        $category->gender = 'all';
        $created = true;
    }

    if ($img) {
        // Normalize to public path (images/...)
        $relative = 'images/categories/' . $folderSlug . '/' . basename($img);
        // Copy to a stable cover name to avoid random names if desired
        $coverPath = $destDir . DIRECTORY_SEPARATOR . 'cover.' . strtolower(pathinfo($img, PATHINFO_EXTENSION));
        if (!file_exists($coverPath)) {
            @copy($img, $coverPath);
        }
        $relative = 'images/categories/' . $folderSlug . '/' . basename($coverPath);
        $category->image = $relative;
    }

    if (empty($category->emoji)) {
        $category->emoji = $emoji;
    }

    $category->save();

    $summary[] = [
        'zip' => basename($zipFile),
        'category' => $catName,
        'status' => ($created ? 'created' : 'updated') . ($img ? ' + image' : ' (no image found)'),
        'image' => $category->image ?? '-',
    ];

    echo ($created ? "Created" : "Updated") . " category '{$catName}'" . ($img ? " with image {$category->image}" : " (no image found)") . "\n";
}

// Clear caches so UI reflects new images
\Artisan::call('optimize:clear');

echo "\n=== Import Summary ===\n";
foreach ($summary as $row) {
    echo sprintf("%-22s | %-24s | %-26s | %s\n",
        $row['zip'], $row['category'], $row['status'], $row['image'] ?? '-'
    );
}
echo "\nDone. If emojis are missing, you can run: php artisan db:seed --class=Database\\Seeders\\CategoryEmojiSeeder\n";

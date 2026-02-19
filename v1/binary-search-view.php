<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$viewPath = resource_path('views/index.blade.php');
$content = file_get_contents($viewPath);
$lines = explode("\n", $content);
$totalLines = count($lines);

echo "Binary search for problematic line in index.blade.php\n";
echo "Total lines: $totalLines\n\n";

// Try rendering chunks
$chunks = [
    ['start' => 0, 'end' => 2000],
    ['start' => 2000, 'end' => 4000],
    ['start' => 4000, 'end' => 6000],
    ['start' => 6000, 'end' => 8000],
    ['start' => 8000, 'end' => $totalLines],
];

foreach ($chunks as $chunk) {
    $start = $chunk['start'];
    $end = $chunk['end'];
    
    echo "Testing lines $start to $end... ";
    
    $chunkLines = array_slice($lines, $start, $end - $start);
    $chunkContent = implode("\n", $chunkLines);
    
    // Save to temp file
    $tempFile = resource_path('views/test-chunk.blade.php');
    file_put_contents($tempFile, $chunkContent);
    
    try {
        view('test-chunk', [])->render();
        echo "✓ OK\n";
    } catch (\Exception $e) {
        echo "✗ ERROR\n";
        echo "   " . $e->getMessage() . "\n";
    }
    
    unlink($tempFile);
}

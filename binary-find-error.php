<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$content = file_get_contents(resource_path('views/index.blade.php'));
$lines = explode("\n", $content);
$totalLines = count($lines);

echo "Binary search for the problematic section...\n\n";

// Start with full file, then narrow down
$ranges = [
    [1, $totalLines],
];

while (count($ranges) > 0) {
    $range = array_shift($ranges);
    [$start, $end] = $range;
    
    if ($end - $start < 100) {
        echo "Problem is in lines $start to $end\n";
        echo "This section contains:\n";
        for ($i = $start - 1; $i < min($end, $totalLines); $i++) {
            if (preg_match('/@(if|auth|guest|foreach|for|while|empty|isset|forelse|endif|endauth|endguest|endforeach|endfor|endwhile|endempty|endisset|endforelse|else)/', $lines[$i])) {
                echo "Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
            }
        }
        break;
    }
    
    $mid = floor(($start + $end) / 2);
    
    // Test first half
    $firstHalf = array_slice($lines, $start - 1, $mid - $start + 1);
    $testContent = "<!DOCTYPE html><html><body>\n" . implode("\n", $firstHalf) . "\n</body></html>";
    
    file_put_contents(resource_path('views/test-chunk.blade.php'), $testContent);
    
    try {
        view('test-chunk', [])->render();
        echo "Lines $start-$mid: ✓ OK\n";
    } catch (\Exception $e) {
        echo "Lines $start-$mid: ✗ ERROR - " . substr($e->getMessage(), 0, 80) . "\n";
        $ranges[] = [$start, $mid];
    }
    
    // Test second half
    $secondHalf = array_slice($lines, $mid, $end - $mid);
    $testContent = "<!DOCTYPE html><html><body>\n" . implode("\n", $secondHalf) . "\n</body></html>";
    
    file_put_contents(resource_path('views/test-chunk.blade.php'), $testContent);
    
    try {
        view('test-chunk', [])->render();
        echo "Lines $mid-$end: ✓ OK\n";
    } catch (\Exception $e) {
        echo "Lines $mid-$end: ✗ ERROR - " . substr($e->getMessage(), 0, 80) . "\n";
        $ranges[] = [$mid, $end];
    }
    
    @unlink(resource_path('views/test-chunk.blade.php'));
}

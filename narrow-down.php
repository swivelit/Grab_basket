<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$content = file_get_contents(resource_path('views/index.blade.php'));
$lines = explode("\n", $content);

function testSection($lines, $start, $end, $desc) {
    $section = array_slice($lines, $start, $end - $start);
    $testContent = "<!DOCTYPE html><html><head></head><body>\n" . implode("\n", $section) . "\n</body></html>";
    
    file_put_contents(resource_path('views/test-temp.blade.php'), $testContent);
    
    try {
        view('test-temp', [])->render();
        echo "$desc (lines $start-$end): ✓ OK\n";
        @unlink(resource_path('views/test-temp.blade.php'));
        return true;
    } catch (\Exception $e) {
        echo "$desc (lines $start-$end): ✗ ERROR - " . substr($e->getMessage(), 0, 100) . "\n";
        @unlink(resource_path('views/test-temp.blade.php'));
        return false;
    }
}

echo "Narrowing down the problematic section...\n\n";

// We know error is in lines 0-8235
// Let's binary search
$start = 0;
$end = 8235;

while ($end - $start > 500) {
    $mid = floor(($start + $end) / 2);
    
    if (!testSection($lines, $start, $mid, "Lines $start-$mid")) {
        // Error in first half
        $end = $mid;
    } elseif (!testSection($lines, $mid, $end, "Lines $mid-$end")) {
        // Error in second half
        $start = $mid;
    } else {
        echo "\nBoth halves OK individually, but together they fail. Looking at boundary...\n";
        break;
    }
}

echo "\nProblem is around lines $start to $end\n";
echo "Checking blade directives in this range...\n\n";

for ($i = $start; $i < min($end, count($lines)); $i++) {
    $line = $lines[$i];
    if (preg_match('/@(if|auth|guest|foreach|for|while|empty|isset|forelse|endif|endauth|endguest|endforeach|endfor|endwhile|endempty|endisset|endforelse|else)/', $line)) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}

<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');
$lines = explode("\n", $compiled);

echo "Searching for unclosed PHP control structures...\n\n";

// Stack to track opening and closing
$stack = [];
$lineNum = 0;

foreach ($lines as $line) {
    $lineNum++;
    
    // Check for PHP if statements (various patterns)
    if (preg_match('/<\?php if\(/', $line)) {
        $stack[] = ['type' => 'if', 'line' => $lineNum, 'code' => trim($line)];
    }
    
    // Check for endif
    if (preg_match('/<\?php endif;/', $line) || preg_match('/endif; \?>/', $line)) {
        if (!empty($stack)) {
            $last = array_pop($stack);
            if ($last['type'] !== 'if') {
                echo "Warning: Found endif but last opening was {$last['type']} at line {$last['line']}\n";
            }
        } else {
            echo "ERROR: Found endif at line $lineNum but no matching if\n";
        }
    }
}

echo "\nUnclosed structures:\n";
if (empty($stack)) {
    echo "None found - all if statements are closed!\n";
} else {
    echo "Found " . count($stack) . " unclosed if statements:\n\n";
    foreach (array_slice($stack, -10) as $item) {
        echo "Line {$item['line']}: {$item['code']}\n";
    }
}

// Now let's check the actual last 20 lines
echo "\n\nLast 20 lines of compiled file:\n";
echo "=====================================\n";
$lastLines = array_slice($lines, -20);
foreach ($lastLines as $idx => $line) {
    $actualLine = count($lines) - 20 + $idx + 1;
    echo sprintf("%4d: %s\n", $actualLine, $line);
}

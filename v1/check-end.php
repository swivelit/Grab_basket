<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');

// Get total lines
$lines = explode("\n", $compiled);
$totalLines = count($lines);

echo "Compiled View Analysis:\n";
echo "Total lines: $totalLines\n\n";

// Look at the last 20 lines
echo "Last 20 lines of compiled view:\n";
echo "================================\n";
for ($i = max(0, $totalLines - 20); $i < $totalLines; $i++) {
    $lineNum = $i + 1;
    echo sprintf("%4d: %s\n", $lineNum, $lines[$i]);
}

<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');

// Count PHP control structures
preg_match_all('/\bif\s*\(/', $compiled, $ifs);
preg_match_all('/\bendif;/', $compiled, $endifs);

echo "PHP Control Structure Count:\n\n";
echo "if(:        " . count($ifs[0]) . "\n";
echo "endif;:     " . count($endifs[0]) . "\n";
$balance = count($ifs[0]) - count($endifs[0]);
echo "Balance:    " . ($balance === 0 ? "✓ OK" : "✗ MISSING " . abs($balance) . " endif;") . "\n\n";

if ($balance !== 0) {
    echo "Looking for unclosed if statements near the end...\n\n";
    
    // Find all if statements
    preg_match_all('/\bif\s*\([^)]+\):\s*/s', $compiled, $matches, PREG_OFFSET_CAPTURE);
    
    // Show last 10 if statements
    $lastIfs = array_slice($matches[0], -10);
    foreach ($lastIfs as $match) {
        $lineNum = substr_count(substr($compiled, 0, $match[1]), "\n") + 1;
        $snippet = substr($match[0], 0, 80);
        echo "Line $lineNum: " . trim($snippet) . "\n";
    }
}

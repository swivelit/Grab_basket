<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');

// Count all variations of if patterns
$patterns = [
    'if(auth' => '/<\?php\s+if\(auth\(\)/',
    'if(' => '/<\?php\s+if\(/s',
    'if :' => '/\bif\s*\([^)]+\)\s*:/s',
    'endif;' => '/endif;\s*\?>/',
];

foreach ($patterns as $name => $pattern) {
    preg_match_all($pattern, $compiled, $matches);
    echo "$name: " . count($matches[0]) . "\n";
}

echo "\nLet's use token_get_all to properly parse PHP:\n\n";

// Use PHP's tokenizer
$tokens = token_get_all($compiled);
$ifCount = 0;
$endifCount = 0;
$elseCount = 0;
$elseifCount = 0;

foreach ($tokens as $token) {
    if (is_array($token)) {
        if ($token[0] == T_IF) {
            $ifCount++;
        } elseif ($token[0] == T_ENDIF) {
            $endifCount++;
        } elseif ($token[0] == T_ELSE) {
            $elseCount++;
        } elseif ($token[0] == T_ELSEIF) {
            $elseifCount++;
        }
    }
}

echo "T_IF tokens: $ifCount\n";
echo "T_ENDIF tokens: $endifCount\n";
echo "T_ELSE tokens: $elseCount\n";
echo "T_ELSEIF tokens: $elseifCount\n";
echo "\nMissing endif: " . ($ifCount - $endifCount) . "\n";

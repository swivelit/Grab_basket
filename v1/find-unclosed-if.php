<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');
$tokens = token_get_all($compiled);

$stack = [];
$lineNum = 1;
$errors = [];

foreach ($tokens as $idx => $token) {
    if (is_array($token)) {
        // Track line numbers
        $lineNum = $token[2];
        
        if ($token[0] == T_IF) {
            // Get the code snippet around this if
            $snippet = '';
            for ($i = $idx; $i < min($idx + 20, count($tokens)); $i++) {
                if (is_array($tokens[$i])) {
                    $snippet .= $tokens[$i][1];
                } else {
                    $snippet .= $tokens[$i];
                }
                if (strlen($snippet) > 100) break;
            }
            $stack[] = ['line' => $lineNum, 'snippet' => substr($snippet, 0, 80)];
        }
        
        if ($token[0] == T_ENDIF) {
            if (empty($stack)) {
                $errors[] = "Line $lineNum: Found endif without opening if";
            } else {
                array_pop($stack);
            }
        }
    }
}

echo "Unclosed IF statements:\n\n";
if (empty($stack)) {
    echo "None!\n";
} else {
    echo "Found " . count($stack) . " unclosed if statements:\n\n";
    foreach ($stack as $item) {
        echo "Line {$item['line']}: {$item['snippet']}\n\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors:\n\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Searching for problematic Blade structures...\n\n";

$content = file_get_contents(resource_path('views/index.blade.php'));
$lines = explode("\n", $content);

// Track all @if/@auth/@empty etc with their line numbers
$stack = [];
$errors = [];

$directives = [
    'if' => ['start' => '@if', 'end' => '@endif'],
    'auth' => ['start' => '@auth', 'end' => '@endauth'],
    'guest' => ['start' => '@guest', 'end' => '@endguest'],
    'empty' => ['start' => '@empty', 'end' => '@endempty'],
    'isset' => ['start' => '@isset', 'end' => '@endisset'],
    'foreach' => ['start' => '@foreach', 'end' => '@endforeach'],
    'forelse' => ['start' => '@forelse', 'end' => '@endforelse'],
    'for' => ['start' => '@for', 'end' => '@endfor'],
    'while' => ['start' => '@while', 'end' => '@endwhile'],
];

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;
    $trimmed = trim($line);
    
    // Check for directive starts
    foreach ($directives as $type => $patterns) {
        if (preg_match('/^' . preg_quote($patterns['start'], '/') . '(\s|$|\()/', $trimmed)) {
            $stack[] = ['type' => $type, 'line' => $lineNumber, 'directive' => $patterns['start']];
        }
        
        // Check for directive ends
        if (preg_match('/^' . preg_quote($patterns['end'], '/') . '(\s|$)/', $trimmed)) {
            if (empty($stack)) {
                $errors[] = "Line $lineNumber: Found {$patterns['end']} without opening {$patterns['start']}";
            } else {
                $last = array_pop($stack);
                if ($last['type'] !== $type) {
                    $errors[] = "Line $lineNumber: Found {$patterns['end']} but expected @end{$last['type']} (opened at line {$last['line']})";
                    // Push it back
                    $stack[] = $last;
                }
            }
        }
    }
    
    // Check for @else and @elseif
    if (preg_match('/^@else(\s|$)/', $trimmed)) {
        // @else should be inside an @if
        $foundIf = false;
        foreach (array_reverse($stack) as $item) {
            if ($item['type'] === 'if') {
                $foundIf = true;
                break;
            }
        }
        if (!$foundIf) {
            $errors[] = "Line $lineNumber: Found @else without preceding @if";
        }
    }
}

// Check for unclosed directives
if (!empty($stack)) {
    echo "❌ UNCLOSED DIRECTIVES FOUND:\n\n";
    foreach ($stack as $item) {
        echo "Line {$item['line']}: {$item['directive']} is not closed\n";
        
        // Show the actual line content
        $actualLine = $lines[$item['line'] - 1];
        echo "   " . trim($actualLine) . "\n\n";
    }
} else {
    echo "✅ All directives are properly closed!\n\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS FOUND:\n\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
} else {
    echo "✅ No directive errors found!\n";
}

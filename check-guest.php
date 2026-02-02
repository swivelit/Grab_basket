<?php

$content = file_get_contents(__DIR__ . '/resources/views/index.blade.php');

// Count @guest/@endguest
preg_match_all('/@guest\b/', $content, $guest);
preg_match_all('/@endguest\b/', $content, $endguest);

echo "Guest directive count:\n";
echo "@guest:    " . count($guest[0]) . "\n";
echo "@endguest: " . count($endguest[0]) . "\n";
$balance = count($guest[0]) - count($endguest[0]);
echo "Balance:   " . ($balance === 0 ? "✓ OK" : "✗ MISSING " . abs($balance) . " @endguest") . "\n\n";

// Check elseif without if
preg_match_all('/@elseif\b/', $content, $elseif);
preg_match_all('/@else\b/', $content, $else);

echo "@elseif: " . count($elseif[0]) . "\n";
echo "@else:   " . count($else[0]) . "\n";

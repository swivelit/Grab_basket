<?php

$content = file_get_contents(__DIR__ . '/resources/views/index.blade.php');

// Count all blade directives
preg_match_all('/@if\b/', $content, $ifs);
preg_match_all('/@endif\b/', $content, $endifs);
preg_match_all('/@unless\b/', $content, $unless);
preg_match_all('/@endunless\b/', $content, $endunless);
preg_match_all('/@isset\b/', $content, $isset);
preg_match_all('/@endisset\b/', $content, $endisset);
preg_match_all('/@empty\b/', $content, $empty);
preg_match_all('/@endempty\b/', $content, $endempty);
preg_match_all('/@forelse\b/', $content, $forelse);
preg_match_all('/@endforelse\b/', $content, $endforelse);
preg_match_all('/@foreach\b/', $content, $foreach);
preg_match_all('/@endforeach\b/', $content, $endforeach);
preg_match_all('/@for\b/', $content, $for);
preg_match_all('/@endfor\b/', $content, $endfor);
preg_match_all('/@while\b/', $content, $while);
preg_match_all('/@endwhile\b/', $content, $endwhile);

echo "Blade Directive Count:\n\n";
echo "@if:        " . count($ifs[0]) . "\n";
echo "@endif:     " . count($endifs[0]) . "\n";
$ifBalance = count($ifs[0]) - count($endifs[0]);
echo "Balance:    " . ($ifBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($ifBalance) . " @endif") . "\n\n";

echo "@unless:    " . count($unless[0]) . "\n";
echo "@endunless: " . count($endunless[0]) . "\n";
$unlessBalance = count($unless[0]) - count($endunless[0]);
echo "Balance:    " . ($unlessBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($unlessBalance) . " @endunless") . "\n\n";

echo "@isset:     " . count($isset[0]) . "\n";
echo "@endisset:  " . count($endisset[0]) . "\n";
$issetBalance = count($isset[0]) - count($endisset[0]);
echo "Balance:    " . ($issetBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($issetBalance) . " @endisset") . "\n\n";

echo "@empty:     " . count($empty[0]) . "\n";
echo "@endempty:  " . count($endempty[0]) . "\n";
$emptyBalance = count($empty[0]) - count($endempty[0]);
echo "Balance:    " . ($emptyBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($emptyBalance) . " @endempty") . "\n\n";

echo "@forelse:    " . count($forelse[0]) . "\n";
echo "@endforelse: " . count($endforelse[0]) . "\n";
$forelseBalance = count($forelse[0]) - count($endforelse[0]);
echo "Balance:     " . ($forelseBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($forelseBalance) . " @endforelse") . "\n\n";

echo "@foreach:    " . count($foreach[0]) . "\n";
echo "@endforeach: " . count($endforeach[0]) . "\n";
$foreachBalance = count($foreach[0]) - count($endforeach[0]);
echo "Balance:     " . ($foreachBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($foreachBalance) . " @endforeach") . "\n\n";

echo "@for:        " . count($for[0]) . "\n";
echo "@endfor:     " . count($endfor[0]) . "\n";
$forBalance = count($for[0]) - count($endfor[0]);
echo "Balance:     " . ($forBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($forBalance) . " @endfor") . "\n\n";

echo "@while:      " . count($while[0]) . "\n";
echo "@endwhile:   " . count($endwhile[0]) . "\n";
$whileBalance = count($while[0]) - count($endwhile[0]);
echo "Balance:     " . ($whileBalance === 0 ? "✓ OK" : "✗ MISSING " . abs($whileBalance) . " @endwhile") . "\n\n";

// Find any unclosed structures
if ($ifBalance !== 0 || $unlessBalance !== 0 || $issetBalance !== 0 || $emptyBalance !== 0 || 
    $forelseBalance !== 0 || $foreachBalance !== 0 || $forBalance !== 0 || $whileBalance !== 0) {
    echo "\n❌ FOUND UNCLOSED BLADE DIRECTIVES!\n";
    exit(1);
} else {
    echo "\n✅ All blade directives are properly closed.\n";
}

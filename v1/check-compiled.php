<?php

$compiled = file_get_contents(__DIR__ . '/storage/framework/views/f3d5a220a837ca4fe57c12b33ed64fef.php');

preg_match_all('/<\?php if\(/', $compiled, $ifs);
preg_match_all('/<\?php endif;/', $compiled, $endifs);
preg_match_all('/<\?php else:/', $compiled, $elses);

echo "PHP Control Structures in Compiled View:\n\n";
echo "<?php if(:     " . count($ifs[0]) . "\n";
echo "<?php endif;:  " . count($endifs[0]) . "\n";
echo "<?php else::   " . count($elses[0]) . "\n";
echo "Missing endif: " . (count($ifs[0]) - count($endifs[0])) . "\n\n";

if (count($ifs[0]) != count($endifs[0])) {
    echo "Finding last few <?php if( statements:\n";
    preg_match_all('/<\?php if\([^>]+/', $compiled, $matches, PREG_OFFSET_CAPTURE);
    $last10 = array_slice($matches[0], -10);
    foreach ($last10 as $match) {
        $lineNum = substr_count(substr($compiled, 0, $match[1]), "\n") + 1;
        echo "Line $lineNum: " . substr($match[0], 0, 100) . "\n";
    }
}

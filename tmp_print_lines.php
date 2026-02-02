<?php
$path = __DIR__ . '/app/Support/number_polyfill.php';
$lines = file($path);
foreach ($lines as $i => $l) {
    printf("%4d: %s", $i+1, rtrim($l));
    echo PHP_EOL;
}

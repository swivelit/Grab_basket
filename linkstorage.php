<?php
// This script creates the Laravel storage symlink for shared hosting (Hostinger, no SSH)
$target = __DIR__ . '/storage/app/public';
$link = __DIR__ . '/public/storage';

if (file_exists($link)) {
    echo 'Symlink already exists: ' . $link;
} else {
    if (symlink($target, $link)) {
        echo 'Symlink created: ' . $link . ' -> ' . $target;
    } else {
        echo 'Failed to create symlink. Check permissions.';
    }
}

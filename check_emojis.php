<?php

use App\Models\Category;

$categories = Category::select('id', 'name', 'emoji')->get();

foreach ($categories as $cat) {
    echo $cat->id . ': ' . $cat->name . ' = ' . ($cat->emoji ?: 'NULL') . "\n";
}
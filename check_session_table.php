<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check if sessions table exists
$hasTable = Schema::hasTable('sessions');
echo "Sessions table exists: " . ($hasTable ? "Yes\n" : "No\n");

if ($hasTable) {
    // Get table structure
    $columns = DB::select("SHOW COLUMNS FROM sessions");
    echo "\nTable structure:\n";
    foreach ($columns as $column) {
        echo $column->Field . " - " . $column->Type . "\n";
    }

    // Count sessions
    $count = DB::table('sessions')->count();
    echo "\nTotal sessions: " . $count . "\n";
}
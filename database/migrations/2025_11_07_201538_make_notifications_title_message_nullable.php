<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make title and message nullable
        DB::statement('ALTER TABLE notifications MODIFY title VARCHAR(255) NULL');
        DB::statement('ALTER TABLE notifications MODIFY message TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make title and message not null
        DB::statement('ALTER TABLE notifications MODIFY title VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE notifications MODIFY message TEXT NOT NULL');
    }
};

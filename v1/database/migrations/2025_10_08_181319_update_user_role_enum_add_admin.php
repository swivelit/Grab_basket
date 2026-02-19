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
        // SQLite doesn't support MODIFY COLUMN with ENUM, so we'll recreate the table
        Schema::table('users', function (Blueprint $table) {
            // For SQLite, we need to handle this differently
            // First, let's check if we're using SQLite
            $driver = Schema::getConnection()->getDriverName();
            
            if ($driver === 'sqlite') {
                // For SQLite, just update any existing admin users
                // The role column should already accept text values
                // No schema change needed for SQLite as it's flexible with text
            } else {
                // For MySQL/PostgreSQL
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller', 'buyer', 'admin') DEFAULT 'buyer'");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check database driver
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver !== 'sqlite') {
            // For MySQL/PostgreSQL - revert back to original role enum values
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('seller', 'buyer') DEFAULT 'buyer'");
        }
        // For SQLite, no action needed as it handles text flexibly
    }
};

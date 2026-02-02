<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'billing_address')) {
                $table->string('billing_address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('billing_address');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('state');
            }
            if (!Schema::hasColumn('users', 'pincode')) {
                $table->string('pincode')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['seller', 'buyer', 'admin'])->default('buyer')->after('pincode');
            } else {
                // Update existing role column to include 'admin' if not already there
                $table->enum('role', ['seller', 'buyer', 'admin'])->default('buyer')->change();
            }
            if (!Schema::hasColumn('users', 'sex')) {
                $table->enum('sex', ['male', 'female', 'other'])->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'dob')) {
                $table->date('dob')->nullable()->after('sex');
            }
            if (!Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('dob');
            }
            if (!Schema::hasColumn('users', 'default_address')) {
                $table->string('default_address')->nullable()->after('profile_picture');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop columns that exist
            $columns = [];
            if (Schema::hasColumn('users', 'phone')) $columns[] = 'phone';
            if (Schema::hasColumn('users', 'billing_address')) $columns[] = 'billing_address';
            if (Schema::hasColumn('users', 'state')) $columns[] = 'state';
            if (Schema::hasColumn('users', 'city')) $columns[] = 'city';
            if (Schema::hasColumn('users', 'pincode')) $columns[] = 'pincode';
            if (Schema::hasColumn('users', 'role')) $columns[] = 'role';
            if (Schema::hasColumn('users', 'sex')) $columns[] = 'sex';
            if (Schema::hasColumn('users', 'dob')) $columns[] = 'dob';
            if (Schema::hasColumn('users', 'profile_picture')) $columns[] = 'profile_picture';
            if (Schema::hasColumn('users', 'default_address')) $columns[] = 'default_address';
            
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

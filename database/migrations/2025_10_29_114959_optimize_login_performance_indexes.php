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
            // Add indexes for faster login lookups
            $table->index(['email', 'password'], 'users_email_password_index');
            $table->index(['phone', 'password'], 'users_phone_password_index');
            $table->index(['role'], 'users_role_index');
        });

        Schema::table('buyers', function (Blueprint $table) {
            // Add indexes for buyer login lookups
            $table->index(['email', 'password'], 'buyers_email_password_index');
            $table->index(['phone', 'password'], 'buyers_phone_password_index');
        });

        Schema::table('sellers', function (Blueprint $table) {
            // Add indexes for seller login lookups
            $table->index(['email', 'password'], 'sellers_email_password_index');
            $table->index(['phone', 'password'], 'sellers_phone_password_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_password_index');
            $table->dropIndex('users_phone_password_index');
            $table->dropIndex('users_role_index');
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex('buyers_email_password_index');
            $table->dropIndex('buyers_phone_password_index');
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex('sellers_email_password_index');
            $table->dropIndex('sellers_phone_password_index');
        });
    }
};

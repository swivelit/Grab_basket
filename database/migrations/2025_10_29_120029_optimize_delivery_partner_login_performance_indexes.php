<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add composite indexes for delivery partner login performance optimization.
     * These indexes dramatically improve authentication speed by allowing single-query
     * lookups instead of separate email/phone and password verification queries.
     */
    public function up(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            // Composite index for email + password login (most common)
            $table->index(['email', 'password'], 'delivery_partners_email_password_index');
            
            // Composite index for phone + password login
            $table->index(['phone', 'password'], 'delivery_partners_phone_password_index');
            
            // Add index for last_active_at for session optimization
            $table->index('last_active_at', 'delivery_partners_last_active_index');
            
            // Add composite index for status + is_verified for quick status checks
            $table->index(['status', 'is_verified'], 'delivery_partners_status_verified_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->dropIndex('delivery_partners_email_password_index');
            $table->dropIndex('delivery_partners_phone_password_index');
            $table->dropIndex('delivery_partners_last_active_index');
            $table->dropIndex('delivery_partners_status_verified_index');
        });
    }
};

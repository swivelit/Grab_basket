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
        Schema::create('warehouse_users', function (Blueprint $table) {
            $table->id();
            
            // Authentication fields
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // Profile fields
            $table->string('phone')->nullable();
            $table->string('employee_id')->unique()->nullable();
            $table->string('department')->default('warehouse');
            
            // Role and permissions
            $table->enum('role', ['staff', 'supervisor', 'manager'])->default('staff');
            $table->json('permissions')->nullable(); // Specific permissions
            
            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            
            // Warehouse-specific fields
            $table->json('assigned_areas')->nullable(); // Which warehouse areas they can access
            $table->boolean('can_add_stock')->default(true);
            $table->boolean('can_adjust_stock')->default(false);
            $table->boolean('can_manage_locations')->default(false);
            $table->boolean('can_view_reports')->default(false);
            $table->boolean('can_manage_quick_delivery')->default(false);
            
            // Audit fields
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['email', 'is_active'], 'idx_warehouse_users_auth');
            $table->index(['role', 'is_active'], 'idx_warehouse_users_role');
            $table->index('employee_id', 'idx_warehouse_users_employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_users');
    }
};

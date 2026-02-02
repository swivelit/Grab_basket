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
        // Add 10-minute delivery support to sellers table
        if (Schema::hasTable('sellers')) {
            Schema::table('sellers', function (Blueprint $table) {
                // Add if columns don't exist
                if (!Schema::hasColumn('sellers', 'available_for_10_min_delivery')) {
                    $table->boolean('available_for_10_min_delivery')->default(false);
                }
                
                if (!Schema::hasColumn('sellers', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable();
                }
                
                if (!Schema::hasColumn('sellers', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable();
                }
                
                if (!Schema::hasColumn('sellers', 'delivery_radius_km')) {
                    $table->integer('delivery_radius_km')->default(5);
                }
                
                if (!Schema::hasColumn('sellers', 'delivery_mode')) {
                    $table->enum('delivery_mode', ['normal', '10-minute', 'both'])->default('normal');
                }
            });
        }

        // Create delivery_settings table if it doesn't exist
        if (!Schema::hasTable('delivery_settings')) {
            Schema::create('delivery_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_name');
                $table->string('setting_value')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns
        if (Schema::hasTable('sellers')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->dropColumnIfExists('available_for_10_min_delivery');
                $table->dropColumnIfExists('latitude');
                $table->dropColumnIfExists('longitude');
                $table->dropColumnIfExists('delivery_radius_km');
                $table->dropColumnIfExists('delivery_mode');
            });
        }

        // Drop delivery_settings table
        Schema::dropIfExists('delivery_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for 10-minute delivery system
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Delivery type and timing
            $table->enum('delivery_type', ['express_10min', 'standard'])->default('standard')->after('status');
            $table->timestamp('delivery_promised_at')->nullable()->after('delivery_type');
            $table->timestamp('delivery_started_at')->nullable()->after('delivery_promised_at');
            $table->timestamp('delivery_completed_at')->nullable()->after('delivery_started_at');
            
            // Delivery partner information
            $table->string('delivery_partner_name')->nullable()->after('delivery_completed_at');
            $table->string('delivery_partner_phone')->nullable()->after('delivery_partner_name');
            $table->string('delivery_partner_vehicle')->nullable()->after('delivery_partner_phone');
            
            // Real-time tracking coordinates
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_partner_vehicle');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
            $table->timestamp('location_updated_at')->nullable()->after('delivery_longitude');
            
            // Store coordinates for distance calculation
            $table->decimal('store_latitude', 10, 8)->nullable()->after('location_updated_at');
            $table->decimal('store_longitude', 11, 8)->nullable()->after('store_latitude');
            
            // Delivery address coordinates
            $table->decimal('customer_latitude', 10, 8)->nullable()->after('store_longitude');
            $table->decimal('customer_longitude', 11, 8)->nullable()->after('customer_latitude');
            
            // Estimated time of arrival
            $table->integer('eta_minutes')->nullable()->after('customer_longitude');
            $table->decimal('distance_km', 8, 2)->nullable()->after('eta_minutes');
            
            // Quick delivery eligibility
            $table->boolean('is_quick_delivery_eligible')->default(false)->after('distance_km');
            $table->text('delivery_notes')->nullable()->after('is_quick_delivery_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'delivery_promised_at',
                'delivery_started_at',
                'delivery_completed_at',
                'delivery_partner_name',
                'delivery_partner_phone',
                'delivery_partner_vehicle',
                'delivery_latitude',
                'delivery_longitude',
                'location_updated_at',
                'store_latitude',
                'store_longitude',
                'customer_latitude',
                'customer_longitude',
                'eta_minutes',
                'distance_km',
                'is_quick_delivery_eligible',
                'delivery_notes'
            ]);
        });
    }
};

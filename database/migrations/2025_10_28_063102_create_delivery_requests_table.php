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
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('delivery_partner_id')->nullable()->constrained('delivery_partners')->onDelete('set null');
            $table->enum('status', [
                'pending',      // Waiting for delivery partner
                'assigned',     // Assigned to delivery partner
                'accepted',     // Accepted by delivery partner
                'rejected',     // Rejected by delivery partner
                'picked_up',    // Order picked up from merchant
                'in_transit',   // On the way to customer
                'delivered',    // Successfully delivered
                'cancelled',    // Cancelled
                'failed'        // Delivery failed
            ])->default('pending');
            
            // Location data
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();
            $table->string('pickup_address', 500)->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->string('delivery_address', 500)->nullable();
            
            // Distance and pricing
            $table->decimal('estimated_distance', 8, 2)->nullable(); // in kilometers
            $table->decimal('actual_distance', 8, 2)->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(25.00); // Standard ₹25 delivery fee
            $table->decimal('bonus_amount', 10, 2)->default(0.00);
            $table->decimal('total_earning', 10, 2)->default(25.00);
            
            // Timing
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->integer('estimated_time')->nullable(); // in minutes
            $table->integer('actual_time')->nullable();
            
            // Additional info
            $table->text('delivery_instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->integer('delivery_attempts')->default(0);
            $table->decimal('customer_rating', 3, 2)->nullable();
            $table->text('customer_feedback')->nullable();
            $table->json('tracking_data')->nullable(); // GPS tracking points
            $table->boolean('is_priority')->default(false);
            
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['delivery_partner_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['delivery_latitude', 'delivery_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
    }
};

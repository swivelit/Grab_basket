<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_food_orders_table.php
public function up()
{
    Schema::create('food_orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('hotel_owner_id')->constrained()->onDelete('cascade');
        $table->string('customer_name');
        $table->string('customer_phone');
        $table->string('delivery_address');
        $table->decimal('food_total', 10, 2);      // subtotal of food items
        $table->decimal('delivery_fee', 8, 2)->default(50.00);
        $table->decimal('total_amount', 10, 2);   // food_total + delivery_fee
        $table->enum('status', [
            'pending', 
            'accepted', 
            'preparing', 
            'ready', 
            'picked_up', 
            'delivered', 
            'cancelled'
        ])->default('pending');
        $table->timestamp('estimated_delivery_time')->nullable(); // 10 mins from now
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_orders');
    }
};

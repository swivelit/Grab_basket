<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_food_order_items_table.php
public function up()
{
    Schema::create('food_order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('food_order_id')->constrained()->onDelete('cascade');
        $table->foreignId('food_item_id')->constrained('food_items')->onDelete('cascade');
        $table->string('food_name');
        $table->decimal('price', 8, 2);
        $table->integer('quantity');
        $table->string('food_type'); // 'veg' or 'non-veg'
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_order_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('food_cart_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('food_cart_id')->constrained('food_carts')->onDelete('cascade');
        $table->foreignId('food_item_id')->constrained('food_items')->onDelete('cascade');
        $table->integer('quantity')->default(1);
        $table->decimal('price', 10, 2); // price at time of add
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_cart_items');
    }
};

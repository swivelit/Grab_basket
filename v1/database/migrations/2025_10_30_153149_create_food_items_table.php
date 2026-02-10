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
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_owner_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->decimal('discounted_price', 8, 2)->nullable();
            $table->string('category'); // starter, main, dessert, beverage, etc.
            $table->string('food_type')->default('veg'); // veg, non-veg, vegan
            $table->json('images')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('preparation_time')->default(15); // in minutes
            $table->text('ingredients')->nullable();
            $table->string('spice_level')->nullable(); // mild, medium, hot
            $table->json('allergens')->nullable(); // nuts, dairy, gluten, etc.
            $table->integer('calories')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};

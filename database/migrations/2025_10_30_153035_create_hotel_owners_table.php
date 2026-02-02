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
        Schema::create('hotel_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('restaurant_name');
            $table->text('restaurant_address');
            $table->string('restaurant_phone');
            $table->string('cuisine_type')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->json('restaurant_images')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->integer('min_order_amount')->default(0);
            $table->integer('delivery_time')->default(30); // in minutes
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->string('status')->default('pending'); // pending, approved, suspended
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_owners');
    }
};

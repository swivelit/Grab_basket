<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('food_cart_items', function (Blueprint $table) {
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->string('food_type')->default('veg'); // or non-veg
            $table->string('category')->nullable();
            $table->foreignId('hotel_owner_id')->constrained('hotel_owners');
        });
    }

    public function down()
    {
        Schema::table('food_cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'image_url',
                'food_type',
                'category',
                'hotel_owner_id'
            ]);
        });
    }
};
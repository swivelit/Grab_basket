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
        Schema::create('ten_min_delivery_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // copy all product fields
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('delivery_charge', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->string('gift_option')->default('no');
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('seller_id'); // required
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');



            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ten_min_delivery_products');
    }
};

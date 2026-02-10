<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ten_min_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ten_min_order_id')->constrained('ten_min_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('ten_min_delivery_products')->onDelete('cascade');
            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('ten_min_order_id');
            $table->index('product_id');
            $table->index('seller_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ten_min_order_items');
    }
};

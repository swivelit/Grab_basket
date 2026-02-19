<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add delivery type to cart and wishlist
     */
    public function up(): void
    {
        // Add delivery_type to cart_items
        Schema::table('cart_items', function (Blueprint $table) {
            $table->enum('delivery_type', ['express_10min', 'standard'])->default('standard')->after('quantity');
        });

        // Add delivery_type to wishlists
        Schema::table('wishlists', function (Blueprint $table) {
            $table->enum('delivery_type', ['express_10min', 'standard'])->default('standard')->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });
    }
};

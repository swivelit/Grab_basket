<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cod')->after('total_amount');
            // Optional: you can also add payment_status if needed (e.g., 'paid', 'pending')
        });
    }

    public function down()
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
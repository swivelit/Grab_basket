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
    Schema::table('food_orders', function (Blueprint $table) {
        $table->string('shop_name')->after('hotel_owner_id')->nullable();
        $table->text('shop_address')->after('shop_name')->nullable();
        $table->string('customer_email')->after('customer_phone')->nullable();
        $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('status');
    });
}

public function down()
{
    Schema::table('food_orders', function (Blueprint $table) {
        $table->dropColumn(['shop_name', 'shop_address', 'customer_email', 'delivery_partner_id']);
    });
}
};

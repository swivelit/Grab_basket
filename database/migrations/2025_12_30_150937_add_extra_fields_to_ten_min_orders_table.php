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
        Schema::table('ten_min_orders', function (Blueprint $table) {
            $table->decimal('wallet_discount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('tax', 10, 2)->default(0)->after('delivery_fee');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->foreignId('seller_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ten_min_orders', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['wallet_discount', 'tax', 'payment_reference', 'seller_id']);
        });
    }
};

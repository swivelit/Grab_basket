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
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->unsignedBigInteger('current_order_id')->nullable()->after('is_available');
            $table->string('current_order_type')->nullable()->after('current_order_id');
            $table->index(['current_order_id', 'current_order_type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_partner_id')) {
                $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('status');
                $table->foreign('delivery_partner_id')->references('id')->on('delivery_partners')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->dropColumn(['current_order_id', 'current_order_type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_partner_id']);
            $table->dropColumn('delivery_partner_id');
        });
    }
};

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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'delivery_status')) {
                    $table->string('delivery_status')->default('pending')->after('status');
                }
                if (!Schema::hasColumn('orders', 'order_status')) {
                    $table->string('order_status')->default('pending')->after('delivery_status');
                }
            });
        }

        if (Schema::hasTable('food_orders')) {
            Schema::table('food_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('food_orders', 'delivery_partner_id')) {
                    $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('status');
                    $table->index('delivery_partner_id');
                }
            });
        }

        if (Schema::hasTable('ten_min_orders')) {
            Schema::table('ten_min_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('ten_min_orders', 'delivery_partner_id')) {
                    $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('status');
                    $table->index('delivery_partner_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'delivery_status')) {
                    $table->dropColumn('delivery_status');
                }
                if (Schema::hasColumn('orders', 'order_status')) {
                    $table->dropColumn('order_status');
                }
            });
        }

        if (Schema::hasTable('food_orders')) {
            Schema::table('food_orders', function (Blueprint $table) {
                if (Schema::hasColumn('food_orders', 'delivery_partner_id')) {
                    $table->dropColumn('delivery_partner_id');
                }
            });
        }

        if (Schema::hasTable('ten_min_orders')) {
            Schema::table('ten_min_orders', function (Blueprint $table) {
                if (Schema::hasColumn('ten_min_orders', 'delivery_partner_id')) {
                    $table->dropColumn('delivery_partner_id');
                }
            });
        }
    }
};

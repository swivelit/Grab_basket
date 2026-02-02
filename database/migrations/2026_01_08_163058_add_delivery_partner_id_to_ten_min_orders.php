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
            if (!Schema::hasColumn('ten_min_orders', 'delivery_partner_id')) {
                $table->foreignId('delivery_partner_id')->nullable()->after('estimated_delivery_time')->constrained('delivery_partners')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ten_min_orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_partner_id']);
            $table->dropColumn('delivery_partner_id');
        });
    }
};

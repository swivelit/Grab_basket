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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_partner_id')) {
                $table->unsignedBigInteger('delivery_partner_id')->nullable()->after('status');
            }

            // Convert delivery_type to string to support more types (food, mixed)
            $table->string('delivery_type')->default('standard')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // We won't drop the column as it's needed for other features,
            // but we could revert it to enum if needed. 
            // However, it's safer to just leave it as string for now.
        });
    }
};

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
        Schema::table('ten_min_delivery_products', function (Blueprint $table) {
            $table->string('unique_id', 64)->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ten_min_delivery_products', function (Blueprint $table) {
            $table->dropColumn('unique_id');
        });
    }
};

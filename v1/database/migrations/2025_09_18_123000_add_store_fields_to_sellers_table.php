<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
{
    Schema::table('sellers', function (Blueprint $table) {
        if (!Schema::hasColumn('sellers', 'store_name')) {
            $table->string('store_name')->nullable();
        }
        if (!Schema::hasColumn('sellers', 'gst_number')) {
            $table->string('gst_number')->nullable();
        }
        if (!Schema::hasColumn('sellers', 'store_address')) {
            $table->string('store_address')->nullable();
        }
        if (!Schema::hasColumn('sellers', 'store_contact')) {
            $table->string('store_contact')->nullable();
        }
    });
}
    public function down() {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn(['store_name', 'gst_number', 'store_address', 'store_contact']);
        });
    }
};
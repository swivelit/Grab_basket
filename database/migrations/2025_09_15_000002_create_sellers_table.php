<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('billing_address');
            $table->string('state');
            $table->string('city');
            $table->string('pincode');
            $table->string('password');
            $table->string('gift_option')->default('no');
            $table->integer('stock')->default(0);
            $table->string('store_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('store_address')->nullable();
            $table->string('store_contact')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('sellers');
    }
};

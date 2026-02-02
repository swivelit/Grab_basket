<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('billing_address');
            $table->string('state');
            $table->string('city');
            $table->string('pincode');
            $table->string('password');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('buyers');
    }
};

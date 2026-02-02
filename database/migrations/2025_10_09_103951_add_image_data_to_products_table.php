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
        Schema::table('products', function (Blueprint $table) {
            $table->longText('image_data')->nullable()->after('image')->comment('Base64 encoded image data');
            $table->string('image_mime_type')->nullable()->after('image_data')->comment('Image MIME type');
            $table->integer('image_size')->nullable()->after('image_mime_type')->comment('Image size in bytes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'image_mime_type', 'image_size']);
        });
    }
};

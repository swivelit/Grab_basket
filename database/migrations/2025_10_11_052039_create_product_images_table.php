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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path'); // path to the image file
            $table->string('original_name')->nullable(); // original filename
            $table->string('mime_type')->nullable(); // image mime type
            $table->integer('file_size')->nullable(); // file size in bytes
            $table->integer('sort_order')->default(0); // for ordering images
            $table->boolean('is_primary')->default(false); // primary image flag
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};

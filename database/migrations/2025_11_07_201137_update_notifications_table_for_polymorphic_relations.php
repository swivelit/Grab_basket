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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop old user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Add polymorphic columns
            $table->string('notifiable_type')->after('id');
            $table->unsignedBigInteger('notifiable_id')->after('notifiable_type');
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Remove polymorphic columns
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
            $table->dropColumn(['notifiable_type', 'notifiable_id']);
            
            // Add back user_id
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        });
    }
};

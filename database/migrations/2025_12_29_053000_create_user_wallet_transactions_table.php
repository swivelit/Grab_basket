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
        Schema::create('user_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', [
                'signup_bonus',
                'referral_bonus',
                'referrer_reward',
                'purchase',
                'refund',
                'admin_adjustment',
                'other'
            ])->default('other');
            $table->integer('amount')->comment('Positive for credit, negative for debit');
            $table->text('description')->nullable();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->onDelete('set null')
                ->comment('For referrals: the user who referred or was referred');
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallet_transactions');
    }
};

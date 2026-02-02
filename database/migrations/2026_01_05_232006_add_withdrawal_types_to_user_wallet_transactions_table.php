<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adding the values to the enum using a raw statement for better compatibility
        DB::statement("ALTER TABLE user_wallet_transactions MODIFY COLUMN type ENUM(
            'signup_bonus', 
            'referral_bonus', 
            'referrer_reward', 
            'purchase', 
            'refund', 
            'admin_adjustment', 
            'other', 
            'withdrawal_request', 
            'withdrawal_refund'
        ) DEFAULT 'other'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE user_wallet_transactions MODIFY COLUMN type ENUM(
            'signup_bonus', 
            'referral_bonus', 
            'referrer_reward', 
            'purchase', 
            'refund', 
            'admin_adjustment', 
            'other'
        ) DEFAULT 'other'");
    }
};

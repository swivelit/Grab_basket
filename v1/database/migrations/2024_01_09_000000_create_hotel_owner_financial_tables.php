<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelOwnerFinancialTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotel_owner_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_owner_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->decimal('on_hold_balance', 10, 2)->default(0.00);
            $table->decimal('pending_withdrawals', 10, 2)->default(0.00);
            $table->string('currency')->default('INR');
            $table->timestamps();

            $table->index(['hotel_owner_id', 'balance', 'on_hold_balance'], 'idx_wallet_owner_balance');
        });

        Schema::create('hotel_owner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_owner_wallet_id')->constrained('hotel_owner_wallets')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_owner_wallet_id', 'status', 'requested_at'], 'idx_withdrawal_wallet_status');
            $table->index(['status', 'processed_at'], 'idx_withdrawal_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_owner_withdrawals');
        Schema::dropIfExists('hotel_owner_wallets');
    }
}
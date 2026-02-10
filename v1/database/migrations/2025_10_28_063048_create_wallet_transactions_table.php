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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_partner_id')->constrained('delivery_partners')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('delivery_partner_wallets')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('transaction_id')->unique();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->enum('category', [
                'delivery_payment',
                'bonus',
                'penalty', 
                'withdrawal',
                'refund',
                'adjustment'
            ]);
            $table->string('description');
            $table->json('metadata')->nullable(); // Store additional data like order details, bonus type, etc.
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_partner_id', 'type', 'status']);
            $table->index(['wallet_id', 'created_at']);
            $table->index(['order_id']);
            $table->index(['transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

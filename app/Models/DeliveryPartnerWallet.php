<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPartnerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_partner_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'pending_amount',
        'total_deliveries',
        'successful_deliveries',
        'average_rating',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
    ];

    /**
     * Get the delivery partner that owns the wallet
     */
    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Add money to wallet after successful delivery
     */
    public function addDeliveryPayment($orderId, $amount = 25.00, $description = 'Delivery payment')
    {
        return $this->addMoney($orderId, $amount, 'delivery_payment', $description);
    }

    /**
     * Add money to wallet
     */
    public function addMoney($orderId, $amount, $category = 'delivery_payment', $description = 'Delivery payment')
    {
        $balanceBefore = $this->balance;
        $balanceAfter = $balanceBefore + $amount;

        // Create transaction
        $transaction = WalletTransaction::create([
            'delivery_partner_id' => $this->delivery_partner_id,
            'wallet_id' => $this->id,
            'order_id' => $orderId,
            'transaction_id' => 'TXN_' . time() . '_' . rand(1000, 9999),
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'category' => $category,
            'description' => $description,
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        // Update wallet balance
        $this->update([
            'balance' => $balanceAfter,
            'total_earned' => $this->total_earned + $amount,
            'last_transaction_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Withdraw money from wallet
     */
    public function withdrawMoney($amount, $description = 'Wallet withdrawal')
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $balanceBefore = $this->balance;
        $balanceAfter = $balanceBefore - $amount;

        // Create transaction
        $transaction = WalletTransaction::create([
            'delivery_partner_id' => $this->delivery_partner_id,
            'wallet_id' => $this->id,
            'transaction_id' => 'WTH_' . time() . '_' . rand(1000, 9999),
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'category' => 'withdrawal',
            'description' => $description,
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        // Update wallet balance
        $this->update([
            'balance' => $balanceAfter,
            'total_withdrawn' => $this->total_withdrawn + $amount,
            'last_transaction_at' => now(),
        ]);

        return $transaction;
    }

    /**
     * Update delivery statistics
     */
    public function updateDeliveryStats($successful = true)
    {
        $this->increment('total_deliveries');

        if ($successful) {
            $this->increment('successful_deliveries');
        }
    }

    /**
     * Calculate success rate
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_deliveries == 0) {
            return 0;
        }

        return round(($this->successful_deliveries / $this->total_deliveries) * 100, 2);
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute()
    {
        return '₹' . number_format($this->balance, 2);
    }

    /**
     * Get formatted total earned
     */
    public function getFormattedTotalEarnedAttribute()
    {
        return '₹' . number_format($this->total_earned, 2);
    }

    /**
     * Get today's earnings from the related partner
     */
    public function getTodayEarningsAttribute()
    {
        return $this->deliveryPartner ? $this->deliveryPartner->today_earnings : 0;
    }

    /**
     * Get this month's earnings from the related partner
     */
    public function getThisMonthEarningsAttribute()
    {
        return $this->deliveryPartner ? $this->deliveryPartner->this_month_earnings : 0;
    }

    /**
     * Get available earnings (balance)
     */
    public function getAvailableEarningsAttribute()
    {
        return $this->balance;
    }
}
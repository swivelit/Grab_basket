<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_partner_id',
        'wallet_id',
        'order_id',
        'transaction_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'category',
        'description',
        'metadata',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the delivery partner that owns the transaction
     */
    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet()
    {
        return $this->belongsTo(DeliveryPartnerWallet::class, 'wallet_id');
    }

    /**
     * Get the order associated with the transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope for delivery payments
     */
    public function scopeDeliveryPayments($query)
    {
        return $query->where('category', 'delivery_payment');
    }

    /**
     * Get formatted amount with proper sign
     */
    public function getFormattedAmountAttribute()
    {
        $sign = $this->type === 'credit' ? '+' : '-';
        return $sign . '₹' . number_format((float)$this->amount, 2);
    }

    /**
     * Get transaction icon based on category
     */
    public function getIconAttribute()
    {
        return match($this->category) {
            'delivery_payment' => 'bi-truck',
            'bonus' => 'bi-gift',
            'penalty' => 'bi-exclamation-triangle',
            'withdrawal' => 'bi-arrow-up-right',
            'refund' => 'bi-arrow-clockwise',
            'adjustment' => 'bi-gear',
            default => 'bi-circle'
        };
    }

    /**
     * Get transaction color based on type and category
     */
    public function getColorAttribute()
    {
        if ($this->type === 'credit') {
            return match($this->category) {
                'delivery_payment' => 'success',
                'bonus' => 'info',
                'refund' => 'primary',
                default => 'success'
            };
        } else {
            return match($this->category) {
                'penalty' => 'danger',
                'withdrawal' => 'warning',
                default => 'secondary'
            };
        }
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Wallet Transaction Model
 * Separate from WalletTransaction which is for delivery partners
 */
class UserWalletTransaction extends Model
{
    protected $table = 'user_wallet_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'related_user_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'user_id' => 'integer',
        'related_user_id' => 'integer',
    ];

    /**
     * Get the user who owns this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related user (for referrals)
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * Scope for credits (positive amounts)
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope for debits (negative amounts)
     */
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted amount with sign
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . $this->amount . ' points';
    }

    /**
     * The "booted" method of the model.
     * Automatically update user wallet points when a transaction is created, updated or deleted.
     */
    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->user->increment('wallet_point', $transaction->amount);
        });

        static::updated(function ($transaction) {
            $diff = $transaction->amount - $transaction->getOriginal('amount');
            if ($diff != 0) {
                $transaction->user->increment('wallet_point', $diff);
            }
        });

        static::deleted(function ($transaction) {
            $transaction->user->decrement('wallet_point', $transaction->amount);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class HotelOwnerWallet extends Model
{
    use HasFactory;

    protected $table = 'hotel_owner_wallets';

    protected $fillable = [
        'hotel_owner_id', 'balance', 'on_hold_balance', 'pending_withdrawals', 'currency'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'on_hold_balance' => 'decimal:2',
        'pending_withdrawals' => 'decimal:2',
    ];

    /**
     * Get the total earnings (including pending withdrawals)
     */
    public function getTotalEarningsAttribute()
    {
        return (float)$this->balance + (float)$this->pending_withdrawals;
    }

    /**
     * Get the current pending withdrawal amount
     */
    public function getPendingWithdrawalAttribute()
    {
        $withdrawal = $this->withdrawals()
            ->where('status', 'pending')
            ->orderBy('id', 'desc')
            ->first();

        return $withdrawal ? $withdrawal->amount : 0;
    }

    /**
     * Get total withdrawn amount
     */
    public function getTotalWithdrawnAttribute()
    {
        return (float)$this->withdrawals()
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function hotelOwner()
    {
        return $this->belongsTo(HotelOwner::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(HotelOwnerWithdrawal::class);
    }

    public function getAvailableBalanceAttribute()
    {
        return (float)$this->balance - (float)$this->on_hold_balance;
    }

    /**
     * Add funds to the wallet's available balance
     */
    public function credit($amount, $description = null)
    {
        if ($amount <= 0) return false;
        
        return DB::transaction(function() use ($amount) {
            return DB::table('hotel_owner_wallets')
                ->where('id', $this->id)
                ->update([
                    'balance' => DB::raw("balance + $amount")
                ]);
        });
    }

    /**
     * Debit funds from available balance
     */
    public function debit($amount, $description = null)
    {
        if ($amount <= 0) return false;
        if ($this->available_balance < $amount) return false;

        return DB::transaction(function() use ($amount) {
            return DB::table('hotel_owner_wallets')
                ->where('id', $this->id)
                ->update([
                    'balance' => DB::raw("balance - $amount")
                ]);
        });
    }

    /**
     * Place funds on hold (e.g., for pending withdrawal)
     */
    public function placeOnHold($amount)
    {
        if ($amount <= 0) return false;
        if ($this->available_balance < $amount) return false;

        return DB::transaction(function() use ($amount) {
            return DB::table('hotel_owner_wallets')
                ->where('id', $this->id)
                ->update([
                    'on_hold_balance' => DB::raw("on_hold_balance + $amount"),
                    'pending_withdrawals' => DB::raw("pending_withdrawals + $amount")
                ]);
        });
    }

    /**
     * Release funds from hold (e.g., rejected withdrawal)
     */
    public function releaseFromHold($amount)
    {
        if ($amount <= 0) return false;
        if ($this->on_hold_balance < $amount) return false;

        return DB::transaction(function() use ($amount) {
            return DB::table('hotel_owner_wallets')
                ->where('id', $this->id)
                ->update([
                    'on_hold_balance' => DB::raw("on_hold_balance - $amount"),
                    'pending_withdrawals' => DB::raw("pending_withdrawals - $amount")
                ]);
        });
    }

    /**
     * Complete a withdrawal by removing funds from hold and balance
     */
    public function completeWithdrawal($amount)
    {
        if ($amount <= 0) return false;
        if ($this->on_hold_balance < $amount) return false;

        return DB::transaction(function() use ($amount) {
            return DB::table('hotel_owner_wallets')
                ->where('id', $this->id)
                ->update([
                    'on_hold_balance' => DB::raw("on_hold_balance - $amount"),
                    'balance' => DB::raw("balance - $amount"),
                    'pending_withdrawals' => DB::raw("pending_withdrawals - $amount")
                ]);
        });
    }

    /**
     * Request a new withdrawal - creates withdrawal record and places funds on hold
     */
    public function requestWithdrawal($amount, $notes = null)
    {
        if ($amount <= 0 || $this->available_balance < $amount) {
            throw new Exception('Insufficient available balance for withdrawal.');
        }

        return DB::transaction(function() use ($amount, $notes) {
            // Place funds on hold first
            if (!$this->placeOnHold($amount)) {
                throw new Exception('Unable to place funds on hold.');
            }

            // Create withdrawal record
            $withdrawal = $this->withdrawals()->create([
                'amount' => $amount,
                'status' => 'pending',
                'requested_at' => now(),
                'notes' => $notes,
            ]);

            if (!$withdrawal) {
                throw new Exception('Unable to create withdrawal record.');
            }

            return $withdrawal;
        });
    }

    /**
     * Process a withdrawal approval
     */
    public function approveWithdrawal(HotelOwnerWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            throw new Exception('Only pending withdrawals can be approved.');
        }

        return DB::transaction(function() use ($withdrawal) {
            if (!$this->completeWithdrawal($withdrawal->amount)) {
                throw new Exception('Unable to complete withdrawal.');
            }

            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now()
            ]);

            return $withdrawal;
        });
    }

    /**
     * Process a withdrawal rejection
     */
    public function rejectWithdrawal(HotelOwnerWithdrawal $withdrawal, $reason = null)
    {
        if ($withdrawal->status !== 'pending') {
            throw new Exception('Only pending withdrawals can be rejected.');
        }

        return DB::transaction(function() use ($withdrawal, $reason) {
            if (!$this->releaseFromHold($withdrawal->amount)) {
                throw new Exception('Unable to release held funds.');
            }

            $withdrawal->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'notes' => $reason ? trim($withdrawal->notes . "\n\nRejection reason: " . $reason) : $withdrawal->notes
            ]);

            return $withdrawal;
        });
    }
}

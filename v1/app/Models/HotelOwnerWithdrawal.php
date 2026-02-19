<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelOwnerWithdrawal extends Model
{
    use HasFactory;

    protected $table = 'hotel_owner_withdrawals';

    protected $fillable = [
        'hotel_owner_wallet_id', 'amount', 'status', 'notes', 'requested_at', 'processed_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(HotelOwnerWallet::class, 'hotel_owner_wallet_id');
    }
}

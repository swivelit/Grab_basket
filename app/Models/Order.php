<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'seller_id',
        'buyer_id',
        'amount',
        'status',
        'delivery_partner_id',
        'paid_at',
        'payment_reference',
        'delivery_address',
        'delivery_city',
        'delivery_state',
        'delivery_pincode',
        'payment_method',
        'is_gift',
        'gift_message',
        'quantity',
        'tracking_number',
        'courier_name',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sellerUser()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyerUser()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }
}

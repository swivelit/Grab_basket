<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HotelOwner;

class FoodOrder extends Model
{
    protected $table = 'food_orders';

    protected $fillable = [
        'hotel_owner_id',
        'shop_name',
        'shop_address',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'food_total',
        'delivery_fee',
        'total_amount',
        'payment_method',
        'status',
        'payment_reference',
        'wallet_discount',
        'estimated_delivery_time',
        'delivery_partner_id'
    ];

    protected $casts = [
        'estimated_delivery_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(FoodOrderItem::class, 'food_order_id');
    }

    public function hotelOwner()
    {
        return $this->belongsTo(HotelOwner::class, 'hotel_owner_id');
    }

    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }

    public function getSubtotalAttribute()
    {
        return $this->food_total;
    }

    public function getAmountAttribute()
    {
        return $this->total_amount;
    }

    public function getDiscountAmountAttribute()
    {
        return 0.00;
    }
}
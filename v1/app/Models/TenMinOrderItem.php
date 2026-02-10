<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenMinOrderItem extends Model
{
    protected $table = 'ten_min_order_items';

    protected $fillable = [
        'ten_min_order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'seller_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'ten_min_order_id' => 'integer',
        'product_id' => 'integer',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(TenMinOrder::class, 'ten_min_order_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\TenMinDeliveryProduct::class, 'product_id');
    }
}
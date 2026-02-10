<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodOrderItem extends Model
{
    protected $table = 'food_order_items';

    protected $fillable = [
        'food_order_id',
        'food_item_id',
        'food_name',
        'price',
        'quantity',
        'food_type'
    ];

    public function foodOrder()
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class, 'food_item_id');
    }
}
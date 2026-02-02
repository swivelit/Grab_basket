<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\FoodCart;
use App\Models\FoodItem;
use App\Models\HotelOwner; // 👈 Ensure this exists

class FoodCartItem extends Model
{
    use HasFactory;

    protected $table = 'food_cart_items';

    protected $fillable = [
        'food_cart_id',
        'food_item_id',
        'quantity',
        'price',
        'name',
        'image_url',
        'food_type',
        'category',
        'hotel_owner_id'
    ];

    public function cart()
    {
        return $this->belongsTo(FoodCart::class, 'food_cart_id');
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class, 'food_item_id');
    }

    public function hotelOwner()
    {
        return $this->belongsTo(HotelOwner::class, 'hotel_owner_id'); // ✅ Fixed
    }

    public function getImageAttribute()
    {
        return $this->image_url ?? 'https://via.placeholder.com/150?text=No+Image';
    }
}
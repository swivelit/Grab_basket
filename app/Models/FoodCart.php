<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User; // 👈 ADD THIS

class FoodCart extends Model
{
    use HasFactory;

    protected $table = 'food_carts';
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(FoodCartItem::class, 'food_cart_id');
    }

    public static function forUser($user)
    {
        return self::firstOrCreate(['user_id' => $user->id]); // ✅ Fixed typo
    }
}
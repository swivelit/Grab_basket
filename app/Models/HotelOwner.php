<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;

class HotelOwner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'restaurant_name',
        'restaurant_address',
        'restaurant_phone',
        'cuisine_type',
        'description',
        'logo',
        'restaurant_images',
        'delivery_fee',
        'min_order_amount',
        'delivery_time',
        'rating',
        'total_orders',
        'is_active',
        'is_verified',
        'status',
        'opening_time',
        'closing_time',
        'operating_days',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'restaurant_images' => 'array',
        'operating_days' => 'array',
        'delivery_fee' => 'decimal:2',
        'rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    // Relationships
    public function foodItems()
    {
        return $this->hasMany(FoodItem::class);
    }

    // Note: Food delivery orders will be implemented later with a separate FoodOrder model
    // For now, we'll use placeholder relationships for dashboard compatibility

    // Helper methods
    public function isOpen()
    {
        if (!$this->opening_time || !$this->closing_time) {
            return false;
        }

        $now = now()->format('H:i:s');
        $today = strtolower(now()->format('l'));

        if (!in_array($today, $this->operating_days ?? [])) {
            return false;
        }

        return $now >= $this->opening_time && $now <= $this->closing_time;
    }

    public function getAverageRating()
    {
        return $this->orders()->whereNotNull('rating')->avg('rating') ?? 0;
    }
}

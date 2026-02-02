<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenMinDeliveryProduct extends Model
{
    protected $fillable = [
        'product_id',
        'unique_id',
        'name',
        'category_id',
        'subcategory_id',
        'seller_id',
        'description',
        'price',
        'discount',
        'delivery_charge',
        'image',
        'gift_option',
        'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'stock' => 'integer',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function seller()
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    // Get the image URL - Use Laravel Cloud URL
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        $imagePath = ltrim($this->image, '/');

        // Static public images shipped with app (e.g., images/srm/...)
        if (str_starts_with($imagePath, 'images/')) {
            return asset($imagePath);
        }

        // Product images - use direct Laravel Cloud public URL
        $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
        return "{$r2PublicUrl}/{$imagePath}";
    }
}
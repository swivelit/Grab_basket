<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenMinGroceryCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'seller_id',
        'name',
        'price',
        'image',
        'quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(TenMinDeliveryProduct::class, 'product_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id'); // or Seller::class if separate
    }

    // Get the image URL - Use Laravel Cloud URL
    public function getImageUrlAttribute()
    {
        // Try to use product's image_url first
        if ($this->product && $this->product->image_url) {
            return $this->product->image_url;
        }

        // Fallback to constructing from stored image path
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
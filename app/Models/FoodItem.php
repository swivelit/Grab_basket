<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FoodItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_owner_id',
        'name',
        'description',
        'price',
        'discounted_price',
        'category',
        'food_type',
        'images',
        'is_available',
        'is_popular',
        'preparation_time',
        'ingredients',
        'spice_level',
        'allergens',
        'calories',
        'rating',
        'total_orders',
        'sort_order',
        'image',
    ];

    protected $casts = [
        'images' => 'array',
        'allergens' => 'array',
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
    ];

    // Relationships
    // In FoodItem.php
    public function hotelOwner()
    {
        return $this->belongsTo(HotelOwner::class, 'hotel_owner_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id'); // Reuse existing OrderItem
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id'); // Reuse existing Review
    }

    // Helper methods
    public function getFinalPrice()
    {
        return $this->discounted_price ?? $this->price;
    }

    public function getDiscountPercentage()
    {
        if (!$this->discounted_price) {
            return 0;
        }

        return round((($this->price - $this->discounted_price) / $this->price) * 100);
    }

    public function isVegetarian()
    {
        return $this->food_type === 'veg';
    }

    public function isNonVegetarian()
    {
        return $this->food_type === 'non-veg';
    }
    // app/Models/FoodItem.php
    public function getFirstImageUrlAttribute()
    {
        // Check if single image field exists
        if (!empty($this->image)) {
            return $this->getImageUrl($this->image);
        }

        // Check images array
        if (!empty($this->images) && is_array($this->images) && !empty($this->images[0])) {
            return $this->getImageUrl($this->images[0]);
        }

        // No placeholder - return null if no image
        return null;
    }

    /**
     * Get image URL from AWS/R2 or serve-image route
     */
    private function getImageUrl($imagePath)
    {
        if (empty($imagePath)) {
            return 'https://via.placeholder.com/480x300?text=No+Image';
        }

        // Normalize slashes (replace all backslashes with forward slashes)
        $imagePath = str_replace('\\', '/', $imagePath);

        // If already a full URL, return as is
        if (strpos($imagePath, 'http') === 0) {
            return $imagePath;
        }

        // Remove any leading slashes for cloud storage path consistency
        $cleanPath = ltrim($imagePath, '/');

        // Priority: Cloudflare R2 direct URL (same logic as Product model)
        // Static public images shipped with app (e.g., images/...)
        if (str_starts_with($cleanPath, 'images/')) {
            return asset($cleanPath);
        }

        $r2PublicUrl = config('filesystems.disks.r2.url') ?: 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
        return rtrim($r2PublicUrl, '/') . '/' . $cleanPath;
    }

    private function isLaravelCloud() {
        // Explicit flag takes precedence
        if (env('LARAVEL_CLOUD_DEPLOYMENT') === true) {
            return true;
        }

        // Check bucket config
        if (!empty(config('filesystems.disks.r2.bucket'))) {
            return true;
        }

        // Check if running on Laravel Cloud based on server name
        if (app()->environment('production') && 
            isset($_SERVER['SERVER_NAME']) && 
            str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud')) {
            return true;
        }

        return false;
    }

    /**
     * Get all image URLs
     */
    public function getImageUrlsAttribute()
    {
        $urls = [];

        // Add single image if exists
        if (!empty($this->image)) {
            $urls[] = $this->getImageUrl($this->image);
        }

        // Add images from array
        if (!empty($this->images) && is_array($this->images)) {
            foreach ($this->images as $img) {
                if (!empty($img)) {
                    $url = $this->getImageUrl($img);
                    if (!in_array($url, $urls)) {
                        $urls[] = $url;
                    }
                }
            }
        }

        return $urls; // Return empty array if no images
    }
}

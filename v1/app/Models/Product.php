<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name',
        'unique_id',
        'category_id',
        'subcategory_id',
        'seller_id',
        'image',
        'image_data',
        'image_mime_type',
        'image_size',
        'description',
        'price',
        'discount',
        'delivery_charge',
        'gift_option',
        'stock',
        'delivery_district',
        'available_for_10min',
        'delivery_radius_km',
    ];

    // Seller relationship - references users table (seller_id references users.id)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    
    // Get seller info from sellers table via email match
    public function getSellerInfoAttribute()
    {
        if (!$this->seller) return null;
        return \App\Models\Seller::where('email', $this->seller->email)->first();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    // Alias for productImages - for compatibility
    public function images()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->primary();
    }

    public function isWishlistedBy($user)
    {
        if (!$user) return false;
        return $this->wishlists()->where('user_id', $user->id)->exists();
    }

    // Get the final selling price after discount
    public function getFinalPriceAttribute()
    {
        if ($this->discount > 0) {
            return $this->price * (1 - $this->discount / 100);
        }
        return $this->price;
    }

    // Get the savings amount
    public function getSavingsAttribute()
    {
        if ($this->discount > 0) {
            return $this->price - $this->final_price;
        }
        return 0;
    }

    // Get the correct image URL (supporting direct URLs, file storage and database storage)
    public function getImageUrlAttribute()
    {
        // Priority 1: Primary image from product_images table
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return $primaryImage->image_url;
        }

        // Priority 2: First image from product_images table
        $firstImage = $this->productImages()->first();
        if ($firstImage) {
            return $firstImage->image_url;
        }

        // Priority 3: Legacy single image field
        if ($this->image) {
            return $this->getLegacyImageUrl();
        }

        // Priority 4: Database stored image
        if ($this->image_data && $this->image_mime_type) {
            return "data:{$this->image_mime_type};base64,{$this->image_data}";
        }
        
    // Priority 5: No image available
    return null;
    }

    // Helper method for legacy image URL generation
    public function getLegacyImageUrl()
    {
        // Priority 1: Direct external image URL (https:// or GitHub raw)
        if ($this->image && (str_starts_with($this->image, 'https://') || str_starts_with($this->image, 'http://'))) {
            return $this->image;
        }
        
        // Priority 2: File/system-stored image
        if ($this->image) {
            $imagePath = ltrim($this->image, '/');

            // Case A: Static public images shipped with app (e.g., images/srm/...)
            if (str_starts_with($imagePath, 'images/')) {
                // Serve directly from public/images in both envs
                return asset($imagePath);
            }

            // Case B: Product images - use direct R2 public URL
            // Use AWS_URL from environment (Laravel Cloud managed storage)
            $r2PublicUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
            return "{$r2PublicUrl}/{$imagePath}";
        }

        return null;
    }

    /**
     * Check if running on Laravel Cloud
     */
    private function isLaravelCloud()
    {
        // Explicit flag takes precedence
        if (env('LARAVEL_CLOUD_DEPLOYMENT') === true) {
            return true;
        }

        // Check if running on Laravel Cloud based on server name
        if (app()->environment('production') && 
            isset($_SERVER['SERVER_NAME']) && 
            str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud')) {
            return true;
        }

        // Check for Laravel Vapor environment
        if (env('VAPOR_ENVIRONMENT') !== null) {
            return true;
        }

        return false;
    }

    // Provide the original, direct image URL for showcasing (prefer gallery primary)
    public function getOriginalImageUrlAttribute()
    {
        // Prefer gallery primary original
        $primary = $this->primaryImage;
        if ($primary && $primary->original_url) {
            return $primary->original_url;
        }
        // Else first gallery
        $first = $this->productImages()->first();
        if ($first && $first->original_url) {
            return $first->original_url;
        }
        // Else legacy single image
        if ($this->image) {
            $imagePath = ltrim($this->image, '/');
            if (str_starts_with($imagePath, 'images/')) {
                return '/' . $imagePath;
            }
            $r2Base = config('filesystems.disks.r2.url');
            if (!empty($r2Base)) {
                return rtrim($r2Base, '/') . '/' . $imagePath;
            }
            // Fallback to serve-image for cloud environments where storage symlink is not applicable
            $pathParts = explode('/', $imagePath, 2);
            if (count($pathParts) === 2) {
                return rtrim(config('app.url'), '/') . '/serve-image/' . $pathParts[0] . '/' . $pathParts[1];
            }
            return (app()->environment('production') ? rtrim(config('app.url'), '/') : '') . '/storage/' . $imagePath;
        }
        // Else fallback to computed
        return null;
    }

    // Store image in database
    public function storeImageInDatabase($imageFile)
    {
        if (!$imageFile || !$imageFile->isValid()) {
            return false;
        }

        try {
            $imageData = base64_encode(file_get_contents($imageFile->getPathname()));
            $mimeType = $imageFile->getMimeType();
            $size = $imageFile->getSize();

            $this->update([
                'image_data' => $imageData,
                'image_mime_type' => $mimeType,
                'image_size' => $size,
                'image' => null // Clear file path since we're using database storage
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to store image in database: ' . $e->getMessage());
            return false;
        }
    }

    // Get image size in human readable format
    public function getImageSizeFormattedAttribute()
    {
        if (!$this->image_size) return null;
        
        if ($this->image_size < 1024) {
            return $this->image_size . ' B';
        } elseif ($this->image_size < 1048576) {
            return round($this->image_size / 1024, 2) . ' KB';
        } else {
            return round($this->image_size / 1048576, 2) . ' MB';
        }
    }
}
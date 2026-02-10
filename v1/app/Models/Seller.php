<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Seller extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'email', 
        'phone', 
        'billing_address', 
        'state', 
        'city', 
        'pincode', 
        'password', 
        'sex', 
        'gift_option', 
        'stock',
        'store_name', 
        'gst_number', 
        'store_address', 
        'store_contact',
        'available_for_10_min_delivery',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'delivery_mode'
    ];
    
    /**
     * Get products from this seller
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }
    
    /**
     * Check if seller is available for 10-minute delivery
     */
    public function isAvailableFor10MinDelivery()
    {
        return $this->available_for_10_min_delivery && in_array($this->delivery_mode, ['10-minute', 'both']);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_partner_id',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'distance_km',
        'estimated_time_minutes',
        'delivery_fee',
        'status',
        'requested_at',
        'accepted_at',
        'pickup_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'priority',
        'expires_at',
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'pickup_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the order associated with the delivery request
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the delivery partner assigned to the request
     */
    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted requests
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for active requests (not cancelled or completed)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    /**
     * Find nearby delivery partners within specified radius
     */
    public static function findNearbyPartners($latitude, $longitude, $radiusKm = 5, $limit = 10)
    {
        return DB::select("
            SELECT dp.*, 
                   (6371 * acos(cos(radians(?)) 
                   * cos(radians(dp.current_latitude)) 
                   * cos(radians(dp.current_longitude) - radians(?)) 
                   + sin(radians(?)) 
                   * sin(radians(dp.current_latitude)))) AS distance_km
            FROM delivery_partners dp 
            WHERE dp.is_online = 1 
              AND dp.status = 'available'
              AND dp.current_latitude IS NOT NULL 
              AND dp.current_longitude IS NOT NULL
            HAVING distance_km < ?
            ORDER BY distance_km ASC 
            LIMIT ?
        ", [$latitude, $longitude, $latitude, $radiusKm, $limit]);
    }

    /**
     * Calculate distance between two coordinates
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Accept delivery request
     */
    public function acceptRequest($deliveryPartnerId)
    {
        $this->delivery_partner_id = $deliveryPartnerId;
        $this->status = 'accepted';
        $this->accepted_at = now();
        return $this->save();
    }

    /**
     * Mark as picked up
     */
    public function markPickedUp()
    {
        $this->status = 'picked_up';
        $this->pickup_at = now();
        return $this->save();
    }

    /**
     * Complete delivery and add payment to wallet
     */
    public function completeDelivery()
    {
        if ($this->status !== 'picked_up') {
            return false;
        }

        $this->status = 'completed';
        $this->delivered_at = now();
        
        if ($this->save() && $this->delivery_partner_id) {
            // Add delivery payment to wallet
            $wallet = DeliveryPartnerWallet::where('delivery_partner_id', $this->delivery_partner_id)->first();
            if ($wallet) {
                return $wallet->addDeliveryPayment($this->delivery_fee, $this->order_id, 'Delivery completed for Order #' . $this->order_id);
            }
        }
        
        return true;
    }

    /**
     * Cancel delivery request
     */
    public function cancelRequest($reason = null)
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    /**
     * Check if request is expired
     */
    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'accepted' => 'info',
            'picked_up' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get formatted delivery fee
     */
    public function getFormattedFeeAttribute()
    {
        return '₹' . number_format((float)$this->delivery_fee, 2);
    }

    /**
     * Get estimated delivery time
     */
    public function getEstimatedDeliveryAttribute()
    {
        if ($this->accepted_at) {
            return $this->accepted_at->addMinutes($this->estimated_time_minutes);
        }
        return $this->requested_at->addMinutes($this->estimated_time_minutes);
    }
}
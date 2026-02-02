<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class DeliveryPartner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'delivery_partners';

    protected $guard = 'delivery_partner';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'pincode',
        'date_of_birth',
        'gender',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'license_expiry',
        'vehicle_rc_number',
        'insurance_number',
        'insurance_expiry',
        'profile_photo',
        'license_photo',
        'vehicle_photo',
        'aadhar_number',
        'aadhar_photo',
        'pan_number',
        'pan_photo',
        'bank_account_holder',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_name',
        'status',
        'is_verified',
        'is_online',
        'is_available',
        'current_order_id',
        'current_order_type',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'current_address',
        'rating',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'total_earnings',
        'this_month_earnings',
        'working_hours',
        'max_delivery_distance',
        'cash_on_delivery_enabled',
        'online_payment_enabled',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'admin_notes',
        'approved_at',
        'last_active_at',
        'registration_type'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'aadhar_number',
        'pan_number',
        'bank_account_number'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'license_expiry' => 'date',
        'insurance_expiry' => 'date',
        'is_verified' => 'boolean',
        'is_online' => 'boolean',
        'is_available' => 'boolean',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'location_updated_at' => 'datetime',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'this_month_earnings' => 'decimal:2',
        'working_hours' => 'array',
        'cash_on_delivery_enabled' => 'boolean',
        'online_payment_enabled' => 'boolean',
        'approved_at' => 'datetime',
        'last_active_at' => 'datetime'
    ];

    /**
     * Get the orders assigned to this delivery partner.
     */
    public function currentOrder(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Assign an order to the partner
     */
    public function assignOrder($order): void
    {
        $this->update([
            'current_order_id' => $order->id,
            'current_order_type' => get_class($order),
            'is_available' => false
        ]);

        $order->update(['delivery_partner_id' => $this->id]);
    }

    /**
     * Clear the current order and make partner available
     */
    public function clearOrder(): void
    {
        $this->update([
            'current_order_id' => null,
            'current_order_type' => null,
            'is_available' => true
        ]);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_partner_id');
    }

    public function deliveryRequests(): HasMany
    {
        return $this->hasMany(DeliveryRequest::class, 'delivery_partner_id');
    }

    /**
     * Get the wallet associated with the delivery partner.
     */
    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeliveryPartnerWallet::class, 'delivery_partner_id');
    }

    /**
     * Get the food orders assigned to this delivery partner.
     */
    public function foodOrders(): HasMany
    {
        return $this->hasMany(FoodOrder::class, 'delivery_partner_id');
    }

    /**
     * Get the 10-min grocery orders assigned to this delivery partner.
     */
    public function tenMinOrders(): HasMany
    {
        return $this->hasMany(TenMinOrder::class, 'delivery_partner_id');
    }

    /**
     * Scope to get only approved partners.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get only available partners.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('is_online', true)
            ->where('status', 'approved');
    }

    /**
     * Scope to get partners in a specific city.
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Check if partner is approved and verified to perform deliveries.
     * This checks the fundamental eligibility, not the current real-time status.
     */
    public function canPerformDeliveries(): bool
    {
        return $this->status === 'approved' &&
            $this->is_verified === true &&
            $this->status !== 'suspended' &&
            $this->status !== 'rejected';
    }

    /**
     * Check if partner is available for delivery right now.
     */
    public function isAvailableForDelivery(): bool
    {
        return $this->canPerformDeliveries() &&
            $this->is_online === true &&
            $this->is_available === true;
    }

    /**
     * Check if the partner is allowed to go online.
     */
    public function canGoOnline(): bool
    {
        return $this->canPerformDeliveries();
    }

    /**
     * Get partner's current location.
     */
    public function getCurrentLocation(): ?array
    {
        if ($this->current_latitude && $this->current_longitude) {
            return [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'address' => $this->current_address,
                'updated_at' => $this->location_updated_at
            ];
        }
        return null;
    }

    /**
     * Update partner's location.
     */
    public function updateLocation(float $latitude, float $longitude, ?string $address = null): bool
    {
        return $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'current_address' => $address,
            'location_updated_at' => now(),
            'last_active_at' => now()
        ]);
    }

    /**
     * Update last active timestamp.
     */
    public function touchActivity(): bool
    {
        return $this->update(['last_active_at' => now()]);
    }

    /**
     * Calculate distance from a given location.
     */
    public function distanceFrom(float $latitude, float $longitude): ?float
    {
        if (!$this->current_latitude || !$this->current_longitude) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latFrom = deg2rad((float) $this->current_latitude);
        $lonFrom = deg2rad((float) $this->current_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Check if partner is within delivery radius of a location.
     */
    public function canDeliverTo(float $latitude, float $longitude): bool
    {
        $distance = $this->distanceFrom($latitude, $longitude);
        return $distance !== null && $distance <= $this->max_delivery_distance;
    }

    /**
     * Get partner's completion rate.
     */
    public function getCompletionRateAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 100.0;
        }
        return round(($this->completed_orders / $this->total_orders) * 100, 2);
    }

    /**
     * Get partner's cancellation rate.
     */
    public function getCancellationRateAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 0.0;
        }
        return round(($this->cancelled_orders / $this->total_orders) * 100, 2);
    }

    /**
     * Check if partner is working today.
     */
    public function isWorkingToday(): bool
    {
        if (!$this->working_hours) {
            return true; // If no hours set, assume always working
        }

        $today = strtolower(Carbon::now()->format('l')); // monday, tuesday, etc.
        $todayHours = $this->working_hours[$today] ?? null;

        if (!$todayHours || !isset($todayHours['start']) || !isset($todayHours['end'])) {
            return false;
        }

        $currentTime = Carbon::now()->format('H:i');
        return $currentTime >= $todayHours['start'] && $currentTime <= $todayHours['end'];
    }

    /**
     * Mark partner as online.
     */
    public function goOnline(): bool
    {
        return $this->update([
            'is_online' => true,
            'is_available' => $this->current_order_id ? false : true,
            'last_active_at' => now()
        ]);
    }

    /**
     * Mark partner as offline.
     */
    public function goOffline(): bool
    {
        return $this->update([
            'is_online' => false,
            'is_available' => false
        ]);
    }

    /**
     * Toggle availability.
     */
    public function toggleAvailability(): bool
    {
        return $this->update([
            'is_available' => !$this->is_available,
            'last_active_at' => now()
        ]);
    }

    /**
     * Get profile photo URL.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            if (str_starts_with($this->profile_photo, 'http')) {
                return $this->profile_photo;
            }
            return asset('storage/' . $this->profile_photo);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get document URL.
     */
    public function getDocumentUrl(string $documentField): ?string
    {
        $path = $this->{$documentField};
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return asset('storage/' . $path);
    }

    /**
     * Update earnings after completing an order.
     */
    public function addEarnings(float $amount): bool
    {
        return $this->update([
            'total_earnings' => $this->total_earnings + $amount,
            'this_month_earnings' => $this->this_month_earnings + $amount
        ]);
    }

    /**
     * Reset monthly earnings (to be called at month start).
     */
    public function resetMonthlyEarnings(): bool
    {
        return $this->update(['this_month_earnings' => 0]);
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">Pending Review</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'suspended' => '<span class="badge bg-secondary">Suspended</span>',
            'inactive' => '<span class="badge bg-light text-dark">Inactive</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get online status badge.
     */
    public function getOnlineStatusBadgeAttribute(): string
    {
        if (!$this->is_online) {
            return '<span class="badge bg-secondary">Offline</span>';
        }

        if ($this->is_available) {
            return '<span class="badge bg-success">Available</span>';
        }

        return '<span class="badge bg-warning">Busy</span>';
    }

    /**
     * Check if documents are complete.
     */
    public function hasCompleteDocuments(): bool
    {
        $requiredDocs = [
            'license_photo',
            'vehicle_photo',
            'aadhar_photo'
        ];

        foreach ($requiredDocs as $doc) {
            if (!$this->{$doc}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get pending orders count.
     */
    public function getPendingOrdersCountAttribute(): int
    {
        return $this->orders()
            ->whereIn('delivery_status', ['assigned', 'picked_up', 'in_transit'])
            ->count();
    }

    /**
     * Get today's earnings (₹30 per delivery).
     */
    public function getTodayEarningsAttribute(): float
    {
        $today = Carbon::today();

        $standardCount = $this->deliveryRequests()
            ->where('status', 'completed')
            ->whereDate('delivered_at', $today)
            ->count();

        $foodCount = $this->foodOrders()
            ->where('status', 'delivered')
            ->whereDate('updated_at', $today)
            ->count();

        $tenMinCount = $this->tenMinOrders()
            ->where('status', 'delivered')
            ->whereDate('updated_at', $today)
            ->count();

        return (float) (($standardCount + $foodCount + $tenMinCount) * 30);
    }

    /**
     * Get total earnings (₹30 per delivery).
     */
    public function getTotalEarningsAllAttribute(): float
    {
        $standardCount = $this->deliveryRequests()
            ->where('status', 'completed')
            ->count();

        $foodCount = $this->foodOrders()
            ->where('status', 'delivered')
            ->count();

        $tenMinCount = $this->tenMinOrders()
            ->where('status', 'delivered')
            ->count();

        return (float) (($standardCount + $foodCount + $tenMinCount) * 30);
    }

    /**
     * Get total deliveries count across all types.
     */
    public function getTotalDeliveriesCountAttribute(): int
    {
        return $this->deliveryRequests()->count() +
            $this->foodOrders()->count() +
            $this->tenMinOrders()->count();
    }

    /**
     * Get completed deliveries count across all types.
     */
    public function getCompletedDeliveriesCountAttribute(): int
    {
        $standard = $this->deliveryRequests()->where('status', 'completed')->count();
        $food = $this->foodOrders()->where('status', 'delivered')->count();
        $tenMin = $this->tenMinOrders()->where('status', 'delivered')->count();

        return $standard + $food + $tenMin;
    }

    /**
     * Get pending deliveries count across all types.
     */
    public function getPendingDeliveriesCountAllAttribute(): int
    {
        $standard = $this->deliveryRequests()->whereIn('status', ['accepted', 'picked_up'])->count();
        $food = $this->foodOrders()->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery'])->count();
        $tenMin = $this->tenMinOrders()->whereIn('status', ['assigned', 'picked_up', 'out_for_delivery'])->count();

        return $standard + $food + $tenMin;
    }

    /**
     * Get today's deliveries count across all types.
     */
    public function getTodayDeliveriesCountAttribute(): int
    {
        $today = Carbon::today();
        $standard = $this->deliveryRequests()->where('status', 'completed')->whereDate('delivered_at', $today)->count();
        $food = $this->foodOrders()->where('status', 'delivered')->whereDate('updated_at', $today)->count();
        $tenMin = $this->tenMinOrders()->where('status', 'delivered')->whereDate('updated_at', $today)->count();

        return $standard + $food + $tenMin;
    }
}

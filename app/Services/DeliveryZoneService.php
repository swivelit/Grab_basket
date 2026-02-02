<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DeliveryZoneService
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Get products filtered by delivery type and district
     */
    public static function getFilteredProducts($deliveryType = 'standard', $perPage = 20)
    {
        $query = Product::with(['category', 'subcategory', 'seller']);

        // If user is logged in
        if (Auth::check()) {
            $user = Auth::user();

            if ($deliveryType === 'express_10min' || $deliveryType === '10min') {
                // 10-minute delivery: Only show products available in user's district within 5km
                if ($user->district) {
                    $query->where('delivery_district', $user->district)
                        ->where('available_for_10min', true);
                }

                // If user has coordinates, use distance-based filtering
                if ($user->latitude && $user->longitude) {
                    // Get nearby sellers within 5km
                    $nearbySellers = \DB::select(
                        "SELECT id, store_latitude, store_longitude,
                         (6371 * acos(cos(radians(?)) * cos(radians(store_latitude)) *
                         cos(radians(store_longitude) - radians(?)) + sin(radians(?)) *
                         sin(radians(store_latitude)))) AS distance
                         FROM sellers
                         WHERE store_latitude IS NOT NULL
                         AND store_longitude IS NOT NULL
                         AND (6371 * acos(cos(radians(?)) * cos(radians(store_latitude)) *
                         cos(radians(store_longitude) - radians(?)) + sin(radians(?)) *
                         sin(radians(store_latitude)))) <= 5
                         ORDER BY distance",
                        [$user->latitude, $user->longitude, $user->latitude,
                         $user->latitude, $user->longitude, $user->latitude]
                    );

                    $sellerIds = collect($nearbySellers)->pluck('id')->toArray();
                    $query->whereIn('seller_id', $sellerIds);
                }

                $query->limit(50); // Limited selection for 10-min delivery
            } else {
                // Standard delivery: Show all products
                // But prioritize district if user has one
                if ($user->district) {
                    $query->orderByRaw("CASE WHEN delivery_district = ? THEN 0 ELSE 1 END", [$user->district]);
                }
            }
        } else {
            // Guest users - show only standard delivery products
            if ($deliveryType === 'express_10min' || $deliveryType === '10min') {
                $query->where('available_for_10min', false); // No 10-min for guests
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Check if a product is deliverable to user's location within 10 minutes
     */
    public static function isDeliverableIn10Minutes($product, $user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        // Check if product is available for 10-min delivery
        if (!$product->available_for_10min) {
            return false;
        }

        // Check district match
        if ($product->delivery_district && $user->district) {
            if ($product->delivery_district !== $user->district) {
                return false;
            }
        }

        // Check distance if coordinates available
        if ($user->latitude && $user->longitude && $product->seller->store_latitude && $product->seller->store_longitude) {
            $distance = self::calculateDistance(
                $user->latitude,
                $user->longitude,
                $product->seller->store_latitude,
                $product->seller->store_longitude
            );

            if ($distance > ($product->delivery_radius_km ?? 5)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user's delivery zone
     */
    public static function getUserDeliveryZone($user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return null;
        }

        return [
            'district' => $user->district,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'city' => $user->city,
            'state' => $user->state,
        ];
    }

    /**
     * Get nearby stores for 10-min delivery
     */
    public static function getNearbyStores($user = null, $radiusKm = 5)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user || !$user->latitude || !$user->longitude) {
            return collect();
        }

        $stores = \DB::select(
            "SELECT id, name, email, store_name, store_latitude, store_longitude, district,
             (6371 * acos(cos(radians(?)) * cos(radians(store_latitude)) *
             cos(radians(store_longitude) - radians(?)) + sin(radians(?)) *
             sin(radians(store_latitude)))) AS distance
             FROM sellers
             WHERE store_latitude IS NOT NULL
             AND store_longitude IS NOT NULL
             AND (6371 * acos(cos(radians(?)) * cos(radians(store_latitude)) *
             cos(radians(store_longitude) - radians(?)) + sin(radians(?)) *
             sin(radians(store_latitude)))) <= ?
             ORDER BY distance",
            [$user->latitude, $user->longitude, $user->latitude,
             $user->latitude, $user->longitude, $user->latitude, $radiusKm]
        );

        return collect($stores);
    }
}

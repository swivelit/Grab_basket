<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickDeliveryService
{
    /**
     * Check if address is eligible for 10-minute delivery
     */
    public static function checkEligibility($latitude, $longitude, $storeLatitude, $storeLongitude): array
    {
        $distance = self::calculateDistance($latitude, $longitude, $storeLatitude, $storeLongitude);
        
        // Get radius from configuration (default 5km)
        $radiusKm = config('warehouse.delivery.quick_delivery_radius_km', 5.0);
        $isEligible = $distance <= $radiusKm;
        
        // Get delivery time from configuration (default 10 minutes)
        $quickDeliveryTime = config('warehouse.delivery.quick_delivery_time_minutes', 10);
        $standardDeliverySpeed = config('warehouse.delivery.standard_delivery_speed_kmh', 5);
        
        return [
            'eligible' => $isEligible,
            'distance_km' => round($distance, 2),
            'eta_minutes' => $isEligible ? $quickDeliveryTime : ceil($distance / $standardDeliverySpeed * 60),
            'message' => $isEligible 
                ? '⚡ 10-Minute Delivery Available!' 
                : '🚚 Standard Delivery (Within ' . ceil($distance / $standardDeliverySpeed * 60) . ' minutes)'
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Get coordinates from Google Geocoding API
     */
    public static function getCoordinates($address, $city, $state, $pincode): ?array
    {
        try {
            $apiKey = config('services.google.maps_api_key');
            
            if (!$apiKey) {
                Log::warning('Google Maps API key not configured');
                return null;
            }

            $fullAddress = "{$address}, {$city}, {$state} {$pincode}, India";
            
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $fullAddress,
                'key' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];
                    
                    return [
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng']
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Geocoding error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Assign delivery partner (Mock - replace with real delivery partner API)
     */
    public static function assignDeliveryPartner(Order $order): array
    {
        // Mock delivery partners pool
        $partners = [
            ['name' => 'Rajesh Kumar', 'phone' => '+91-9876543210', 'vehicle' => 'Bike - KA01AB1234'],
            ['name' => 'Amit Sharma', 'phone' => '+91-9876543211', 'vehicle' => 'Bike - KA01CD5678'],
            ['name' => 'Priya Singh', 'phone' => '+91-9876543212', 'vehicle' => 'Scooter - KA01EF9012'],
            ['name' => 'Vikram Patel', 'phone' => '+91-9876543213', 'vehicle' => 'Bike - KA01GH3456'],
            ['name' => 'Anjali Reddy', 'phone' => '+91-9876543214', 'vehicle' => 'Bike - KA01IJ7890'],
        ];

        $partner = $partners[array_rand($partners)];
        
        $order->update([
            'delivery_partner_name' => $partner['name'],
            'delivery_partner_phone' => $partner['phone'],
            'delivery_partner_vehicle' => $partner['vehicle'],
            'delivery_started_at' => now(),
            'delivery_promised_at' => now()->addMinutes($order->delivery_type === 'express_10min' ? 10 : 30),
            'eta_minutes' => $order->delivery_type === 'express_10min' ? 10 : 30,
        ]);

        return $partner;
    }

    /**
     * Update delivery partner live location (Mock - would come from partner app)
     */
    public static function updateDeliveryLocation(Order $order, $latitude, $longitude): void
    {
        $order->update([
            'delivery_latitude' => $latitude,
            'delivery_longitude' => $longitude,
            'location_updated_at' => now(),
        ]);

        // Recalculate ETA based on current location
        if ($order->customer_latitude && $order->customer_longitude) {
            $remainingDistance = self::calculateDistance(
                $latitude,
                $longitude,
                $order->customer_latitude,
                $order->customer_longitude
            );

            // Assume average speed of 20 km/h in city
            $etaMinutes = ceil(($remainingDistance / 20) * 60);
            
            $order->update([
                'eta_minutes' => max(1, $etaMinutes),
                'distance_km' => round($remainingDistance, 2)
            ]);
        }
    }

    /**
     * Simulate live tracking (for demo purposes)
     */
    public static function simulateLiveTracking(Order $order): array
    {
        if (!$order->store_latitude || !$order->customer_latitude) {
            return [];
        }

        // Generate intermediate points between store and customer
        $waypoints = [];
        $steps = 10;

        for ($i = 0; $i <= $steps; $i++) {
            $ratio = $i / $steps;
            $lat = $order->store_latitude + ($order->customer_latitude - $order->store_latitude) * $ratio;
            $lng = $order->store_longitude + ($order->customer_longitude - $order->store_longitude) * $ratio;
            
            $waypoints[] = [
                'lat' => $lat,
                'lng' => $lng,
                'step' => $i
            ];
        }

        return $waypoints;
    }

    /**
     * Get Google Maps route URL
     */
    public static function getGoogleMapsRoute(Order $order): ?string
    {
        if (!$order->delivery_latitude || !$order->customer_latitude) {
            return null;
        }

        $origin = "{$order->delivery_latitude},{$order->delivery_longitude}";
        $destination = "{$order->customer_latitude},{$order->customer_longitude}";

        return "https://www.google.com/maps/dir/?api=1&origin={$origin}&destination={$destination}&travelmode=driving";
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeliveryRequest;
use App\Models\Order;


class DeliveryRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();
        
        // Sample locations in a city (e.g., Chennai)
        $locations = [
            ['lat' => 13.0827, 'lng' => 80.2707, 'address' => 'T. Nagar, Chennai'],
            ['lat' => 13.0569, 'lng' => 80.2427, 'address' => 'Anna Nagar, Chennai'],
            ['lat' => 13.1135, 'lng' => 80.2462, 'address' => 'Kilpauk, Chennai'],
            ['lat' => 13.0732, 'lng' => 80.2609, 'address' => 'Egmore, Chennai'],
            ['lat' => 13.0475, 'lng' => 80.2574, 'address' => 'Vadapalani, Chennai'],
            ['lat' => 13.0394, 'lng' => 80.2298, 'address' => 'Porur, Chennai'],
            ['lat' => 13.1062, 'lng' => 80.2101, 'address' => 'Aminjikarai, Chennai'],
            ['lat' => 13.0915, 'lng' => 80.2337, 'address' => 'Chetpet, Chennai'],
            ['lat' => 13.0648, 'lng' => 80.2348, 'address' => 'Nungambakkam, Chennai'],
            ['lat' => 13.0524, 'lng' => 80.2195, 'address' => 'Ashok Nagar, Chennai'],
        ];

        // Create some sample orders if they don't exist
        if (Order::count() === 0) {
            for ($i = 1; $i <= 10; $i++) {
                Order::create([
                    'user_id' => 1, // Assuming user with ID 1 exists
                    'total_amount' => $faker->randomFloat(2, 50, 500),
                    'status' => 'confirmed',
                    'created_at' => $faker->dateTimeBetween('-1 week', 'now'),
                ]);
            }
        }

        $orders = Order::limit(10)->get();

        // Create delivery requests
        foreach ($orders as $index => $order) {
            $pickupLocation = $locations[array_rand($locations)];
            $deliveryLocation = $locations[array_rand($locations)];
            
            // Ensure pickup and delivery are different
            while ($pickupLocation === $deliveryLocation) {
                $deliveryLocation = $locations[array_rand($locations)];
            }

            // Calculate distance (simple approximation)
            $distance = $this->calculateDistance(
                $pickupLocation['lat'], 
                $pickupLocation['lng'],
                $deliveryLocation['lat'], 
                $deliveryLocation['lng']
            );

            $estimatedTime = max(15, $distance * 3); // 3 minutes per km, minimum 15 minutes

            DeliveryRequest::create([
                'order_id' => $order->id,
                'pickup_address' => $pickupLocation['address'],
                'pickup_latitude' => $pickupLocation['lat'],
                'pickup_longitude' => $pickupLocation['lng'],
                'delivery_address' => $deliveryLocation['address'],
                'delivery_latitude' => $deliveryLocation['lat'],
                'delivery_longitude' => $deliveryLocation['lng'],
                'distance_km' => round($distance, 2),
                'estimated_time_minutes' => round($estimatedTime),
                'delivery_fee' => 25.00, // Standard ₹25 fee
                'status' => $index < 3 ? 'pending' : ($index < 7 ? 'completed' : 'pending'),
                'priority' => $faker->randomElement(['low', 'medium', 'high']),
                'requested_at' => $faker->dateTimeBetween('-2 hours', 'now'),
                'expires_at' => now()->addHours(2),
                'notes' => $faker->optional(0.3)->sentence(),
            ]);
        }

        $this->command->info('Created ' . $orders->count() . ' delivery requests');
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
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
}

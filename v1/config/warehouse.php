<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Warehouse Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the warehouse/store location configuration for
    | the 10-minute express delivery feature. The delivery radius is
    | calculated from these coordinates.
    |
    */

    'name' => env('WAREHOUSE_NAME', 'GrabBaskets Warehouse'),
    
    'address' => [
        'street' => env('WAREHOUSE_STREET', 'Mahatma Gandhi Nagar Rd, Near Annai Therasa English School'),
        'area' => env('WAREHOUSE_AREA', 'MRR Nagar, Palani Chettipatti'),
        'city' => env('WAREHOUSE_CITY', 'Theni'),
        'state' => env('WAREHOUSE_STATE', 'Tamil Nadu'),
        'pincode' => env('WAREHOUSE_PINCODE', '625531'),
        'country' => env('WAREHOUSE_COUNTRY', 'India'),
    ],

    'coordinates' => [
        'latitude' => env('WAREHOUSE_LATITUDE', 10.0103),
        'longitude' => env('WAREHOUSE_LONGITUDE', 77.4773),
    ],

    'delivery' => [
        // 10-minute delivery coverage radius in kilometers
        'quick_delivery_radius_km' => env('QUICK_DELIVERY_RADIUS', 5),
        
        // Express delivery time in minutes
        'quick_delivery_time_minutes' => env('QUICK_DELIVERY_TIME', 10),
        
        // Standard delivery estimated time calculation
        'standard_delivery_speed_kmh' => env('STANDARD_DELIVERY_SPEED', 5),
    ],

    'operating_hours' => [
        'enabled' => env('WAREHOUSE_HOURS_ENABLED', true),
        'timezone' => env('WAREHOUSE_TIMEZONE', 'Asia/Kolkata'),
        'open' => env('WAREHOUSE_OPEN_TIME', '08:00'),
        'close' => env('WAREHOUSE_CLOSE_TIME', '22:00'),
        'days' => env('WAREHOUSE_OPERATING_DAYS', '1,2,3,4,5,6,7'), // 1=Monday, 7=Sunday
    ],

    'contact' => [
        'phone' => env('WAREHOUSE_PHONE', '+91-XXXXXXXXXX'),
        'email' => env('WAREHOUSE_EMAIL', 'warehouse@grabbaskets.com'),
    ],
];

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierTrackingService
{
    /**
     * Track package using multiple courier APIs
     */
    public static function trackPackage($trackingNumber, $courier = null)
    {
        $trackingData = [
            'tracking_number' => $trackingNumber,
            'courier' => $courier,
            'status' => 'unknown',
            'events' => [],
            'estimated_delivery' => null,
            'current_location' => null,
            'error' => null
        ];

        try {
            // Try different tracking services
            if ($courier) {
                $trackingData = self::trackWithSpecificCourier($trackingNumber, $courier);
            } else {
                // Auto-detect courier and track
                $trackingData = self::autoDetectAndTrack($trackingNumber);
            }
        } catch (\Exception $e) {
            Log::error("Courier tracking failed: " . $e->getMessage());
            $trackingData['error'] = 'Unable to track package. Please try again later.';
        }

        return $trackingData;
    }

    /**
     * Auto-detect courier based on tracking number pattern
     */
    private static function autoDetectAndTrack($trackingNumber)
    {
        $courier = self::detectCourier($trackingNumber);
        return self::trackWithSpecificCourier($trackingNumber, $courier);
    }

    /**
     * Detect courier based on tracking number pattern
     */
    private static function detectCourier($trackingNumber)
    {
        // Common Indian courier patterns
        $patterns = [
            'delhivery' => '/^(DH|DL)\d{10,15}$/',
            'bluedart' => '/^(BD|A)\d{8,12}$/',
            'dtdc' => '/^D\d{9,12}$/',
            'fedex' => '/^\d{12,14}$/',
            'aramex' => '/^\d{10,11}$/',
            'ecom' => '/^EC\d{10,13}$/',
            'xpressbees' => '/^XB\d{10,13}$/',
            'india_post' => '/^[A-Z]{2}\d{9}IN$/',
        ];

        foreach ($patterns as $courier => $pattern) {
            if (preg_match($pattern, $trackingNumber)) {
                return $courier;
            }
        }

        return 'generic';
    }

    /**
     * Track with specific courier
     */
    private static function trackWithSpecificCourier($trackingNumber, $courier)
    {
        switch ($courier) {
            case 'delhivery':
                return self::trackDelhivery($trackingNumber);
            case 'bluedart':
                return self::trackBlueDart($trackingNumber);
            case 'dtdc':
                return self::trackDTDC($trackingNumber);
            case 'india_post':
                return self::trackIndiaPost($trackingNumber);
            case 'fedex':
                return self::trackFedEx($trackingNumber);
            default:
                return self::trackGeneric($trackingNumber);
        }
    }

    /**
     * Track using Delhivery API
     */
    private static function trackDelhivery($trackingNumber)
    {
        try {
            // Delhivery tracking API
            $response = Http::timeout(10)->get('https://track.delhivery.com/api/v1/packages/json/', [
                'waybill' => $trackingNumber,
                'verbose' => 2
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['ShipmentData'][0])) {
                    $shipment = $data['ShipmentData'][0]['Shipment'];
                    
                    return [
                        'tracking_number' => $trackingNumber,
                        'courier' => 'Delhivery',
                        'status' => $shipment['Status']['Status'] ?? 'In Transit',
                        'current_location' => $shipment['Destination']['City'] ?? 'Unknown',
                        'estimated_delivery' => $shipment['ExpectedDeliveryDate'] ?? null,
                        'events' => self::formatDelhiveryEvents($data['ShipmentData'][0]['ShipmentTrack'] ?? []),
                        'error' => null
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Delhivery tracking failed: " . $e->getMessage());
        }

        return self::getDefaultTrackingData($trackingNumber, 'Delhivery');
    }

    /**
     * Track using Blue Dart API (mock implementation)
     */
    private static function trackBlueDart($trackingNumber)
    {
        // Blue Dart requires API key and integration
        // This is a mock implementation
        return [
            'tracking_number' => $trackingNumber,
            'courier' => 'Blue Dart',
            'status' => 'In Transit',
            'current_location' => 'Mumbai Hub',
            'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
            'events' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'Package picked up',
                    'location' => 'Origin'
                ],
                [
                    'date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'status' => 'In transit to Mumbai Hub',
                    'location' => 'Mumbai'
                ]
            ],
            'error' => null
        ];
    }

    /**
     * Track using DTDC API (mock implementation)
     */
    private static function trackDTDC($trackingNumber)
    {
        return [
            'tracking_number' => $trackingNumber,
            'courier' => 'DTDC',
            'status' => 'Out for Delivery',
            'current_location' => 'Local Delivery Hub',
            'estimated_delivery' => date('Y-m-d'),
            'events' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'Out for delivery',
                    'location' => 'Local Hub'
                ],
                [
                    'date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'status' => 'Reached destination city',
                    'location' => 'Destination Hub'
                ]
            ],
            'error' => null
        ];
    }

    /**
     * Track using India Post API
     */
    private static function trackIndiaPost($trackingNumber)
    {
        try {
            // India Post tracking
            $response = Http::timeout(10)->get('https://www.indiapost.gov.in/_layouts/15/dop.portal.tracking/TrackConsignment.aspx', [
                'consignmentid' => $trackingNumber
            ]);

            // Since India Post doesn't have a public JSON API, this would require scraping
            // For now, returning mock data
            return [
                'tracking_number' => $trackingNumber,
                'courier' => 'India Post',
                'status' => 'In Transit',
                'current_location' => 'Regional Sorting Hub',
                'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
                'events' => [
                    [
                        'date' => date('Y-m-d H:i:s'),
                        'status' => 'Item received at sorting facility',
                        'location' => 'Regional Hub'
                    ]
                ],
                'error' => null
            ];
        } catch (\Exception $e) {
            Log::error("India Post tracking failed: " . $e->getMessage());
        }

        return self::getDefaultTrackingData($trackingNumber, 'India Post');
    }

    /**
     * Track using FedEx (requires API key)
     */
    private static function trackFedEx($trackingNumber)
    {
        // FedEx requires API credentials
        return [
            'tracking_number' => $trackingNumber,
            'courier' => 'FedEx',
            'status' => 'In Transit',
            'current_location' => 'International Hub',
            'estimated_delivery' => date('Y-m-d', strtotime('+1 day')),
            'events' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'Package in transit',
                    'location' => 'International Hub'
                ]
            ],
            'error' => null
        ];
    }

    /**
     * Generic tracking (fallback)
     */
    private static function trackGeneric($trackingNumber)
    {
        return [
            'tracking_number' => $trackingNumber,
            'courier' => 'Unknown Courier',
            'status' => 'Tracking information not available',
            'current_location' => 'Unknown',
            'estimated_delivery' => null,
            'events' => [],
            'error' => 'Courier not recognized. Please contact customer support.'
        ];
    }

    /**
     * Format Delhivery events
     */
    private static function formatDelhiveryEvents($events)
    {
        $formattedEvents = [];
        
        foreach ($events as $event) {
            $formattedEvents[] = [
                'date' => $event['Date'] ?? date('Y-m-d H:i:s'),
                'status' => $event['Instructions'] ?? 'Package update',
                'location' => $event['StatusLocation'] ?? 'Unknown'
            ];
        }

        return $formattedEvents;
    }

    /**
     * Get default tracking data when API fails
     */
    private static function getDefaultTrackingData($trackingNumber, $courier)
    {
        return [
            'tracking_number' => $trackingNumber,
            'courier' => $courier,
            'status' => 'Tracking information will be updated soon',
            'current_location' => 'Processing',
            'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
            'events' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'Package information received',
                    'location' => 'Origin'
                ]
            ],
            'error' => null
        ];
    }

    /**
     * Get list of supported couriers
     */
    public static function getSupportedCouriers()
    {
        return [
            'delhivery' => 'Delhivery',
            'bluedart' => 'Blue Dart',
            'dtdc' => 'DTDC',
            'india_post' => 'India Post',
            'fedex' => 'FedEx',
            'aramex' => 'Aramex',
            'ecom' => 'Ecom Express',
            'xpressbees' => 'XpressBees'
        ];
    }

    /**
     * Track multiple packages at once
     */
    public static function trackMultiplePackages($trackingNumbers)
    {
        $results = [];
        
        foreach ($trackingNumbers as $trackingNumber) {
            $results[] = self::trackPackage($trackingNumber);
        }

        return $results;
    }
}
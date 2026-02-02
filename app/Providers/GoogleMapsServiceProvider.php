<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleMapsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('GoogleMapsService', function ($app) {
            return new GoogleMapsService(
                config('services.google.maps_api_key'),
                config('services.google.maps', [])
            );
        });

        $this->app->alias('GoogleMapsService', 'google.maps');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

class GoogleMapsService
{
    protected $apiKey;
    protected $config;

    public function __construct($apiKey, $config)
    {
        $this->apiKey = $apiKey;
        $this->config = $config;
    }

    /**
     * Get cached geocoding results
     */
    public function geocode($address)
    {
        $cacheKey = 'geocode_' . md5($address);
        
        if ($this->config['cache_enabled'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $this->apiKey,
        ]);

        $result = $response->json();

        if ($this->config['cache_enabled'] && $result['status'] === 'OK') {
            Cache::put($cacheKey, $result, $this->config['cache_duration'] * 60);
        }

        return $result;
    }

    /**
     * Reverse geocode coordinates
     */
    public function reverseGeocode($lat, $lng)
    {
        $cacheKey = 'reverse_geocode_' . md5("$lat,$lng");
        
        if ($this->config['cache_enabled'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "$lat,$lng",
            'key' => $this->apiKey,
        ]);

        $result = $response->json();

        if ($this->config['cache_enabled'] && $result['status'] === 'OK') {
            Cache::put($cacheKey, $result, $this->config['cache_duration'] * 60);
        }

        return $result;
    }

    /**
     * Get distance matrix
     */
    public function getDistance($origin, $destination)
    {
        $cacheKey = 'distance_' . md5("$origin|$destination");
        
        if ($this->config['cache_enabled'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $origin,
            'destinations' => $destination,
            'key' => $this->apiKey,
        ]);

        $result = $response->json();

        if ($this->config['cache_enabled'] && $result['status'] === 'OK') {
            Cache::put($cacheKey, $result, $this->config['cache_duration'] * 60);
        }

        return $result;
    }

    /**
     * Get directions
     */
    public function getDirections($origin, $destination, $waypoints = [])
    {
        $cacheKey = 'directions_' . md5("$origin|$destination|" . implode('|', $waypoints));
        
        if ($this->config['cache_enabled'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $params = [
            'origin' => $origin,
            'destination' => $destination,
            'key' => $this->apiKey,
        ];

        if (!empty($waypoints)) {
            $params['waypoints'] = implode('|', $waypoints);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', $params);

        $result = $response->json();

        if ($this->config['cache_enabled'] && $result['status'] === 'OK') {
            Cache::put($cacheKey, $result, $this->config['cache_duration'] * 60);
        }

        return $result;
    }

    /**
     * Get the script URL for the Maps JavaScript API
     */
    public function getScriptUrl($libraries = [])
    {
        $defaultLibraries = ['places'];
        $libraries = array_unique(array_merge($defaultLibraries, $libraries));
        
        return sprintf(
            'https://maps.googleapis.com/maps/api/js?key=%s&libraries=%s',
            $this->apiKey,
            implode(',', $libraries)
        );
    }

    /**
     * Validate if the request is from an allowed domain
     */
    public function validateDomain($referer)
    {
        if (empty($this->config['allowed_domains'])) {
            return true;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        foreach ($this->config['allowed_domains'] as $domain) {
            if ($domain === $host) {
                return true;
            }
            if (strpos($domain, '*') === 0 && substr($host, -strlen($domain) + 1) === substr($domain, 1)) {
                return true;
            }
        }

        return false;
    }
}
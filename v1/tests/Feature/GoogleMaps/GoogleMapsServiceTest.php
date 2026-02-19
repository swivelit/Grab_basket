<?php

namespace Tests\Feature\GoogleMaps;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class GoogleMapsServiceTest extends TestCase
{
    /** @test */
    public function it_validates_allowed_domains()
    {
        $mapsService = app('google.maps');

        // Test allowed domains
        $this->assertTrue($mapsService->validateDomain('https://grabbaskets.com'));
        $this->assertTrue($mapsService->validateDomain('https://www.grabbaskets.com'));
        $this->assertTrue($mapsService->validateDomain('http://localhost'));
        
        // Test disallowed domains
        $this->assertFalse($mapsService->validateDomain('https://malicious-site.com'));
        $this->assertFalse($mapsService->validateDomain('https://fake-grabbaskets.com'));
    }

    /** @test */
    public function it_geocodes_address_and_caches_result()
    {
        $mapsService = app('google.maps');
        $address = "Thanjavur, Tamil Nadu, India";

        // First call should hit the API
        $result1 = $mapsService->geocode($address);
        
        $this->assertEquals('OK', $result1['status']);
        $this->assertArrayHasKey('results', $result1);
        
        // Second call should hit the cache
        $result2 = $mapsService->geocode($address);
        
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has('geocode_' . md5($address)));
    }

    /** @test */
    public function it_gets_distance_between_locations()
    {
        $mapsService = app('google.maps');
        
        $result = $mapsService->getDistance(
            "Thanjavur, Tamil Nadu",
            "Chennai, Tamil Nadu"
        );

        $this->assertEquals('OK', $result['status']);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('elements', $result['rows'][0]);
        $this->assertArrayHasKey('distance', $result['rows'][0]['elements'][0]);
    }

    /** @test */
    public function it_gets_directions_with_waypoints()
    {
        $mapsService = app('google.maps');
        
        $result = $mapsService->getDirections(
            "Thanjavur, Tamil Nadu",
            "Chennai, Tamil Nadu",
            ["Trichy, Tamil Nadu"]
        );

        $this->assertEquals('OK', $result['status']);
        $this->assertArrayHasKey('routes', $result);
        $this->assertArrayHasKey('legs', $result['routes'][0]);
    }
}
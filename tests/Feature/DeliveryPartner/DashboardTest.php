<?php

namespace Tests\Feature\DeliveryPartner;

use Tests\TestCase;
use App\Models\DeliveryPartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $deliveryPartner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->deliveryPartner = DeliveryPartner::factory()->create([
            'is_online' => true
        ]);
    }

    /** @test */
    public function dashboard_loads_with_minimal_initial_data()
    {
        $this->actingAs($this->deliveryPartner, 'delivery_partner');

        $response = $this->get(route('delivery-partner.dashboard'));

        $response->assertStatus(200)
            ->assertViewIs('delivery-partner.dashboard.index')
            ->assertViewHas('partner')
            ->assertViewHas('initial_stats')
            ->assertDontSee('error');

        $stats = $response->viewData('initial_stats');
        $this->assertArrayHasKey('name', $stats);
        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('is_online', $stats);
        $this->assertArrayHasKey('rating', $stats);
    }

    /** @test */
    public function dashboard_api_returns_stats()
    {
        $this->actingAs($this->deliveryPartner, 'delivery_partner');

        $response = $this->getJson('/api/delivery-partner/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_orders',
                    'completed_orders',
                    'completion_rate',
                    'rating',
                    'total_earnings',
                    'today_earnings',
                    'pending_orders',
                    'active_hours'
                ]
            ]);
    }

    /** @test */
    public function dashboard_api_returns_orders()
    {
        $this->actingAs($this->deliveryPartner, 'delivery_partner');

        $response = $this->getJson('/api/delivery-partner/dashboard/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'recent' => [
                        '*' => [
                            'order_id',
                            'status',
                            'created_at',
                            'delivery_fee'
                        ]
                    ],
                    'available' => [
                        '*' => [
                            'id',
                            'order_number',
                            'total_amount',
                            'delivery_address'
                        ]
                    ]
                ]
            ]);
    }
}
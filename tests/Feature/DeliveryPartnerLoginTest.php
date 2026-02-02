<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Hash;

class DeliveryPartnerLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function delivery_partner_can_login_and_access_dashboard()
    {
        // Create a delivery partner
        $partner = DeliveryPartner::factory()->create([
            'email' => 'test@delivery.com',
            'password' => Hash::make('password123'),
            'status' => 'approved',
        ]);

        // Visit login page (GET)
        $this->get('/delivery-partner/login')->assertStatus(200);

        // Post login credentials
        $response = $this->post('/delivery-partner/login', [
            'login' => 'test@delivery.com',
            'password' => 'password123',
        ]);

        // After successful login, expect redirect to dashboard
        $response->assertRedirect('/delivery-partner/dashboard');

        // Follow redirect and assert dashboard accessible
        $this->followingRedirects()
            ->get('/delivery-partner/dashboard')
            ->assertStatus(200)
            ->assertSeeText('Welcome back');
    }
}

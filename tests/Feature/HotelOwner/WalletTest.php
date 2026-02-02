<?php

namespace Tests\Feature\HotelOwner;

use Tests\TestCase;
use App\Models\HotelOwner;
use App\Models\HotelOwnerWallet;
use App\Models\HotelOwnerWithdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Assert;

class WalletTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $hotelOwner;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test hotel owner
        $this->hotelOwner = HotelOwner::factory()->create();
        
        // Create wallet with initial balance
        $this->wallet = HotelOwnerWallet::create([
            'hotel_owner_id' => $this->hotelOwner->id,
            'balance' => 1000,
            'currency' => 'INR'
        ]);
    }

    /** @test */
    public function hotel_owner_can_view_wallet()
    {
        $this->actingAs($this->hotelOwner, 'hotel_owner');

        $response = $this->get(route('hotel-owner.wallet.index'));

        $response->assertStatus(200)
            ->assertViewIs('hotel-owner.wallet.index')
            ->assertViewHas('wallet')
            ->assertSee('₹1,000.00');
    }

    /** @test */
    public function hotel_owner_can_request_withdrawal()
    {
        $this->actingAs($this->hotelOwner, 'hotel_owner');

        $response = $this->post(route('hotel-owner.wallet.withdraw'), [
            'amount' => 500,
            'notes' => 'Test withdrawal'
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('hotel_owner_withdrawals', [
            'hotel_owner_wallet_id' => $this->wallet->id,
            'amount' => 500,
            'status' => 'pending'
        ]);

        $this->wallet->refresh();
        Assert::assertSame(500.0, $this->wallet->available_balance);
    }

    /** @test */
    public function withdrawal_request_fails_with_insufficient_balance()
    {
        $this->actingAs($this->hotelOwner, 'hotel_owner');

        $response = $this->post(route('hotel-owner.wallet.withdraw'), [
            'amount' => 1500,
            'notes' => 'Test withdrawal'
        ]);

        $response->assertSessionHas('error');
        
        $this->assertDatabaseMissing('hotel_owner_withdrawals', [
            'hotel_owner_wallet_id' => $this->wallet->id,
            'amount' => 1500
        ]);

        $this->wallet->refresh();
        Assert::assertSame(1000.0, $this->wallet->balance);
    }
}
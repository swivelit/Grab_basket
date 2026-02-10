<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '9876543210',
                'billing_address' => 'Test Address',
                'state' => 'Test State',
                'city' => 'Test City',
                'pincode' => '123456',
                'role' => 'buyer',
                'sex' => 'male',
            ]);
        }
    }
}

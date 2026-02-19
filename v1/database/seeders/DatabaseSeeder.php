<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SubcategorySeeder::class,
            ProductSeeder::class,
            CategoryEmojiSeeder::class,
        ]);

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

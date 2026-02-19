<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Services\InfobipSmsService;

class TestSmsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:test-sellers {--phone=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SMS notifications with current sellers and add phone numbers if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking sellers in database...');
        
        // Check for sellers (users who have products)
        $sellers = User::whereHas('products')->get();
        $this->info("Found {$sellers->count()} sellers in database");
        
        if ($sellers->isEmpty()) {
            $this->warn('No sellers found. Creating test sellers...');
            $this->createTestSellers();
            $sellers = User::whereHas('products')->get();
        }
        
        // Show current sellers
        $this->table(
            ['ID', 'Name', 'Email', 'Phone', 'Products Count'],
            $sellers->map(function($seller) {
                return [
                    $seller->id,
                    $seller->name,
                    $seller->email,
                    $seller->phone ?? 'No phone',
                    $seller->products()->count()
                ];
            })
        );
        
        // Add phone numbers to sellers without them
        $sellersWithoutPhone = $sellers->whereNull('phone');
        if ($sellersWithoutPhone->count() > 0) {
            $this->warn("Found {$sellersWithoutPhone->count()} sellers without phone numbers");
            
            if ($this->confirm('Add test phone numbers to sellers?')) {
                $this->addPhoneNumbersToSellers($sellersWithoutPhone);
            }
        }
        
        // Test SMS with sellers who have phone numbers
        $sellersWithPhone = User::whereHas('products')->whereNotNull('phone')->get();
        
        if ($sellersWithPhone->isEmpty()) {
            $this->error('No sellers with phone numbers available for testing');
            return;
        }
        
        $this->info("Testing SMS with {$sellersWithPhone->count()} sellers...");
        
        if ($this->confirm('Send test SMS to all sellers with phone numbers?')) {
            $this->testSmsWithSellers($sellersWithPhone);
        }
        
        $this->info('✅ SMS testing completed!');
    }
    
    private function createTestSellers()
    {
        $this->info('Creating test sellers with products...');
        
        $testSellers = [
            [
                'name' => 'Test Seller 1',
                'email' => 'seller1@test.com',
                'phone' => '919876543210',
                'password' => bcrypt('password')
            ],
            [
                'name' => 'Test Seller 2', 
                'email' => 'seller2@test.com',
                'phone' => '919876543211',
                'password' => bcrypt('password')
            ]
        ];
        
        foreach ($testSellers as $sellerData) {
            $seller = User::create($sellerData);
            
            // Create a test product for this seller
            Product::create([
                'name' => 'Test Product - ' . $seller->name,
                'description' => 'Test product for SMS testing',
                'price' => rand(100, 1000),
                'stock' => rand(10, 100),
                'seller_id' => $seller->id,
                'category_id' => 1, // Assuming category 1 exists
                'subcategory_id' => 1, // Assuming subcategory 1 exists
            ]);
            
            $this->info("✅ Created seller: {$seller->name} with phone: {$seller->phone}");
        }
    }
    
    private function addPhoneNumbersToSellers($sellers)
    {
        $testPhones = [
            '919876543212',
            '919876543213', 
            '919876543214',
            '919876543215',
            '919876543216'
        ];
        
        $phoneIndex = 0;
        foreach ($sellers as $seller) {
            if ($phoneIndex < count($testPhones)) {
                $seller->phone = $testPhones[$phoneIndex];
                $seller->save();
                $this->info("✅ Added phone {$testPhones[$phoneIndex]} to {$seller->name}");
                $phoneIndex++;
            }
        }
    }
    
    private function testSmsWithSellers($sellers)
    {
        $smsService = new InfobipSmsService();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($sellers as $seller) {
            $testMessage = "🧪 SMS Test Alert! Hi {$seller->name}, this is a test notification from GrabBasket. Your SMS integration is working! You would receive order alerts like this. Test time: " . now()->format('H:i:s') . " - GrabBasket";
            
            $this->info("Sending SMS to {$seller->name} ({$seller->phone})...");
            
            $result = $smsService->sendSms($seller->phone, $testMessage);
            
            if ($result['success']) {
                $this->info("✅ SMS sent successfully to {$seller->name}");
                $successCount++;
            } else {
                $this->error("❌ Failed to send SMS to {$seller->name}: " . $result['error']);
                $errorCount++;
            }
            
            // Add small delay between messages
            sleep(1);
        }
        
        $this->info("\n📊 SMS Test Results:");
        $this->info("✅ Successful: {$successCount}");
        $this->info("❌ Failed: {$errorCount}");
        
        if ($successCount > 0) {
            $this->info("🎉 SMS integration is working! Check the phones for test messages.");
        }
    }
}

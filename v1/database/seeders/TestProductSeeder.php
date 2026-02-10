<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Seller;
use App\Models\Subcategory;

class TestProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first seller (or create one if none exists)
        $seller = Seller::first();
        if (!$seller) {
            $seller = Seller::create([
                'name' => 'Test Seller',
                'email' => 'testseller@example.com',
                'phone' => '9876543210',
                'billing_address' => 'Test Billing Address',
                'state' => 'Test State',
                'city' => 'Test City',
                'pincode' => '123456',
                'password' => bcrypt('password123'),
                'store_name' => 'Test Store',
                'store_address' => 'Test Store Address',
                'store_contact' => '9876543210',
                'sex' => 'male'
            ]);
        }

        // Get categories (or create basic ones if none exist)
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $categories = collect([
                Category::create(['name' => 'ELECTRONICS', 'unique_id' => 'ELE']),
                Category::create(['name' => 'FASHION', 'unique_id' => 'FAS']),
                Category::create(['name' => 'HOME', 'unique_id' => 'HOM']),
                Category::create(['name' => 'BOOKS', 'unique_id' => 'BOO']),
                Category::create(['name' => 'SPORTS', 'unique_id' => 'SPO'])
            ]);
        }

        // Create subcategories for each category
        $subcategories = [];
        foreach ($categories as $category) {
            $subcategory = Subcategory::firstOrCreate(
                ['category_id' => $category->id],
                [
                    'name' => $category->name . ' Items',
                    'unique_id' => substr($category->unique_id, 0, 2) . '1',
                    'description' => 'Items in ' . $category->name . ' category'
                ]
            );
            $subcategories[$category->name] = $subcategory;
        }

        // Test products for payment flow
        $testProducts = [
            [
                'name' => 'iPhone 15 Pro',
                'unique_id' => 'IP1',
                'description' => 'Latest iPhone with advanced camera system and A17 Pro chip',
                'price' => 129900.00,
                'stock' => 50,
                'category_id' => $categories->where('name', 'ELECTRONICS')->first()->id,
                'subcategory_id' => $subcategories['ELECTRONICS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 5
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'unique_id' => 'SG1',
                'description' => 'Premium Android smartphone with S Pen and amazing display',
                'price' => 124999.00,
                'stock' => 30,
                'category_id' => $categories->where('name', 'ELECTRONICS')->first()->id,
                'subcategory_id' => $subcategories['ELECTRONICS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 8
            ],
            [
                'name' => 'MacBook Air M3',
                'unique_id' => 'MB1',
                'description' => 'Ultra-thin laptop with Apple M3 chip and all-day battery life',
                'price' => 114900.00,
                'stock' => 25,
                'category_id' => $categories->where('name', 'ELECTRONICS')->first()->id,
                'subcategory_id' => $subcategories['ELECTRONICS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 10
            ],
            [
                'name' => 'Nike Air Jordan 1',
                'unique_id' => 'NJ1',
                'description' => 'Classic basketball shoes with premium leather construction',
                'price' => 12995.00,
                'stock' => 100,
                'category_id' => $categories->where('name', 'SPORTS')->first()->id,
                'subcategory_id' => $subcategories['SPORTS']->id,
                'seller_id' => 1,
                'gift_option' => 'no',
                'delivery_charge' => 99,
                'discount' => 15
            ],
            [
                'name' => 'Levi\'s 501 Original Jeans',
                'unique_id' => 'LJ1',
                'description' => 'Classic straight-leg jeans in premium denim',
                'price' => 3999.00,
                'stock' => 150,
                'category_id' => $categories->where('name', 'FASHION')->first()->id,
                'subcategory_id' => $subcategories['FASHION']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 49,
                'discount' => 20
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'unique_id' => 'SH1',
                'description' => 'Industry-leading noise canceling wireless headphones',
                'price' => 29990.00,
                'stock' => 75,
                'category_id' => $categories->where('name', 'ELECTRONICS')->first()->id,
                'subcategory_id' => $subcategories['ELECTRONICS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 12
            ],
            [
                'name' => 'The Psychology of Money',
                'unique_id' => 'PM1',
                'description' => 'Timeless lessons on wealth, greed, and happiness by Morgan Housel',
                'price' => 399.00,
                'stock' => 200,
                'category_id' => $categories->where('name', 'BOOKS')->first()->id,
                'subcategory_id' => $subcategories['BOOKS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 0
            ],
            [
                'name' => 'Dyson V15 Detect Vacuum',
                'unique_id' => 'DV1',
                'description' => 'Powerful cordless vacuum with laser dust detection',
                'price' => 59900.00,
                'stock' => 20,
                'category_id' => $categories->where('name', 'HOME')->first()->id,
                'subcategory_id' => $subcategories['HOME']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 7
            ],
            [
                'name' => 'Boat Airdopes 141',
                'unique_id' => 'BA1',
                'description' => 'True wireless earbuds with deep bass and long battery life',
                'price' => 1799.00,
                'stock' => 300,
                'category_id' => $categories->where('name', 'ELECTRONICS')->first()->id,
                'subcategory_id' => $subcategories['ELECTRONICS']->id,
                'seller_id' => 1,
                'gift_option' => 'no',
                'delivery_charge' => 49,
                'discount' => 25
            ],
            [
                'name' => 'Adidas Ultraboost 22',
                'unique_id' => 'AU1',
                'description' => 'Premium running shoes with responsive cushioning',
                'price' => 16999.00,
                'stock' => 80,
                'category_id' => $categories->where('name', 'SPORTS')->first()->id,
                'subcategory_id' => $subcategories['SPORTS']->id,
                'seller_id' => 1,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 18
            ]
        ];

        foreach ($testProducts as $productData) {
            Product::create($productData);
        }

        $this->command->info('Test products created successfully for payment flow testing!');
    }
}

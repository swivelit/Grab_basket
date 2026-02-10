<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subcategoriesData = [
            'ELECTRONICS' => [
                ['name' => 'Mobile Phones', 'unique_id' => 'MOB', 'description' => 'Smartphones and feature phones'],
                ['name' => 'Laptops & Computers', 'unique_id' => 'LAP', 'description' => 'Laptops, desktops, and accessories'],
                ['name' => 'Audio & Headphones', 'unique_id' => 'AUD', 'description' => 'Headphones, speakers, and audio devices'],
                ['name' => 'Cameras', 'unique_id' => 'CAM', 'description' => 'Digital cameras and photography equipment'],
                ['name' => 'Gaming', 'unique_id' => 'GAM', 'description' => 'Gaming consoles and accessories'],
                ['name' => 'Wearables', 'unique_id' => 'WEA', 'description' => 'Smartwatches and fitness trackers'],
            ],
            'MEN\'S FASHION' => [
                ['name' => 'Men\'s Shirts', 'unique_id' => 'MSH', 'description' => 'Casual and formal shirts for men'],
                ['name' => 'Men\'s Pants', 'unique_id' => 'MPA', 'description' => 'Trousers, jeans, and pants for men'],
                ['name' => 'Men\'s Shoes', 'unique_id' => 'MSO', 'description' => 'Formal and casual shoes for men'],
                ['name' => 'Men\'s Accessories', 'unique_id' => 'MAC', 'description' => 'Wallets, belts, and accessories for men'],
                ['name' => 'Men\'s Watches', 'unique_id' => 'MWA', 'description' => 'Watches and timepieces for men'],
            ],
            'WOMEN\'S FASHION' => [
                ['name' => 'Women\'s Dresses', 'unique_id' => 'WDR', 'description' => 'Casual and formal dresses for women'],
                ['name' => 'Women\'s Tops', 'unique_id' => 'WTO', 'description' => 'Blouses, shirts, and tops for women'],
                ['name' => 'Women\'s Bottoms', 'unique_id' => 'WBO', 'description' => 'Skirts, pants, and bottoms for women'],
                ['name' => 'Women\'s Shoes', 'unique_id' => 'WSO', 'description' => 'Heels, flats, and shoes for women'],
                ['name' => 'Handbags', 'unique_id' => 'HAN', 'description' => 'Purses, bags, and handbags'],
            ],
            'HOME & KITCHEN' => [
                ['name' => 'Kitchen Appliances', 'unique_id' => 'KIT', 'description' => 'Kitchen appliances and tools'],
                ['name' => 'Home Decor', 'unique_id' => 'DEC', 'description' => 'Decorative items for home'],
                ['name' => 'Cleaning Supplies', 'unique_id' => 'CLE', 'description' => 'Cleaning tools and supplies'],
                ['name' => 'Storage & Organization', 'unique_id' => 'STO', 'description' => 'Storage solutions'],
            ],
            'BEAUTY & PERSONAL CARE' => [
                ['name' => 'Skincare', 'unique_id' => 'SKI', 'description' => 'Skincare products and treatments'],
                ['name' => 'Makeup', 'unique_id' => 'MAK', 'description' => 'Cosmetics and makeup products'],
                ['name' => 'Hair Care', 'unique_id' => 'HAI', 'description' => 'Hair care and styling products'],
                ['name' => 'Fragrances', 'unique_id' => 'FRA', 'description' => 'Perfumes and fragrances'],
            ],
            'SPORTS & FITNESS' => [
                ['name' => 'Exercise Equipment', 'unique_id' => 'EXE', 'description' => 'Fitness and exercise equipment'],
                ['name' => 'Sports Gear', 'unique_id' => 'SPG', 'description' => 'Equipment for various sports'],
                ['name' => 'Athletic Wear', 'unique_id' => 'ATH', 'description' => 'Sports and athletic clothing'],
                ['name' => 'Outdoor Recreation', 'unique_id' => 'OUT', 'description' => 'Outdoor sports equipment'],
            ],
            'BOOKS & EDUCATION' => [
                ['name' => 'Fiction', 'unique_id' => 'FIC', 'description' => 'Fiction books and novels'],
                ['name' => 'Non-Fiction', 'unique_id' => 'NOF', 'description' => 'Non-fiction and educational books'],
                ['name' => 'Textbooks', 'unique_id' => 'TEX', 'description' => 'Academic and educational textbooks'],
                ['name' => 'Children\'s Books', 'unique_id' => 'CHI', 'description' => 'Books for children'],
            ],
            'KIDS & TOYS' => [
                ['name' => 'Action Figures', 'unique_id' => 'ACT', 'description' => 'Action figures and collectibles'],
                ['name' => 'Board Games', 'unique_id' => 'BOA', 'description' => 'Board games and puzzles'],
                ['name' => 'Educational Toys', 'unique_id' => 'EDU', 'description' => 'Educational and learning toys'],
                ['name' => 'Outdoor Toys', 'unique_id' => 'OTO', 'description' => 'Outdoor play equipment'],
                ['name' => 'Kids Clothing', 'unique_id' => 'KCL', 'description' => 'Clothing for children'],
            ],
            'AUTOMOTIVE' => [
                ['name' => 'Car Accessories', 'unique_id' => 'CAR', 'description' => 'Car accessories and parts'],
                ['name' => 'Motorcycle Gear', 'unique_id' => 'MOT', 'description' => 'Motorcycle accessories'],
                ['name' => 'Tools & Equipment', 'unique_id' => 'TOO', 'description' => 'Automotive tools'],
            ],
            'HEALTH & WELLNESS' => [
                ['name' => 'Vitamins & Supplements', 'unique_id' => 'VIT', 'description' => 'Health supplements'],
                ['name' => 'Medical Supplies', 'unique_id' => 'MED', 'description' => 'Medical equipment and supplies'],
                ['name' => 'Fitness Nutrition', 'unique_id' => 'FIT', 'description' => 'Protein and fitness nutrition'],
            ],
            'JEWELRY & ACCESSORIES' => [
                ['name' => 'Necklaces', 'unique_id' => 'NEC', 'description' => 'Necklaces and pendants'],
                ['name' => 'Rings', 'unique_id' => 'RIN', 'description' => 'Rings and bands'],
                ['name' => 'Earrings', 'unique_id' => 'EAR', 'description' => 'Earrings and ear accessories'],
                ['name' => 'Bracelets', 'unique_id' => 'BRA', 'description' => 'Bracelets and bangles'],
            ],
            'GROCERY & FOOD' => [
                ['name' => 'Fresh Produce', 'unique_id' => 'PRO', 'description' => 'Fresh fruits and vegetables'],
                ['name' => 'Packaged Foods', 'unique_id' => 'PAC', 'description' => 'Packaged and processed foods'],
                ['name' => 'Beverages', 'unique_id' => 'BEV', 'description' => 'Drinks and beverages'],
                ['name' => 'Snacks', 'unique_id' => 'SNA', 'description' => 'Snacks and confectionery'],
            ],
            'FURNITURE' => [
                ['name' => 'Living Room', 'unique_id' => 'LIV', 'description' => 'Living room furniture'],
                ['name' => 'Bedroom', 'unique_id' => 'BED', 'description' => 'Bedroom furniture'],
                ['name' => 'Office Furniture', 'unique_id' => 'OFF', 'description' => 'Office and workspace furniture'],
                ['name' => 'Dining Room', 'unique_id' => 'DIN', 'description' => 'Dining room furniture'],
            ],
            'GARDEN & OUTDOOR' => [
                ['name' => 'Garden Tools', 'unique_id' => 'GTO', 'description' => 'Gardening tools and equipment'],
                ['name' => 'Plants & Seeds', 'unique_id' => 'PLA', 'description' => 'Plants, seeds, and gardening supplies'],
                ['name' => 'Outdoor Furniture', 'unique_id' => 'OFU', 'description' => 'Patio and outdoor furniture'],
            ],
            'PET SUPPLIES' => [
                ['name' => 'Dog Supplies', 'unique_id' => 'DOG', 'description' => 'Supplies for dogs'],
                ['name' => 'Cat Supplies', 'unique_id' => 'CAT', 'description' => 'Supplies for cats'],
                ['name' => 'Pet Food', 'unique_id' => 'PFO', 'description' => 'Pet food and treats'],
            ],
            'BABY PRODUCTS' => [
                ['name' => 'Baby Care', 'unique_id' => 'BBC', 'description' => 'Baby care products'],
                ['name' => 'Baby Clothing', 'unique_id' => 'BCL', 'description' => 'Clothing for babies'],
                ['name' => 'Baby Gear', 'unique_id' => 'BGE', 'description' => 'Baby strollers, car seats, etc.'],
            ],
        ];

        foreach ($subcategoriesData as $categoryName => $subcategories) {
            $category = Category::where('name', $categoryName)->first();
            
            if ($category) {
                foreach ($subcategories as $subcategory) {
                    Subcategory::firstOrCreate(
                        [
                            'unique_id' => $subcategory['unique_id'],
                            'category_id' => $category->id
                        ],
                        [
                            'name' => $subcategory['name'],
                            'unique_id' => $subcategory['unique_id'],
                            'category_id' => $category->id,
                            'description' => $subcategory['description'],
                        ]
                    );
                }
            }
        }

        $this->command->info('Subcategories created successfully!');
    }
}

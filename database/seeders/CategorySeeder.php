<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'ELECTRONICS', 'unique_id' => 'ELE', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'MEN\'S FASHION', 'unique_id' => 'MFA', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'WOMEN\'S FASHION', 'unique_id' => 'WFA', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'HOME & KITCHEN', 'unique_id' => 'HOM', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1507089947368-19c1da9775ae?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'BEAUTY & PERSONAL CARE', 'unique_id' => 'BEA', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'SPORTS & FITNESS', 'unique_id' => 'SPO', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1519864600265-abb23847ef2c?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'BOOKS & EDUCATION', 'unique_id' => 'BOO', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'KIDS & TOYS', 'unique_id' => 'KTO', 'gender' => 'kids', 'image' => 'https://images.unsplash.com/photo-1503457574465-0ec62fae31a0?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'AUTOMOTIVE', 'unique_id' => 'AUT', 'gender' => 'men', 'image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'HEALTH & WELLNESS', 'unique_id' => 'HEA', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'JEWELRY & ACCESSORIES', 'unique_id' => 'JEW', 'gender' => 'women', 'image' => 'https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'GROCERY & FOOD', 'unique_id' => 'GRO', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1502741338009-cac2772e18bc?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'FURNITURE', 'unique_id' => 'FUR', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'GARDEN & OUTDOOR', 'unique_id' => 'GAR', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'PET SUPPLIES', 'unique_id' => 'PET', 'gender' => 'all', 'image' => 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'BABY PRODUCTS', 'unique_id' => 'BAB', 'gender' => 'kids', 'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=400&q=80'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['unique_id' => $category['unique_id']],
                $category
            );
        }

        $this->command->info('Categories created successfully!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoryEmojiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emojiMappings = [
            'ELECTRONICS' => '🖥️',
            'MEN\'S FASHION' => '👔',
            'WOMEN\'S FASHION' => '👗',
            'HOME & KITCHEN' => '🍽️',
            'BEAUTY & PERSONAL CARE' => '💄',
            'SPORTS & FITNESS' => '🏃‍♂️',
            'BOOKS & EDUCATION' => '📚',
            'KIDS & TOYS' => '🧸',
            'AUTOMOTIVE' => '🚗',
            'HEALTH & WELLNESS' => '🏥',
            'JEWELRY & ACCESSORIES' => '💍',
            'GROCERY & FOOD' => '🥬',
            'FURNITURE' => '🛋️',
            'GARDEN & OUTDOOR' => '🌻',
            'PET SUPPLIES' => '🐕',
            'BABY PRODUCTS' => '👶',
            'CLOTHING' => '👕',
            'BOOKS' => '📖',
        ];

        $categories = Category::all();

        foreach ($categories as $category) {
            $categoryName = strtoupper(trim($category->name));

            // Direct mapping
            if (isset($emojiMappings[$categoryName])) {
                $category->emoji = $emojiMappings[$categoryName];
            } else {
                // Partial matching for similar categories
                $emoji = '🛍️'; // Default

                if (str_contains($categoryName, 'ELECTRONIC'))
                    $emoji = '⚡';
                elseif (str_contains($categoryName, 'FASHION') || str_contains($categoryName, 'CLOTH'))
                    $emoji = '👗';
                elseif (str_contains($categoryName, 'BEAUTY') || str_contains($categoryName, 'CARE'))
                    $emoji = '💅';
                elseif (str_contains($categoryName, 'SPORT') || str_contains($categoryName, 'FITNESS'))
                    $emoji = '⚽';
                elseif (str_contains($categoryName, 'BOOK') || str_contains($categoryName, 'EDUCATION'))
                    $emoji = '📚';
                elseif (str_contains($categoryName, 'KID') || str_contains($categoryName, 'TOY'))
                    $emoji = '🎮';
                elseif (str_contains($categoryName, 'AUTO') || str_contains($categoryName, 'CAR'))
                    $emoji = '🚘';
                elseif (str_contains($categoryName, 'HEALTH') || str_contains($categoryName, 'WELLNESS'))
                    $emoji = '💊';
                elseif (str_contains($categoryName, 'JEWELRY') || str_contains($categoryName, 'ACCESS'))
                    $emoji = '💎';
                elseif (str_contains($categoryName, 'GROCERY') || str_contains($categoryName, 'FOOD'))
                    $emoji = '🍎';
                elseif (str_contains($categoryName, 'FURNITURE') || str_contains($categoryName, 'HOME'))
                    $emoji = '🏠';
                elseif (str_contains($categoryName, 'GARDEN') || str_contains($categoryName, 'OUTDOOR'))
                    $emoji = '🌸';
                elseif (str_contains($categoryName, 'PET'))
                    $emoji = '🐾';
                elseif (str_contains($categoryName, 'BABY'))
                    $emoji = '🍼';

                $category->emoji = $emoji;
            }

            $category->save();
        }
    }
}

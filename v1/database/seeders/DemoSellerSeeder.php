<?php
// database/seeders/DemoSellerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Seller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;

class DemoSellerSeeder extends Seeder
{
    public function run()
    {
        // 1. Create demo user and seller
        $user = User::firstOrCreate([
            'email' => 'demo-seller@example.com',
        ], [
            'name' => 'Demo Seller',
            'password' => Hash::make('demopassword'),
            'phone' => '9999999999',
            'billing_address' => '123 Demo Billing Street',
            'state' => 'DemoState',
            'city' => 'DemoCity',
        ]);

        $seller = Seller::firstOrCreate([
            'email' => $user->email,
        ], [
            'store_name' => 'Demo Store',
            'gst_number' => 'GSTDEMO123',
            'store_address' => '123 Demo Street',
            'store_contact' => '9999999999',
        ]);

        // 2. Create a category and subcategory
        $category = Category::firstOrCreate([
            'name' => 'Demo Category',
            'unique_id' => 'DEM',
        ]);
        $subcategory = Subcategory::firstOrCreate([
            'name' => 'Demo Subcategory',
            'category_id' => $category->id,
            'unique_id' => 'DSC',
        ]);

        // 3. Upload a demo image to cloud disk
        $disk = env('FILESYSTEM_DISK', 'r2');
        $imagePath = 'products/demo_product_image.jpg';
        if (!Storage::disk($disk)->exists($imagePath)) {
            $demoImage = file_get_contents('https://via.placeholder.com/400x400.png?text=Demo+Product');
            Storage::disk($disk)->put($imagePath, $demoImage);
        }

        // 4. Create a demo product
        Product::firstOrCreate([
            'unique_id' => 'DEMO1',
            'seller_id' => $user->id,
        ], [
            'name' => 'Demo Product',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'description' => 'This is a demo product for website showcase.',
            'price' => 99.99,
            'discount' => 10,
            'delivery_charge' => 0,
            'gift_option' => 'yes',
            'stock' => 100,
            'image' => $imagePath,
        ]);
    }
}

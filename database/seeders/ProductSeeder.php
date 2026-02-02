<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents                'image' => 'https://via.placeholder.com/400x400/1a1a1a/ffffff?text=iPhone+15+Pro+Max',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra 512GB Titanium Black',
                'unique_id' => 'SG1',
                'description' => 'Premium Android smartphone with S Pen, 200MP camera, AI features, and 6.8-inch Dynamic AMOLED display.',
                'price' => 134999.00,
                'stock' => 30,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 8,
                'image' => 'https://via.placeholder.com/400x400/2c3e50/ffffff?text=Samsung+Galaxy+S24+Ultra',te\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create the default seller
        $seller = User::where('email', 'seller@example.com')->first();
        if (!$seller) {
            $seller = User::create([
                'name' => 'Default Seller',
                'email' => 'seller@example.com',
                'phone' => '9876543210',
                'billing_address' => 'Default Address',
                'state' => 'Default State',
                'city' => 'Default City',
                'pincode' => '123456',
                'role' => 'seller',
                'sex' => 'male',
                'password' => bcrypt('password'),
            ]);
        }
        $sellerId = $seller->id;

        // Get or create the demo seller
        $demoSeller = User::where('email', 'demo-seller@example.com')->first();
        if (!$demoSeller) {
            $demoSeller = User::create([
                'name' => 'Demo Seller',
                'email' => 'demo-seller@example.com',
                'phone' => '9000000000',
                'billing_address' => 'Demo Address',
                'state' => 'Demo State',
                'city' => 'Demo City',
                'pincode' => '999999',
                'role' => 'seller',
                'sex' => 'male',
                'password' => bcrypt('password'),
            ]);
        }
        $demoSellerId = $demoSeller->id;

        // Seed products for default seller
        $this->createElectronicsProducts($sellerId);
        $this->createFashionProducts($sellerId);
        $this->createHomeKitchenProducts($sellerId);
        $this->createBeautyProducts($sellerId);
        $this->createSportsProducts($sellerId);
        $this->createBooksProducts($sellerId);
        $this->createOtherProducts($sellerId);

        // Seed demo products for demo seller (with DEMO- prefix for unique_id)
        $this->createElectronicsProducts($demoSellerId, 'DEMO-');
        $this->createFashionProducts($demoSellerId, 'DEMO-');
        $this->createHomeKitchenProducts($demoSellerId, 'DEMO-');
        $this->createBeautyProducts($demoSellerId, 'DEMO-');
        $this->createSportsProducts($demoSellerId, 'DEMO-');
        $this->createBooksProducts($demoSellerId, 'DEMO-');
        $this->createOtherProducts($demoSellerId, 'DEMO-');

        $this->command->info('Products created successfully for default and demo sellers!');
    }

    private function createElectronicsProducts($sellerId, $uniquePrefix = '')
    {
        $electronics = Category::where('name', 'ELECTRONICS')->first();
        $mobileSubcat = Subcategory::where('name', 'Mobile Phones')->first();
        $laptopSubcat = Subcategory::where('name', 'Laptops & Computers')->first();
        $audioSubcat = Subcategory::where('name', 'Audio & Headphones')->first();

    $products = [
            // Mobile Phones (Amazon bestsellers)
            [
                'name' => 'iPhone 15 Pro Max 256GB Natural Titanium',
                'unique_id' => 'IP1',
                'description' => 'Latest iPhone with titanium design, A17 Pro chip, 48MP camera system, and USB-C. 6.7-inch Super Retina XDR display.',
                'price' => 159900.00,
                'stock' => 25,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 5,
                'image' => 'https://via.placeholder.com/400x400/1a1a1a/ffffff?text=iPhone+15+Pro+Max',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra 512GB Titanium Black',
                'unique_id' => 'SG1',
                'description' => 'Premium Android smartphone with S Pen, 200MP camera, AI features, and 6.8-inch Dynamic AMOLED display.',
                'price' => 134999.00,
                'stock' => 30,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 8,
                'image' => 'https://via.placeholder.com/400x400/2c3e50/ffffff?text=Samsung+Galaxy+S24+Ultra',
            ],
            [
                'name' => 'OnePlus 12 16GB RAM 512GB Silky Black',
                'unique_id' => 'OP1',
                'description' => 'Flagship Android phone with Snapdragon 8 Gen 3, 120Hz display, 50MP Hasselblad camera, and 100W fast charging.',
                'price' => 64999.00,
                'stock' => 40,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 12,
                'image' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Xiaomi 14 Ultra 16GB+512GB Black',
                'unique_id' => 'XI1',
                'description' => 'Professional photography smartphone with Leica quad camera system, Snapdragon 8 Gen 3, and 90W charging.',
                'price' => 89999.00,
                'stock' => 20,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 15,
                                'image' => 'https://picsum.photos/400/400?random=1',
            ],
            [
                'name' => 'Xiaomi 14 Ultra 512GB Black',
                'unique_id' => 'XM1',
                'description' => 'Professional photography smartphone with Leica optics, Snapdragon 8 Gen 3, and 6.73-inch LTPO AMOLED display.',
                'price' => 89999.00,
                'stock' => 35,
                'category_id' => $electronics->id,
                'subcategory_id' => $mobileSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 10,
                'image' => 'https://picsum.photos/400/400?random=2',
            ],
            
            // Laptops (Amazon bestsellers)
            [
                'name' => 'MacBook Air M3 15-inch 16GB 512GB Space Gray',
                'unique_id' => 'MB1',
                'description' => 'Ultra-thin laptop with Apple M3 chip, 18-hour battery life, Liquid Retina display, and MagSafe charging.',
                'price' => 184900.00,
                'stock' => 15,
                'category_id' => $electronics->id,
                'subcategory_id' => $laptopSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 8,
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Dell XPS 13 Plus Intel i7 32GB 1TB Platinum',
                'unique_id' => 'DX1',
                'description' => 'Premium ultrabook with 13.4-inch 4K+ touchscreen, Intel 12th gen i7, Thunderbolt 4, and Windows 11 Pro.',
                'price' => 149999.00,
                'stock' => 12,
                'category_id' => $electronics->id,
                'subcategory_id' => $laptopSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 499,
                'discount' => 12,
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'ASUS ROG Zephyrus G16 RTX 4070 Gaming Laptop',
                'unique_id' => 'AS1',
                'description' => 'High-performance gaming laptop with Intel i9, RTX 4070, 32GB RAM, 1TB SSD, and 240Hz display.',
                'price' => 199999.00,
                'stock' => 8,
                'category_id' => $electronics->id,
                'subcategory_id' => $laptopSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 18,
                'image' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=400&h=400&fit=crop',
            ],

            // Audio (Amazon bestsellers)
            [
                'name' => 'Sony WH-1000XM5 Wireless Noise Canceling Headphones',
                'unique_id' => 'SH1',
                'description' => 'Industry-leading noise canceling with 30-hour battery, multipoint connection, and premium sound quality.',
                'price' => 29990.00,
                'stock' => 50,
                'category_id' => $electronics->id,
                'subcategory_id' => $audioSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Apple AirPods Pro 2nd Generation with MagSafe',
                'unique_id' => 'AP1',
                'description' => 'Premium wireless earbuds with adaptive transparency, spatial audio, and up to 30 hours of listening time.',
                'price' => 24900.00,
                'stock' => 75,
                'category_id' => $electronics->id,
                'subcategory_id' => $audioSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 8,
                'image' => 'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'JBL Tune 770NC Wireless Over-Ear Headphones',
                'unique_id' => 'JB1',
                'description' => 'Adaptive noise cancelling headphones with 70-hour battery life, JBL Pure Bass sound, and hands-free calls.',
                'price' => 8999.00,
                'stock' => 60,
                'category_id' => $electronics->id,
                'subcategory_id' => $audioSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createFashionProducts($sellerId, $uniquePrefix = '')
    {

        $mensFashion = Category::where('name', "MEN'S FASHION")->first();
        $womensFashion = Category::where('name', "WOMEN'S FASHION")->first();
        $menSubcat = Subcategory::where('name', "Men's Shirts")->first();
        $womenSubcat = Subcategory::where('name', "Women's Dresses")->first();
        $footwearSubcat = Subcategory::where('name', "Men's Shoes")->first();

        if (!$mensFashion) {
            echo "[ERROR] Category 'MEN'S FASHION' not found. Please check CategorySeeder.\n";
            return;
        }
        if (!$womensFashion) {
            echo "[ERROR] Category 'WOMEN'S FASHION' not found. Please check CategorySeeder.\n";
            return;
        }
        if (!$menSubcat) {
            echo "[ERROR] Subcategory 'Men's Shirts' not found. Please check SubcategorySeeder.\n";
            return;
        }
        if (!$womenSubcat) {
            echo "[ERROR] Subcategory 'Women's Dresses' not found. Please check SubcategorySeeder.\n";
            return;
        }
        if (!$footwearSubcat) {
            echo "[ERROR] Subcategory 'Men's Shoes' not found. Please check SubcategorySeeder.\n";
            return;
        }

    $products = [
        // Men's Fashion (Popular from Amazon)
        [
            'name' => "Levi's 501 Original Fit Jeans - Dark Stonewash",
            'unique_id' => 'LJ1',
            'description' => 'Classic straight-leg jeans in premium denim with button fly, 100% cotton, and iconic Levi\'s styling.',
            'price' => 4999.00,
            'stock' => 100,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $menSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 99,
            'discount' => 25,
            'image' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400&h=400&fit=crop',
        ],
        [
            'name' => 'Ralph Lauren Classic Fit Polo Shirt - Navy',
            'unique_id' => 'RL1',
            'description' => 'Iconic cotton mesh polo shirt with ribbed collar, two-button placket, and embroidered pony logo.',
            'price' => 3499.00,
            'stock' => 80,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $menSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 99,
            'discount' => 15,
            'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop',
        ],
        [
            'name' => 'Tommy Hilfiger Cotton Crew Neck T-Shirt',
            'unique_id' => 'TH1',
            'description' => 'Premium 100% cotton crew neck t-shirt with classic Tommy Hilfiger logo and comfortable regular fit.',
            'price' => 1999.00,
            'stock' => 120,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $menSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 99,
            'discount' => 20,
            'image' => 'https://images.unsplash.com/photo-1529374255404-311a2a4f1fd9?w=400&h=400&fit=crop',
        ],

        // Women's Fashion (Popular from Amazon)
        [
            'name' => 'Zara Floral Print Midi Dress - Navy Floral',
            'unique_id' => 'ZD1',
            'description' => 'Elegant floral print midi dress with short sleeves, V-neckline, and flowing A-line silhouette perfect for any occasion.',
            'price' => 3999.00,
            'stock' => 60,
            'category_id' => $womensFashion->id,
            'subcategory_id' => $womenSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 99,
            'discount' => 30,
            'image' => 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&h=400&fit=crop',
        ],
        [
            'name' => 'H&M Ribbed Cotton Bodysuit - Black',
            'unique_id' => 'HM1',
            'description' => 'Fitted ribbed bodysuit in soft cotton jersey with snap fasteners at bottom and long sleeves.',
            'price' => 1499.00,
            'stock' => 85,
            'category_id' => $womensFashion->id,
            'subcategory_id' => $womenSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 99,
            'discount' => 25,
            'image' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=400&fit=crop',
        ],

        // Men's Shoes (Popular from Amazon)
        [
            'name' => 'Nike Air Jordan 1 Retro High OG - Chicago',
            'unique_id' => 'NJ1',
            'description' => 'Iconic basketball shoes with premium leather upper, Air-Sole unit, and classic Chicago colorway.',
            'price' => 12995.00,
            'stock' => 45,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $footwearSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'no',
            'delivery_charge' => 199,
            'discount' => 18,
            'image' => 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=400&h=400&fit=crop',
        ],
        [
            'name' => 'Adidas Ultraboost 22 Running Shoes - Core Black',
            'unique_id' => 'AU1',
            'description' => 'Premium running shoes with responsive Boost midsole, Primeknit upper, and Continental rubber outsole.',
            'price' => 16999.00,
            'stock' => 35,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $footwearSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 199,
            'discount' => 22,
            'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&h=400&fit=crop',
        ],
        [
            'name' => 'Converse Chuck Taylor All Star Classic High Top',
            'unique_id' => 'CV1',
            'description' => 'Timeless canvas sneakers with rubber toe cap, metal eyelets, and iconic Chuck Taylor design.',
            'price' => 4999.00,
            'stock' => 70,
            'category_id' => $mensFashion->id,
            'subcategory_id' => $footwearSubcat->id,
            'seller_id' => $sellerId,
            'gift_option' => 'yes',
            'delivery_charge' => 149,
            'discount' => 15,
            'image' => 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?w=400&h=400&fit=crop',
        ],
    ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createHomeKitchenProducts($sellerId, $uniquePrefix = '')
    {
        $home = Category::where('name', 'HOME & KITCHEN')->first();
        $kitchenSubcat = Subcategory::where('name', 'Kitchen Appliances')->first();
        $decorSubcat = Subcategory::where('name', 'Home Decor')->first();

    $products = [
            // Home Appliances (Amazon + Zepto popular items)
            [
                'name' => 'Dyson V15 Detect Absolute Cordless Vacuum',
                'unique_id' => 'DV1',
                'description' => 'Powerful cordless vacuum with laser dust detection, LCD screen, and up to 60 minutes runtime.',
                'price' => 59900.00,
                'stock' => 15,
                'category_id' => $home->id,
                'subcategory_id' => $kitchenSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 0,
                'discount' => 12,
                'image' => 'https://images.unsplash.com/photo-1558618644-fbd671c999fb?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'KitchenAid Artisan Stand Mixer 5-Quart',
                'unique_id' => 'KA1',
                'description' => 'Professional 5-quart stand mixer with 10 speeds, tilt-head design, and includes wire whip, dough hook.',
                'price' => 45999.00,
                'stock' => 20,
                'category_id' => $home->id,
                'subcategory_id' => $kitchenSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 299,
                'discount' => 8,
                'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Instant Pot Duo 7-in-1 Electric Pressure Cooker 6Qt',
                'unique_id' => 'IP2',
                'description' => 'Multi-use programmable pressure cooker, slow cooker, rice cooker, steamer, sauté, yogurt maker and warmer.',
                'price' => 8999.00,
                'stock' => 40,
                'category_id' => $home->id,
                'subcategory_id' => $kitchenSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1585237021262-e965b54e6e55?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Philips Air Fryer XXL Digital with Rapid Air Technology',
                'unique_id' => 'PH1',
                'description' => 'Large capacity air fryer with digital display, 7 preset programs, and dishwasher-safe parts.',
                'price' => 12999.00,
                'stock' => 30,
                'category_id' => $home->id,
                'subcategory_id' => $kitchenSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1574673215399-79d61d8d2c6b?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Round Gold Frame Wall Mirror 24-inch',
                'unique_id' => 'DM1',
                'description' => 'Modern decorative wall mirror with elegant gold metal frame, perfect for living room or bedroom.',
                'price' => 3999.00,
                'stock' => 40,
                'category_id' => $home->id,
                'subcategory_id' => $decorSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createBeautyProducts($sellerId, $uniquePrefix = '')
    {
        $beauty = Category::where('name', 'BEAUTY & PERSONAL CARE')->first();
        $skincareSubcat = Subcategory::where('name', 'Skincare')->first();
        $makeupSubcat = Subcategory::where('name', 'Makeup')->first();

    $products = [
            // Beauty & Personal Care (Premium brands like Sephora/Nykaa)
            [
                'name' => 'Fenty Beauty Pro Filt\'r Soft Matte Foundation',
                'unique_id' => 'FB1',
                'description' => 'Long-wear foundation with medium to full buildable coverage, available in 50 shades for all skin tones.',
                'price' => 3599.00,
                'stock' => 50,
                'category_id' => $beauty->id,
                'subcategory_id' => $makeupSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 15,
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'The Ordinary Niacinamide 10% + Zinc 1% Serum',
                'unique_id' => 'TO1',
                'description' => 'High-strength vitamin and mineral blemish formula to reduce appearance of blemishes and congestion.',
                'price' => 699.00,
                'stock' => 200,
                'category_id' => $beauty->id,
                'subcategory_id' => $skincareSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 49,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Charlotte Tilbury Pillow Talk Lipstick',
                'unique_id' => 'CT1',
                'description' => 'Iconic matte revolution lipstick in universally flattering nude-pink shade, buildable coverage.',
                'price' => 2899.00,
                'stock' => 60,
                'category_id' => $beauty->id,
                'subcategory_id' => $makeupSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 10,
                'image' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'CeraVe Hydrating Cleanser for Normal to Dry Skin',
                'unique_id' => 'CV1',
                'description' => 'Gentle foaming cleanser with hyaluronic acid and ceramides, developed with dermatologists.',
                'price' => 1299.00,
                'stock' => 100,
                'category_id' => $beauty->id,
                'subcategory_id' => $skincareSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Rare Beauty Soft Pinch Liquid Blush',
                'unique_id' => 'RB1',
                'description' => 'Weightless liquid blush that blends seamlessly for a natural, healthy-looking flush of color.',
                'price' => 1999.00,
                'stock' => 80,
                'category_id' => $beauty->id,
                'subcategory_id' => $makeupSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 18,
                'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createSportsProducts($sellerId, $uniquePrefix = '')
    {
        $sports = Category::where('name', 'SPORTS & FITNESS')->first();
        $exerciseSubcat = Subcategory::where('name', 'Exercise Equipment')->first();
        $athleticSubcat = Subcategory::where('name', 'Athletic Wear')->first();

    $products = [
            // Sports & Fitness (Popular from Amazon/Decathlon)
            [
                'name' => 'Bowflex SelectTech 552 Adjustable Dumbbells',
                'unique_id' => 'BF1',
                'description' => 'Space-efficient dumbbells that adjust from 5 to 52.5 pounds with unique dial system for home gym.',
                'price' => 32999.00,
                'stock' => 15,
                'category_id' => $sports->id,
                'subcategory_id' => $exerciseSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'no',
                'delivery_charge' => 599,
                'discount' => 12,
                'image' => 'https://images.unsplash.com/photo-1517963879433-6ad2b056d712?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Nike Dri-FIT Legend Training T-Shirt',
                'unique_id' => 'ND1',
                'description' => 'Lightweight training tee with Dri-FIT technology to keep you dry and comfortable during workouts.',
                'price' => 1999.00,
                'stock' => 100,
                'category_id' => $sports->id,
                'subcategory_id' => $athleticSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1544966503-7cc5ac882d5f?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Yoga Mat Premium 6mm Non-Slip Exercise Mat',
                'unique_id' => 'YM1',
                'description' => 'Extra thick yoga mat with excellent grip and cushioning, perfect for all types of yoga and pilates.',
                'price' => 2499.00,
                'stock' => 80,
                'category_id' => $sports->id,
                'subcategory_id' => $exerciseSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1506629905607-bb15e0c7b4c6?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Under Armour HeatGear Compression Leggings',
                'unique_id' => 'UA1',
                'description' => 'Ultra-tight compression fit with HeatGear fabric that wicks sweat and dries really fast.',
                'price' => 3999.00,
                'stock' => 60,
                'category_id' => $sports->id,
                'subcategory_id' => $athleticSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 15,
                'image' => 'https://images.unsplash.com/photo-1506629905607-bb15e0c7b4c6?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Resistance Bands Set with Door Anchor',
                'unique_id' => 'RB2',
                'description' => 'Complete resistance band workout kit with 5 stackable bands, door anchor, and exercise guide.',
                'price' => 1499.00,
                'stock' => 120,
                'category_id' => $sports->id,
                'subcategory_id' => $exerciseSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 30,
                'image' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createBooksProducts($sellerId, $uniquePrefix = '')
    {
        $books = Category::where('name', 'BOOKS & EDUCATION')->first();
        $fictionSubcat = Subcategory::where('name', 'Fiction')->first();
        $nonfictionSubcat = Subcategory::where('name', 'Non-Fiction')->first();

    $products = [
            // Books & Education (Bestsellers from Amazon)
            [
                'name' => 'Atomic Habits by James Clear',
                'unique_id' => 'AH1',
                'description' => 'An Easy & Proven Way to Build Good Habits & Break Bad Ones - #1 New York Times Bestseller.',
                'price' => 699.00,
                'stock' => 200,
                'category_id' => $books->id,
                'subcategory_id' => $nonfictionSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'The Psychology of Money by Morgan Housel',
                'unique_id' => 'PM1',
                'description' => 'Timeless lessons on wealth, greed, and happiness. How to think about money in a healthier way.',
                'price' => 599.00,
                'stock' => 150,
                'category_id' => $books->id,
                'subcategory_id' => $nonfictionSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'The Seven Husbands of Evelyn Hugo by Taylor Jenkins Reid',
                'unique_id' => 'EH1',
                'description' => 'Reclusive Hollywood icon Evelyn Hugo finally decides to tell her life story - but only to one reporter.',
                'price' => 459.00,
                'stock' => 180,
                'category_id' => $books->id,
                'subcategory_id' => $fictionSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 15,
                'image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'The Alchemist by Paulo Coelho',
                'unique_id' => 'AL1',
                'description' => 'Paulo Coelho\'s masterpiece about following your dreams - a magical fable about listening to your heart.',
                'price' => 299.00,
                'stock' => 400,
                'category_id' => $books->id,
                'subcategory_id' => $fictionSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 30,
                'image' => 'https://images.unsplash.com/photo-1485322551133-3a4c27a9d925?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Rich Dad Poor Dad by Robert T. Kiyosaki',
                'unique_id' => 'RD1',
                'description' => 'What the Rich Teach Their Kids About Money That the Poor and Middle Class Do Not!',
                'price' => 399.00,
                'stock' => 300,
                'category_id' => $books->id,
                'subcategory_id' => $nonfictionSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 99,
                'discount' => 35,
                'image' => 'https://images.unsplash.com/photo-1554415707-6e8cfc93fe23?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }

    private function createOtherProducts($sellerId, $uniquePrefix = '')
    {
    // Toys
    $toys = Category::where('name', "KIDS & TOYS")->first();
    $educationalSubcat = Subcategory::where('name', 'Educational Toys')->first();
        
    // Health
    $health = Category::where('name', 'HEALTH & WELLNESS')->first();
    $vitaminsSubcat = Subcategory::where('name', 'Vitamins & Supplements')->first();

    if (!$toys) {
        echo "[ERROR] Category 'KIDS & TOYS' not found. Please check CategorySeeder.\n";
        return;
    }
    if (!$educationalSubcat) {
        echo "[ERROR] Subcategory 'Educational Toys' not found. Please check SubcategorySeeder.\n";
        return;
    }
    if (!$health) {
        echo "[ERROR] Category 'HEALTH & WELLNESS' not found. Please check CategorySeeder.\n";
        return;
    }
    if (!$vitaminsSubcat) {
        echo "[ERROR] Subcategory 'Vitamins & Supplements' not found. Please check SubcategorySeeder.\n";
        return;
    }
    $products = [
            // Kids & Toys (Popular from Amazon/Zepto kids section)
            [
                'name' => 'LEGO Creator 3-in-1 Cyber Drone Building Set',
                'unique_id' => 'LG1',
                'description' => 'Build 3 different models: cyber drone, cyber mech, and cyber scooter with this 113-piece creative set.',
                'price' => 2999.00,
                'stock' => 60,
                'category_id' => $toys->id,
                'subcategory_id' => $educationalSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 149,
                'discount' => 15,
                'image' => 'https://images.unsplash.com/photo-1558060370-d532d3d86191?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Melissa & Doug Wooden Activity Table',
                'unique_id' => 'MD1',
                'description' => 'Multi-activity table with bead maze, shape sorter, counting, and spinning gears for toddlers.',
                'price' => 4999.00,
                'stock' => 40,
                'category_id' => $toys->id,
                'subcategory_id' => $educationalSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 199,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Hot Wheels Track Builder Unlimited Triple Loop Kit',
                'unique_id' => 'HW1',
                'description' => 'Motorized booster with triple loop track set, includes 1 Hot Wheels vehicle and connector pieces.',
                'price' => 3499.00,
                'stock' => 50,
                'category_id' => $toys->id,
                'subcategory_id' => $educationalSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'yes',
                'delivery_charge' => 149,
                'discount' => 18,
                'image' => 'https://images.unsplash.com/photo-1472457897821-70d3819a0e24?w=400&h=400&fit=crop',
            ],
            // Health & Wellness (Popular supplements from Amazon)
            [
                'name' => 'Nature Made Multivitamin Daily Tablets',
                'unique_id' => 'NM1',
                'description' => 'Complete daily nutrition with 23 key vitamins and minerals, USP verified, 130 tablets.',
                'price' => 1299.00,
                'stock' => 200,
                'category_id' => $health->id,
                'subcategory_id' => $vitaminsSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'no',
                'delivery_charge' => 99,
                'discount' => 25,
                'image' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Nordic Naturals Ultimate Omega Fish Oil',
                'unique_id' => 'NN1',
                'description' => 'High-potency omega-3 supplement with EPA and DHA for heart and brain health, 60 soft gels.',
                'price' => 2499.00,
                'stock' => 100,
                'category_id' => $health->id,
                'subcategory_id' => $vitaminsSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'no',
                'delivery_charge' => 99,
                'discount' => 20,
                'image' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=400&h=400&fit=crop',
            ],
            [
                'name' => 'Garden of Life Vitamin D3 5000 IU',
                'unique_id' => 'GOL1',
                'description' => 'Whole food vitamin D3 supplement for immune support and bone health, 60 vegetarian capsules.',
                'price' => 1899.00,
                'stock' => 150,
                'category_id' => $health->id,
                'subcategory_id' => $vitaminsSubcat->id,
                'seller_id' => $sellerId,
                'gift_option' => 'no',
                'delivery_charge' => 99,
                'discount' => 30,
                'image' => 'https://images.unsplash.com/photo-1550572017-edd951b55104?w=400&h=400&fit=crop',
            ],
        ];

        foreach ($products as $product) {
            $productCopy = $product;
            if ($uniquePrefix) {
                $productCopy['unique_id'] = $uniquePrefix . $product['unique_id'];
            }
            $productCopy['seller_id'] = $sellerId;
            Product::firstOrCreate(
                ['unique_id' => $productCopy['unique_id']],
                $productCopy
            );
        }
    }
}

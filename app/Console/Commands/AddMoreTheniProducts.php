<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;

class AddMoreTheniProducts extends Command
{
    protected $signature = 'seller:add-more-theni-products';
    protected $description = 'Add more products for Theni Selvakummar';

    private $sellerId = 2; // From previous command output
    private $sellerName = 'Theni Selvakummar';
    private $results = [];

    public function handle()
    {
        $this->info("🚀 Adding more products for {$this->sellerName}...");
        
        try {
            $this->processMoreProducts();
            $this->displayResults();
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function processMoreProducts()
    {
        $products = $this->getAdditionalProductData();

        foreach ($products as $index => $productData) {
            try {
                $this->createProduct($productData, $index + 711); // Start from SRM711
            } catch (\Exception $e) {
                $this->results['errors'][] = "Product {$productData['name']}: " . $e->getMessage();
                $this->error("❌ Error: {$productData['name']} - {$e->getMessage()}");
            }
        }
    }

    private function getAdditionalProductData()
    {
        return [
            [
                'name' => 'Curry Leaves Powder 100g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Fresh curry leaves dried and powdered. Rich in antioxidants and vitamins.',
                'price' => 120.00,
                'discount' => 0,
                'delivery_charge' => 35,
                'stock' => 80,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Aloe Vera Gel 200ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Skincare',
                'description' => 'Pure aloe vera gel for skin and hair care. Natural moisturizer and healer.',
                'price' => 180.00,
                'discount' => 15,
                'delivery_charge' => 40,
                'stock' => 70,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Millets Mix 500g',
                'category' => 'Groceries',
                'subcategory' => 'Health Foods',
                'description' => 'Nutritious mix of various millets. High in protein and fiber.',
                'price' => 220.00,
                'discount' => 10,
                'delivery_charge' => 50,
                'stock' => 60,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Moringa Powder 150g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Supplements',
                'description' => 'Organic moringa leaf powder. Superfood rich in vitamins and minerals.',
                'price' => 280.00,
                'discount' => 12,
                'delivery_charge' => 45,
                'stock' => 50,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Groundnut Oil 1L',
                'category' => 'Groceries',
                'subcategory' => 'Cooking Oils',
                'description' => 'Cold-pressed groundnut oil. Heart-healthy and flavorful cooking oil.',
                'price' => 350.00,
                'discount' => 8,
                'delivery_charge' => 80,
                'stock' => 40,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Hibiscus Flower Powder 100g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Hair Care',
                'description' => 'Natural hibiscus powder for hair conditioning. Promotes hair growth and shine.',
                'price' => 160.00,
                'discount' => 5,
                'delivery_charge' => 35,
                'stock' => 90,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Tamarind Paste 250g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Pure tamarind paste without additives. Essential for South Indian cooking.',
                'price' => 140.00,
                'discount' => 0,
                'delivery_charge' => 40,
                'stock' => 100,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Herbal Bath Powder 200g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Bath & Body',
                'description' => 'Traditional herbal ubtan for glowing skin. Mix of 12 natural ingredients.',
                'price' => 200.00,
                'discount' => 10,
                'delivery_charge' => 45,
                'stock' => 65,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Sesame Oil 500ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Hair Care',
                'description' => 'Pure sesame oil for massage and hair care. Traditional Ayurvedic oil.',
                'price' => 240.00,
                'discount' => 8,
                'delivery_charge' => 50,
                'stock' => 55,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Rock Salt 1kg',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Natural rock salt with minerals. Healthier alternative to refined salt.',
                'price' => 100.00,
                'discount' => 0,
                'delivery_charge' => 60,
                'stock' => 150,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Neem Oil 200ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Skincare',
                'description' => 'Pure neem oil for skin conditions. Natural antiseptic and moisturizer.',
                'price' => 220.00,
                'discount' => 15,
                'delivery_charge' => 45,
                'stock' => 45,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Fenugreek Powder 100g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Fresh ground fenugreek powder. Adds distinctive flavor to curries.',
                'price' => 130.00,
                'discount' => 5,
                'delivery_charge' => 35,
                'stock' => 85,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Coconut Milk Powder 200g',
                'category' => 'Groceries',
                'subcategory' => 'Dairy Alternatives',
                'description' => 'Instant coconut milk powder. Perfect for curries and desserts.',
                'price' => 180.00,
                'discount' => 10,
                'delivery_charge' => 40,
                'stock' => 70,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Amla Powder 150g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Supplements',
                'description' => 'Organic amla powder rich in Vitamin C. Boosts immunity and hair health.',
                'price' => 160.00,
                'discount' => 8,
                'delivery_charge' => 40,
                'stock' => 80,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Mustard Oil 500ml',
                'category' => 'Groceries',
                'subcategory' => 'Cooking Oils',
                'description' => 'Pure mustard oil for cooking and massage. Traditional and healthy.',
                'price' => 200.00,
                'discount' => 5,
                'delivery_charge' => 55,
                'stock' => 60,
                'gift_option' => 'no'
            ]
        ];
    }

    private function createProduct($productData, $srmNumber)
    {
        // Find or create category
        $category = Category::firstOrCreate([
            'name' => $productData['category']
        ], [
            'unique_id' => strtoupper(Str::random(3)),
            'emoji' => $this->getCategoryEmoji($productData['category'])
        ]);

        // Find or create subcategory
        $subcategory = Subcategory::firstOrCreate([
            'name' => $productData['subcategory'],
            'category_id' => $category->id
        ], [
            'unique_id' => strtoupper(Str::random(3))
        ]);

        // Generate unique product ID
        $uniqueId = 'SRM' . $srmNumber;

        // Handle image
        $imagePath = $this->processProductImage($srmNumber);

        // Create product
        $product = Product::create([
            'name' => $productData['name'],
            'unique_id' => $uniqueId,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'seller_id' => $this->sellerId,
            'description' => $productData['description'],
            'price' => $productData['price'],
            'discount' => $productData['discount'] ?? 0,
            'delivery_charge' => $productData['delivery_charge'] ?? 0,
            'gift_option' => $productData['gift_option'] ?? 'no',
            'stock' => $productData['stock'] ?? 1,
            'image' => $imagePath
        ]);

        $this->results['success'][] = [
            'name' => $product->name,
            'unique_id' => $product->unique_id,
            'category' => $category->name,
            'price' => $product->price,
            'image' => $imagePath ? 'Yes' : 'No'
        ];

        $this->info("✅ Created: {$product->name} (ID: {$product->unique_id})");

        return $product;
    }

    private function processProductImage($srmNumber)
    {
        $sourceDir = base_path('SRM IMG/');
        $targetDir = storage_path('app/public/products/');

        // Look for image with this SRM number
        $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $imageName = 'SRM' . $srmNumber;

        foreach ($possibleExtensions as $ext) {
            $sourceFile = $sourceDir . $imageName . '.' . $ext;
            if (file_exists($sourceFile)) {
                $targetFileName = $imageName . '_' . time() . '.' . $ext;
                $targetFile = $targetDir . $targetFileName;

                if (copy($sourceFile, $targetFile)) {
                    $this->info("📷 Copied image: {$imageName}.{$ext}");
                    return 'products/' . $targetFileName;
                }
                break;
            }
        }

        $this->warn("📷 No image found for: {$imageName}");
        return null;
    }

    private function getCategoryEmoji($categoryName)
    {
        $emojiMap = [
            'Health & Beauty' => '💄',
            'Groceries' => '🛒',
            'Supplements' => '💊',
            'Cooking Oils' => '🫒',
            'Dairy Alternatives' => '🥥'
        ];

        return $emojiMap[$categoryName] ?? '🛍️';
    }

    private function displayResults()
    {
        $this->info("\n📊 ADDITIONAL PRODUCTS CREATED:");
        $this->info("• Products Created: " . count($this->results['success'] ?? []));
        $this->info("• Total Products for {$this->sellerName}: " . (10 + count($this->results['success'] ?? [])));
    }
}
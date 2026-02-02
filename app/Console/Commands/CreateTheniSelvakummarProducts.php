<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class CreateTheniSelvakummarProducts extends Command
{
    protected $signature = 'seller:create-theni-products';
    protected $description = 'Create products for Theni Selvakummar with images';

    private $sellerId;
    private $sellerName = 'Theni Selvakummar';
    private $results = [];

    public function handle()
    {
        $this->info("🚀 Creating products for {$this->sellerName}...");
        
        try {
            $this->initializeSeller();
            $this->processProducts();
            $this->displayResults();
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function initializeSeller()
    {
        // Find or create seller
        $user = User::where('name', 'like', '%Theni%')
                    ->orWhere('name', 'like', '%Selvakummar%')
                    ->first();

        if (!$user) {
            // Create new user/seller
            $user = User::create([
                'name' => $this->sellerName,
                'email' => 'theni.selvakummar@grabbaskets.com',
                'password' => Hash::make('password123'),
                'role' => 'seller',
                'phone' => '9876543210',
                'email_verified_at' => now()
            ]);

            // Create seller profile
            Seller::create([
                'user_id' => $user->id,
                'name' => $this->sellerName,
                'email' => $user->email,
                'phone' => '9876543210',
                'store_name' => $this->sellerName . ' Store',
                'address' => 'Theni, Tamil Nadu'
            ]);

            $this->info("✅ Created new seller: {$this->sellerName}");
        } else {
            $this->info("📍 Using existing seller: {$user->name}");
        }

        $this->sellerId = $user->id;
    }

    private function processProducts()
    {
        $products = $this->getSampleProductData();

        foreach ($products as $index => $productData) {
            try {
                $this->createProduct($productData, $index + 701); // Start from SRM701
            } catch (\Exception $e) {
                $this->results['errors'][] = "Product {$productData['name']}: " . $e->getMessage();
                $this->error("❌ Error: {$productData['name']} - {$e->getMessage()}");
            }
        }
    }

    private function getSampleProductData()
    {
        return [
            [
                'name' => 'Premium Coconut Oil 500ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Hair Care',
                'description' => 'Pure cold-pressed coconut oil for hair and skin care. Natural and chemical-free.',
                'price' => 250.00,
                'discount' => 10,
                'delivery_charge' => 50,
                'stock' => 100,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Organic Turmeric Powder 250g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Fresh organic turmeric powder from Theni farms. High curcumin content.',
                'price' => 180.00,
                'discount' => 5,
                'delivery_charge' => 40,
                'stock' => 75,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Handmade Neem Soap 100g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Bath & Body',
                'description' => 'Natural neem soap with antibacterial properties. Handcrafted with traditional methods.',
                'price' => 120.00,
                'discount' => 0,
                'delivery_charge' => 30,
                'stock' => 200,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Pure Honey 500g',
                'category' => 'Groceries',
                'subcategory' => 'Health Foods',
                'description' => 'Raw unprocessed honey from local beehives. Rich in natural enzymes and minerals.',
                'price' => 350.00,
                'discount' => 15,
                'delivery_charge' => 60,
                'stock' => 50,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Cardamom Green 50g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Premium green cardamom from Western Ghats. Aromatic and flavorful.',
                'price' => 500.00,
                'discount' => 8,
                'delivery_charge' => 50,
                'stock' => 30,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Herbal Hair Oil 200ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Hair Care',
                'description' => 'Ayurvedic hair oil blend with 15 natural herbs. Promotes hair growth and shine.',
                'price' => 280.00,
                'discount' => 12,
                'delivery_charge' => 45,
                'stock' => 80,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Organic Jaggery 1kg',
                'category' => 'Groceries',
                'subcategory' => 'Sweeteners',
                'description' => 'Pure organic jaggery made from sugarcane. Natural sweetener rich in minerals.',
                'price' => 200.00,
                'discount' => 0,
                'delivery_charge' => 70,
                'stock' => 120,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Sandalwood Face Pack 100g',
                'category' => 'Health & Beauty',
                'subcategory' => 'Skincare',
                'description' => 'Natural sandalwood face pack for glowing skin. Suitable for all skin types.',
                'price' => 320.00,
                'discount' => 20,
                'delivery_charge' => 40,
                'stock' => 60,
                'gift_option' => 'yes'
            ],
            [
                'name' => 'Black Pepper Powder 100g',
                'category' => 'Groceries',
                'subcategory' => 'Spices',
                'description' => 'Fresh ground black pepper from Kerala. Strong aroma and pungent taste.',
                'price' => 150.00,
                'discount' => 5,
                'delivery_charge' => 35,
                'stock' => 90,
                'gift_option' => 'no'
            ],
            [
                'name' => 'Rose Water 250ml',
                'category' => 'Health & Beauty',
                'subcategory' => 'Skincare',
                'description' => 'Pure rose water for face and hair. Natural toner with anti-aging properties.',
                'price' => 180.00,
                'discount' => 10,
                'delivery_charge' => 40,
                'stock' => 100,
                'gift_option' => 'yes'
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
            'subcategory' => $subcategory->name,
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

        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

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
                } else {
                    $this->warn("⚠️  Failed to copy image: {$imageName}.{$ext}");
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
            'Spices' => '🌶️',
            'Hair Care' => '💇',
            'Bath & Body' => '🛁',
            'Health Foods' => '🍯',
            'Skincare' => '✨',
            'Sweeteners' => '🍯'
        ];

        return $emojiMap[$categoryName] ?? '🛍️';
    }

    private function displayResults()
    {
        $this->info("\n" . str_repeat("=", 60));
        $this->info("📊 BULK UPLOAD RESULTS FOR {$this->sellerName}");
        $this->info(str_repeat("=", 60));

        if (!empty($this->results['success'])) {
            $this->info("\n✅ SUCCESSFULLY CREATED PRODUCTS (" . count($this->results['success']) . "):");
            $this->info(str_repeat("-", 60));

            foreach ($this->results['success'] as $product) {
                $this->line(sprintf(
                    "ID: %-8s | %-30s | ₹%-8.2f | %s",
                    $product['unique_id'],
                    substr($product['name'], 0, 30),
                    $product['price'],
                    $product['image']
                ));
            }
        }

        if (!empty($this->results['errors'])) {
            $this->error("\n❌ ERRORS (" . count($this->results['errors']) . "):");
            $this->info(str_repeat("-", 60));

            foreach ($this->results['errors'] as $error) {
                $this->error("• " . $error);
            }
        }

        $this->info("\n📈 SUMMARY:");
        $this->info("• Products Created: " . count($this->results['success'] ?? []));
        $this->info("• Errors: " . count($this->results['errors'] ?? []));
        $this->info("• Seller: {$this->sellerName} (ID: {$this->sellerId})");

        $this->info("\n🔗 NEXT STEPS:");
        $this->info("• Login as seller to view products in dashboard");
        $this->info("• Use bulk image upload if needed: /seller/dashboard");
        $this->info("• Check image diagnostic: /image-diagnostic.php");

        $this->info("\n" . str_repeat("=", 60));
    }
}
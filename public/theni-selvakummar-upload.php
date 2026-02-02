<?php
/*
 * Bulk Product Upload Script for Theni Selvakummar
 * This script handles Excel product data and image associations
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TheniSelvakummarBulkUpload {
    
    private $sellerId;
    private $sellerName = 'Theni Selvakummar';
    private $results = [];
    
    public function __construct() {
        $this->initializeSeller();
    }
    
    private function initializeSeller() {
        // Find or create seller
        $user = User::where('name', 'like', '%' . $this->sellerName . '%')
                    ->orWhere('email', 'like', '%theni%')
                    ->orWhere('email', 'like', '%selvakummar%')
                    ->first();
        
        if (!$user) {
            // Create new user/seller
            $user = User::create([
                'name' => $this->sellerName,
                'email' => strtolower(str_replace(' ', '', $this->sellerName)) . '@grabbaskets.com',
                'password' => bcrypt('password123'),
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
            
            echo "✅ Created new seller: {$this->sellerName}\n";
        }
        
        $this->sellerId = $user->id;
        echo "📍 Using seller ID: {$this->sellerId} ({$this->sellerName})\n";
    }
    
    public function processProducts() {
        echo "\n🚀 Starting bulk product upload for {$this->sellerName}...\n\n";
        
        // Sample product data - you can extend this with Excel parsing
        $products = $this->getSampleProductData();
        
        foreach ($products as $index => $productData) {
            try {
                $this->createProduct($productData, $index + 701); // Start from SRM701
            } catch (Exception $e) {
                $this->results['errors'][] = "Product {$productData['name']}: " . $e->getMessage();
                echo "❌ Error: {$productData['name']} - {$e->getMessage()}\n";
            }
        }
        
        $this->displayResults();
    }
    
    private function getSampleProductData() {
        // Sample product data - replace with Excel parsing
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
    
    private function createProduct($productData, $srmNumber) {
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
        
        echo "✅ Created: {$product->name} (ID: {$product->unique_id})\n";
        
        return $product;
    }
    
    private function processProductImage($srmNumber) {
        $sourceDir = __DIR__ . '/../SRM IMG/';
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
                    echo "📷 Copied image: {$imageName}.{$ext}\n";
                    return 'products/' . $targetFileName;
                } else {
                    echo "⚠️  Failed to copy image: {$imageName}.{$ext}\n";
                }
                break;
            }
        }
        
        echo "📷 No image found for: {$imageName}\n";
        return null;
    }
    
    private function getCategoryEmoji($categoryName) {
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
    
    private function displayResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 BULK UPLOAD RESULTS FOR {$this->sellerName}\n";
        echo str_repeat("=", 60) . "\n";
        
        if (!empty($this->results['success'])) {
            echo "\n✅ SUCCESSFULLY CREATED PRODUCTS (" . count($this->results['success']) . "):\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($this->results['success'] as $product) {
                echo sprintf(
                    "ID: %-8s | %-30s | ₹%-8.2f | %s\n",
                    $product['unique_id'],
                    substr($product['name'], 0, 30),
                    $product['price'],
                    $product['image']
                );
            }
        }
        
        if (!empty($this->results['errors'])) {
            echo "\n❌ ERRORS (" . count($this->results['errors']) . "):\n";
            echo str_repeat("-", 60) . "\n";
            
            foreach ($this->results['errors'] as $error) {
                echo "• {$error}\n";
            }
        }
        
        echo "\n📈 SUMMARY:\n";
        echo "• Products Created: " . count($this->results['success'] ?? []) . "\n";
        echo "• Errors: " . count($this->results['errors'] ?? []) . "\n";
        echo "• Seller: {$this->sellerName} (ID: {$this->sellerId})\n";
        
        echo "\n🔗 NEXT STEPS:\n";
        echo "• Login as seller to view products in dashboard\n";
        echo "• Use bulk image upload if needed: /seller/dashboard\n";
        echo "• Check image diagnostic: /image-diagnostic.php\n";
        
        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Initialize and run
try {
    echo "🚀 Theni Selvakummar Product Upload Script\n";
    echo "==========================================\n";
    
    $uploader = new TheniSelvakummarBulkUpload();
    $uploader->processProducts();
    
    echo "\n✅ Upload process completed successfully!\n";
    echo "🌐 Visit the application to see the new products.\n";
    
} catch (Exception $e) {
    echo "\n❌ Script Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
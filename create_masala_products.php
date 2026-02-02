<?php
// This script will replace current products with masala/spice products and fetch appropriate images
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

$googleApiKey = 'AIzaSyCpNq0PrC0QhLaXcWV-VtzM0rOWNF0njP4';
$googleCseId = 'e0705b44ba5784dea';

// Define masala/spice products
$masalaProducts = [
    ['name' => 'Red Chili Powder (Lal Mirch)', 'category' => 'COOKING', 'price' => 50, 'description' => 'Premium quality red chili powder for authentic taste'],
    ['name' => 'Turmeric Powder (Haldi)', 'category' => 'COOKING', 'price' => 40, 'description' => 'Pure turmeric powder with natural color and flavor'],
    ['name' => 'Coriander Powder (Dhania)', 'category' => 'COOKING', 'price' => 35, 'description' => 'Fresh ground coriander powder for aromatic cooking'],
    ['name' => 'Cumin Powder (Jeera)', 'category' => 'COOKING', 'price' => 45, 'description' => 'Roasted cumin powder for enhanced flavor'],
    ['name' => 'Garam Masala', 'category' => 'COOKING', 'price' => 60, 'description' => 'Traditional blend of aromatic spices'],
    ['name' => 'Biryani Masala', 'category' => 'COOKING', 'price' => 55, 'description' => 'Special spice mix for authentic biryani'],
    ['name' => 'Chicken Masala', 'category' => 'COOKING', 'price' => 50, 'description' => 'Perfect spice blend for chicken curry'],
    ['name' => 'Mutton Masala', 'category' => 'COOKING', 'price' => 65, 'description' => 'Rich spice mix for mutton dishes'],
    ['name' => 'Fish Masala', 'category' => 'COOKING', 'price' => 48, 'description' => 'Coastal spice blend for fish curry'],
    ['name' => 'Pav Bhaji Masala', 'category' => 'COOKING', 'price' => 42, 'description' => 'Mumbai style pav bhaji spice mix'],
    ['name' => 'Rajma Masala', 'category' => 'COOKING', 'price' => 38, 'description' => 'Special blend for kidney bean curry'],
    ['name' => 'Sambhar Masala', 'category' => 'COOKING', 'price' => 40, 'description' => 'South Indian sambhar spice powder'],
    ['name' => 'Rasam Powder', 'category' => 'COOKING', 'price' => 35, 'description' => 'Traditional rasam spice mix'],
    ['name' => 'Meat Masala', 'category' => 'COOKING', 'price' => 55, 'description' => 'Universal meat spice blend'],
    ['name' => 'Paneer Masala', 'category' => 'COOKING', 'price' => 45, 'description' => 'Special spice mix for paneer dishes'],
    ['name' => 'Vegetable Masala', 'category' => 'COOKING', 'price' => 40, 'description' => 'All-purpose vegetable spice mix'],
    ['name' => 'Tandoori Masala', 'category' => 'COOKING', 'price' => 50, 'description' => 'Authentic tandoori spice blend'],
    ['name' => 'Curry Powder', 'category' => 'COOKING', 'price' => 45, 'description' => 'Classic curry spice powder'],
    ['name' => 'Black Pepper Powder', 'category' => 'COOKING', 'price' => 80, 'description' => 'Fresh ground black pepper'],
    ['name' => 'Cardamom Powder (Elaichi)', 'category' => 'COOKING', 'price' => 120, 'description' => 'Aromatic cardamom powder'],
    ['name' => 'Cinnamon Powder (Dalchini)', 'category' => 'COOKING', 'price' => 90, 'description' => 'Sweet cinnamon powder'],
    ['name' => 'Clove Powder (Laung)', 'category' => 'COOKING', 'price' => 150, 'description' => 'Intense clove powder'],
    ['name' => 'Nutmeg Powder (Jaiphal)', 'category' => 'COOKING', 'price' => 200, 'description' => 'Premium nutmeg powder'],
    ['name' => 'Mace Powder (Javitri)', 'category' => 'COOKING', 'price' => 250, 'description' => 'Exotic mace powder'],
    ['name' => 'Fennel Powder (Saunf)', 'category' => 'COOKING', 'price' => 60, 'description' => 'Sweet fennel seed powder'],
    ['name' => 'Fenugreek Powder (Methi)', 'category' => 'COOKING', 'price' => 45, 'description' => 'Bitter fenugreek leaf powder'],
    ['name' => 'Asafoetida (Hing)', 'category' => 'COOKING', 'price' => 180, 'description' => 'Pure asafoetida for tempering'],
    ['name' => 'Mustard Powder (Sarson)', 'category' => 'COOKING', 'price' => 40, 'description' => 'Pungent mustard seed powder'],
    ['name' => 'Bay Leaf Powder (Tej Patta)', 'category' => 'COOKING', 'price' => 70, 'description' => 'Aromatic bay leaf powder'],
    ['name' => 'Star Anise Powder', 'category' => 'COOKING', 'price' => 160, 'description' => 'Sweet star anise powder'],
    ['name' => 'Dry Ginger Powder (Sonth)', 'category' => 'COOKING', 'price' => 85, 'description' => 'Pure dry ginger powder'],
    ['name' => 'Amchur Powder (Dry Mango)', 'category' => 'COOKING', 'price' => 65, 'description' => 'Tangy dry mango powder'],
    ['name' => 'Chat Masala', 'category' => 'COOKING', 'price' => 35, 'description' => 'Tangy chat spice mix'],
    ['name' => 'Kitchen King Masala', 'category' => 'COOKING', 'price' => 55, 'description' => 'Universal kitchen spice blend'],
    ['name' => 'Shahi Garam Masala', 'category' => 'COOKING', 'price' => 75, 'description' => 'Royal garam masala blend'],
    ['name' => 'Kashmiri Red Chili Powder', 'category' => 'COOKING', 'price' => 85, 'description' => 'Mild Kashmiri red chili powder'],
    ['name' => 'Deggi Mirch Powder', 'category' => 'COOKING', 'price' => 70, 'description' => 'Colorful deggi mirch powder'],
    ['name' => 'Pickle Masala', 'category' => 'COOKING', 'price' => 45, 'description' => 'Special pickle spice mix'],
    ['name' => 'Tea Masala', 'category' => 'COOKING', 'price' => 60, 'description' => 'Aromatic tea spice blend'],
    ['name' => 'Masala Chai Powder', 'category' => 'COOKING', 'price' => 55, 'description' => 'Ready-to-use chai masala'],
    ['name' => 'Pani Puri Masala', 'category' => 'COOKING', 'price' => 30, 'description' => 'Tangy pani puri spice mix'],
    ['name' => 'Bhel Puri Masala', 'category' => 'COOKING', 'price' => 35, 'description' => 'Mumbai bhel puri seasoning'],
    ['name' => 'Dosa Podi', 'category' => 'COOKING', 'price' => 40, 'description' => 'South Indian dosa gun powder'],
    ['name' => 'Idli Podi', 'category' => 'COOKING', 'price' => 38, 'description' => 'Spicy idli gun powder'],
    ['name' => 'Coconut Chutney Powder', 'category' => 'COOKING', 'price' => 45, 'description' => 'Instant coconut chutney mix'],
    ['name' => 'Maharashtrian Goda Masala', 'category' => 'COOKING', 'price' => 80, 'description' => 'Traditional Maharashtrian spice blend'],
    ['name' => 'Punjabi Garam Masala', 'category' => 'COOKING', 'price' => 65, 'description' => 'North Indian garam masala'],
    ['name' => 'Bengali Panch Phoron', 'category' => 'COOKING', 'price' => 50, 'description' => 'Bengali five-spice blend'],
    ['name' => 'Gujarati Dhokla Masala', 'category' => 'COOKING', 'price' => 40, 'description' => 'Dhokla seasoning spice'],
    ['name' => 'Rajasthani Laal Maas Masala', 'category' => 'COOKING', 'price' => 70, 'description' => 'Spicy Rajasthani meat masala'],
    ['name' => 'Hyderabadi Biryani Masala', 'category' => 'COOKING', 'price' => 75, 'description' => 'Authentic Hyderabadi biryani spice'],
    ['name' => 'Kolhapuri Masala', 'category' => 'COOKING', 'price' => 65, 'description' => 'Fiery Kolhapuri spice blend'],
    ['name' => 'Kadai Masala', 'category' => 'COOKING', 'price' => 50, 'description' => 'Special kadai cooking spice'],
    ['name' => 'Malvani Masala', 'category' => 'COOKING', 'price' => 60, 'description' => 'Coastal Malvani spice mix'],
    ['name' => 'Awadhi Garam Masala', 'category' => 'COOKING', 'price' => 85, 'description' => 'Royal Awadhi spice blend'],
    ['name' => 'Kasuri Methi Powder', 'category' => 'COOKING', 'price' => 45, 'description' => 'Dried fenugreek leaves powder'],
    ['name' => 'Kala Namak (Black Salt)', 'category' => 'COOKING', 'price' => 35, 'description' => 'Mineral-rich black salt'],
    ['name' => 'Rock Salt Powder', 'category' => 'COOKING', 'price' => 25, 'description' => 'Pure rock salt powder'],
    ['name' => 'Sendha Namak', 'category' => 'COOKING', 'price' => 30, 'description' => 'Fasting salt (sendha namak)'],
    ['name' => 'Mixed Spice Powder', 'category' => 'COOKING', 'price' => 55, 'description' => 'Balanced mixed spice blend'],
    ['name' => 'Sandwich Masala', 'category' => 'COOKING', 'price' => 35, 'description' => 'Tangy sandwich seasoning'],
    ['name' => 'Fruit Chat Masala', 'category' => 'COOKING', 'price' => 40, 'description' => 'Sweet and tangy fruit seasoning'],
    ['name' => 'Roasted Cumin Powder', 'category' => 'COOKING', 'price' => 50, 'description' => 'Dry roasted cumin powder'],
    ['name' => 'Roasted Coriander Powder', 'category' => 'COOKING', 'price' => 40, 'description' => 'Dry roasted coriander powder'],
    ['name' => 'Organic Turmeric Powder', 'category' => 'COOKING', 'price' => 60, 'description' => 'Certified organic turmeric'],
    ['name' => 'Organic Red Chili Powder', 'category' => 'COOKING', 'price' => 75, 'description' => 'Certified organic red chili']
];

function fetchGoogleImage($query, $apiKey, $cseId) {
    $client = new Client();
    $url = 'https://www.googleapis.com/customsearch/v1';
    try {
        $response = $client->get($url, [
            'query' => [
                'q' => $query . ' spice powder masala',
                'cx' => $cseId,
                'key' => $apiKey,
                'searchType' => 'image',
                'num' => 1
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        if (!empty($data['items'][0]['link'])) {
            return $data['items'][0]['link'];
        }
    } catch (Exception $e) {
        echo "Google API error for $query: " . $e->getMessage() . "\n";
    }
    return null;
}

// Get COOKING category ID
$cookingCategory = \App\Models\Category::where('name', 'COOKING')->first();
if (!$cookingCategory) {
    echo "COOKING category not found. Creating it...\n";
    $cookingCategory = \App\Models\Category::create([
        'name' => 'COOKING',
        'unique_id' => 'COOK001'
    ]);
}

// Create or get a subcategory for spices
$spicesSubcategory = \App\Models\Subcategory::where('category_id', $cookingCategory->id)->first();
if (!$spicesSubcategory) {
    echo "Creating Spices subcategory...\n";
    $spicesSubcategory = \App\Models\Subcategory::create([
        'name' => 'Spices & Masala',
        'unique_id' => 'SPICE001',
        'category_id' => $cookingCategory->id,
        'description' => 'Traditional Indian spices and masala powders'
    ]);
}

echo "Clearing existing products...\n";
// Clear related records first
DB::table('cart_items')->delete();
DB::table('wishlists')->delete();
DB::table('reviews')->delete();
// Now safely delete products
\App\Models\Product::query()->delete();

echo "Adding masala products with images...\n";
$created = 0;
$uniqueIdCounter = 301;

foreach ($masalaProducts as $masala) {
    // Create product
    $product = \App\Models\Product::create([
        'name' => $masala['name'],
        'unique_id' => 'SRM' . $uniqueIdCounter,
        'category_id' => $cookingCategory->id,
        'subcategory_id' => $spicesSubcategory->id,
        'seller_id' => 1, // Assuming seller ID 1 exists
        'description' => $masala['description'],
        'price' => $masala['price'],
        'discount' => 0,
        'delivery_charge' => 50,
        'gift_option' => 'no',
        'stock' => 100
    ]);

    // Fetch and assign image
    $imgUrl = fetchGoogleImage($masala['name'], $googleApiKey, $googleCseId);
    if ($imgUrl) {
        $product->image = $imgUrl;
        $product->save();
        echo "[CREATED] {$masala['name']} -> {$imgUrl}\n";
    } else {
        echo "[CREATED] {$masala['name']} -> NO IMAGE\n";
    }

    $created++;
    $uniqueIdCounter++;
    
    // Small delay to avoid hitting API limits
    usleep(300000); // 0.3 seconds
}

echo "\nTotal masala products created: $created\n";
echo "Database updated with authentic Indian masala products!\n";
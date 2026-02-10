<?php
// This script will add oral care products with images
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

$googleApiKey = 'AIzaSyCpNq0PrC0QhLaXcWV-VtzM0rOWNF0njP4';
$googleCseId = 'e0705b44ba5784dea';

// Define oral care products
$oralCareProducts = [
    ['name' => 'Colgate Total Advanced Health Toothpaste 200g', 'price' => 120, 'description' => 'Complete protection for teeth and gums'],
    ['name' => 'Sensodyne Rapid Relief Toothpaste 100g', 'price' => 180, 'description' => 'Instant relief for sensitive teeth'],
    ['name' => 'Pepsodent Germi Check Toothpaste 150g', 'price' => 85, 'description' => 'Fights germs for 12 hours'],
    ['name' => 'Close Up Red Hot Toothpaste 80g', 'price' => 65, 'description' => 'Gel toothpaste with red gel formula'],
    ['name' => 'Oral-B Pro Health Toothpaste 140g', 'price' => 150, 'description' => 'Professional dental care at home'],
    ['name' => 'Himalaya Complete Care Toothpaste 100g', 'price' => 95, 'description' => 'Natural herbal toothpaste'],
    ['name' => 'Dabur Red Ayurvedic Toothpaste 200g', 'price' => 110, 'description' => 'Traditional Ayurvedic oral care'],
    ['name' => 'Patanjali Dant Kanti Toothpaste 100g', 'price' => 45, 'description' => 'Natural herbal dental care'],
    ['name' => 'Vicco Vajradanti Toothpaste 100g', 'price' => 75, 'description' => 'Ayurvedic toothpaste with vajradanti'],
    ['name' => 'Meswak Toothpaste Al Falah 150g', 'price' => 80, 'description' => 'Natural miswak extract toothpaste'],
    
    ['name' => 'Oral-B Cross Action Toothbrush Medium', 'price' => 85, 'description' => 'Clinically proven superior clean'],
    ['name' => 'Colgate 360 Charcoal Gold Toothbrush', 'price' => 95, 'description' => 'Charcoal infused bristles'],
    ['name' => 'Sensodyne Gentle Care Soft Toothbrush', 'price' => 120, 'description' => 'Extra soft bristles for sensitive teeth'],
    ['name' => 'Aquafresh Clean Control Toothbrush', 'price' => 70, 'description' => 'Flexible head for better reach'],
    ['name' => 'Pepsodent Active Salt Toothbrush', 'price' => 55, 'description' => 'Salt bristles for deep cleaning'],
    ['name' => 'Close Up Diamond Attraction Toothbrush', 'price' => 65, 'description' => 'Diamond shaped bristles'],
    ['name' => 'Himalaya Gum Expert Toothbrush Soft', 'price' => 60, 'description' => 'Gentle on gums'],
    ['name' => 'Dabur Red Premium Toothbrush', 'price' => 50, 'description' => 'Traditional Ayurvedic toothbrush'],
    
    ['name' => 'Listerine Cool Mint Mouthwash 250ml', 'price' => 180, 'description' => 'Kills 99.9% germs that cause bad breath'],
    ['name' => 'Oral-B Pro Expert Mouthwash 500ml', 'price' => 220, 'description' => 'Professional strength mouthwash'],
    ['name' => 'Colgate Plax Fresh Tea Mouthwash 250ml', 'price' => 140, 'description' => 'Natural tea extract mouthwash'],
    ['name' => 'Sensodyne Daily Care Mouthwash 500ml', 'price' => 280, 'description' => 'Gentle care for sensitive teeth'],
    ['name' => 'Himalaya HiOra Mouthwash 215ml', 'price' => 120, 'description' => 'Herbal mouthwash for complete oral care'],
    ['name' => 'Dabur Babool Mouthwash 175ml', 'price' => 85, 'description' => 'Ayurvedic mouthwash with babool'],
    
    ['name' => 'Oral-B Essential Floss Waxed 50m', 'price' => 180, 'description' => 'Slides easily between teeth'],
    ['name' => 'Colgate Total Pro Floss 25m', 'price' => 120, 'description' => 'Advanced floss for tight spaces'],
    ['name' => 'Sensodyne Gentle Floss 50m', 'price' => 150, 'description' => 'Gentle flossing for sensitive gums'],
    ['name' => 'Himalaya Dental Floss 50m', 'price' => 95, 'description' => 'Natural wax coating'],
    
    ['name' => 'Orajel Instant Pain Relief Gel 10g', 'price' => 160, 'description' => 'Fast acting oral pain relief'],
    ['name' => 'Dentogel Oral Pain Relief 15g', 'price' => 95, 'description' => 'Numbing gel for oral pain'],
    ['name' => 'Mucopain Gel 15g', 'price' => 110, 'description' => 'Local anesthetic for mouth ulcers'],
    
    ['name' => 'Colgate Optic White Whitening Strips', 'price' => 850, 'description' => 'Professional whitening at home'],
    ['name' => 'Oral-B 3D White Whitestrips', 'price' => 920, 'description' => 'Removes 10 years of stains'],
    ['name' => 'Sensodyne Whitening Pen 2ml', 'price' => 450, 'description' => 'Gentle whitening for sensitive teeth'],
    
    ['name' => 'Oral-B Pulsar Battery Toothbrush', 'price' => 380, 'description' => 'Vibrating bristles for better clean'],
    ['name' => 'Colgate 360 Battery Toothbrush', 'price' => 320, 'description' => 'Battery powered cleaning action'],
    ['name' => 'Sensodyne Power Toothbrush', 'price' => 450, 'description' => 'Gentle power for sensitive teeth'],
    
    ['name' => 'Fixodent Denture Adhesive Cream 40g', 'price' => 280, 'description' => 'Strong hold for dentures'],
    ['name' => 'Polident Denture Cleanser Tablets', 'price' => 220, 'description' => 'Deep cleans dentures'],
    ['name' => 'Sea Bond Denture Adhesive Strips', 'price' => 320, 'description' => 'Zinc-free denture strips'],
    
    ['name' => 'Oral-B Tongue Cleaner', 'price' => 120, 'description' => 'Removes bacteria from tongue'],
    ['name' => 'Colgate Tongue Scraper', 'price' => 85, 'description' => 'Effective tongue cleaning'],
    ['name' => 'Himalaya Tongue Cleaner Copper', 'price' => 150, 'description' => 'Traditional copper tongue cleaner'],
    
    ['name' => 'Oral-B Interdental Brushes Pack of 6', 'price' => 220, 'description' => 'Clean between teeth effectively'],
    ['name' => 'TePe Interdental Brush Set', 'price' => 280, 'description' => 'Swedish quality interdental brushes'],
    ['name' => 'Colgate Interdental Picks 40pcs', 'price' => 160, 'description' => 'Portable interdental cleaning'],
    
    ['name' => 'Oral-B Pro 1000 Electric Toothbrush', 'price' => 2500, 'description' => 'Rechargeable electric toothbrush'],
    ['name' => 'Philips Sonicare CleanCare+ Electric Toothbrush', 'price' => 3200, 'description' => 'Sonic technology for superior clean'],
    ['name' => 'Colgate ProClinical 150 Electric Toothbrush', 'price' => 1800, 'description' => 'Professional cleaning at home'],
    
    ['name' => 'TheraBreath Fresh Breath Oral Rinse 473ml', 'price' => 650, 'description' => 'Clinically proven fresh breath'],
    ['name' => 'Biotene Dry Mouth Oral Rinse 500ml', 'price' => 580, 'description' => 'Relief for dry mouth symptoms'],
    ['name' => 'ACT Anticavity Fluoride Rinse 532ml', 'price' => 420, 'description' => 'Strengthens teeth with fluoride']
];

function fetchGoogleImage($query, $apiKey, $cseId) {
    $client = new Client();
    $url = 'https://www.googleapis.com/customsearch/v1';
    try {
        $response = $client->get($url, [
            'query' => [
                'q' => $query . ' oral care dental hygiene',
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

// Get or create DENTAL CARE category
$dentalCategory = \App\Models\Category::where('name', 'DENTAL CARE')->first();
if (!$dentalCategory) {
    echo "DENTAL CARE category not found. Creating it...\n";
    $dentalCategory = \App\Models\Category::create([
        'name' => 'DENTAL CARE',
        'unique_id' => 'D1'
    ]);
}

// Create or get subcategories for oral care
$subcategories = [
    'Toothpaste' => 'T1',
    'Toothbrushes' => 'T2', 
    'Mouthwash' => 'M1',
    'Dental Accessories' => 'D1'
];

$subCatObjects = [];
foreach($subcategories as $name => $uniqueId) {
    $subcat = \App\Models\Subcategory::where('name', $name)->where('category_id', $dentalCategory->id)->first();
    if (!$subcat) {
        echo "Creating $name subcategory...\n";
        $subcat = \App\Models\Subcategory::create([
            'name' => $name,
            'unique_id' => $uniqueId,
            'category_id' => $dentalCategory->id,
            'description' => "Oral care products - $name"
        ]);
    }
    $subCatObjects[$name] = $subcat;
}

echo "Adding oral care products with images...\n";
$created = 0;
$uniqueIdCounter = 401; // Starting from 401 for oral care

foreach ($oralCareProducts as $product) {
    // Determine subcategory based on product name
    $subcategoryId = $subCatObjects['Dental Accessories']->id; // default
    if (strpos(strtolower($product['name']), 'toothpaste') !== false) {
        $subcategoryId = $subCatObjects['Toothpaste']->id;
    } elseif (strpos(strtolower($product['name']), 'toothbrush') !== false) {
        $subcategoryId = $subCatObjects['Toothbrushes']->id;
    } elseif (strpos(strtolower($product['name']), 'mouthwash') !== false || strpos(strtolower($product['name']), 'rinse') !== false) {
        $subcategoryId = $subCatObjects['Mouthwash']->id;
    }

    // Create product
    $newProduct = \App\Models\Product::create([
        'name' => $product['name'],
        'unique_id' => 'ORL' . $uniqueIdCounter,
        'category_id' => $dentalCategory->id,
        'subcategory_id' => $subcategoryId,
        'seller_id' => 1, // Assuming seller ID 1 exists
        'description' => $product['description'],
        'price' => $product['price'],
        'discount' => 0,
        'delivery_charge' => 40,
        'gift_option' => 'no',
        'stock' => 50
    ]);

    // Fetch and assign image
    $imgUrl = fetchGoogleImage($product['name'], $googleApiKey, $googleCseId);
    if ($imgUrl) {
        $newProduct->image = $imgUrl;
        $newProduct->save();
        echo "[CREATED] {$product['name']} -> {$imgUrl}\n";
    } else {
        echo "[CREATED] {$product['name']} -> NO IMAGE\n";
    }

    $created++;
    $uniqueIdCounter++;
    
    // Small delay to avoid hitting API limits
    usleep(200000); // 0.2 seconds
}

echo "\nTotal oral care products created: $created\n";
echo "Oral care category added successfully!\n";
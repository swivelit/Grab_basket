<?php
// Simple test without Laravel bootstrap to avoid potential issues
include_once 'vendor/autoload.php';

// Set up minimal environment
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/database/database.sqlite';

// Check if file exists first
if (!file_exists($_ENV['DB_DATABASE'])) {
    echo "Database file not found: " . $_ENV['DB_DATABASE'];
    exit;
}

// Try to connect to database directly
try {
    $pdo = new PDO('sqlite:' . $_ENV['DB_DATABASE']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Connection Test</h1>";
    
    // Check product 28
    $stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE id = ?");
    $stmt->execute([28]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<h2>Product 28 Details</h2>";
        echo "<p><strong>ID:</strong> " . $product['id'] . "</p>";
        echo "<p><strong>Name:</strong> " . $product['name'] . "</p>";
        echo "<p><strong>Image:</strong> " . $product['image'] . "</p>";
        
        // Generate image URL manually
        $imagePath = $product['image'];
        if (strpos($imagePath, 'images/') === 0) {
            $cleanPath = str_replace('images/', '', $imagePath);
            $imageUrl = "https://grabbaskets.laravel.cloud/images/" . $cleanPath;
            echo "<p><strong>Generated URL:</strong> " . $imageUrl . "</p>";
            echo "<img src='{$imageUrl}' alt='{$product['name']}' style='max-width: 300px; border: 1px solid #ccc;'/>";
        }
    } else {
        echo "<p>Product 28 not found in database</p>";
    }
    
    // Check a few SRM products
    echo "<h2>SRM Products</h2>";
    $stmt = $pdo->prepare("SELECT id, name, image FROM products WHERE image LIKE 'images/SRM%' LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "<p><strong>Product {$product['id']}:</strong> {$product['name']} - {$product['image']}</p>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
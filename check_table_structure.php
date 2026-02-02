<?php

try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Products Table Structure ===\n";
    $stmt = $pdo->query("PRAGMA table_info(products)");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['name']} | {$row['type']} | " . ($row['notnull'] ? 'NOT NULL' : 'NULL') . "\n";
    }
    
    echo "\n=== Sample Products with Details ===\n";
    $stmt = $pdo->query("SELECT id, name, image, unique_id FROM products WHERE image IS NOT NULL AND image != '' LIMIT 3");
    echo "Products WITH images:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Image: {$row['image']} | Unique: {$row['unique_id']}\n";
    }
    
    echo "\nProducts WITHOUT images (sample):\n";
    $stmt = $pdo->query("SELECT id, name, image, unique_id FROM products WHERE image IS NULL OR image = '' LIMIT 5");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Image: " . ($row['image'] ?: 'NULL') . " | Unique: {$row['unique_id']}\n";
    }
    
} catch(PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

?>
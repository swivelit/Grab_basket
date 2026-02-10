<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grabbaskets', 'root', '');
    echo "Connection successful with empty password!";
} catch (PDOException $e) {
    echo "Connection failed with empty password: " . $e->getMessage();
}
?>

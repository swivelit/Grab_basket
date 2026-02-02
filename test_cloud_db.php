<?php
$host = 'db-a044784e-ba1f-4e83-9a1d-c90ab7e43d66.ap-southeast-1.db.laravel.cloud';
$db   = 'main';
$user = 'zp843aq36waqo5we';
$pass = 'QTcZfT09dHUWxAA4XAM6';
$port = 3306;

echo "Testing connection to $host:$port...\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "SUCCESS: Connection established!\n";
} catch (\PDOException $e) {
    echo "ERROR: Connection failed.\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Message: " . $e->getMessage() . "\n";
}
?>

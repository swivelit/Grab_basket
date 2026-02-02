<?php
// Test image upload endpoint
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$uploadedFile = $_FILES['image'];
$name = $_POST['name'] ?? 'Test Product';
$price = $_POST['price'] ?? '100';

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($uploadedFile['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    exit;
}

// Validate file size (max 2MB)
if ($uploadedFile['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 2MB.']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/storage/test-uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
$filename = 'test_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully!',
        'data' => [
            'filename' => $filename,
            'size' => $uploadedFile['size'],
            'type' => $uploadedFile['type'],
            'url' => '/storage/test-uploads/' . $filename,
            'product_name' => $name,
            'price' => $price
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
}
?>
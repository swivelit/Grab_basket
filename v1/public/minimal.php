<?php

// Direct minimal test without Laravel bootstrap
echo json_encode([
    'status' => 'PHP Working',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'php_version' => phpversion(),
    'message' => 'Direct PHP execution successful'
]);

?>
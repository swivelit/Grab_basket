<!DOCTYPE html>
<html>
<head>
    <title>Search Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Search Functionality Diagnostic</h1>
    <?php
    try {
        echo "<div class='info'><strong>Testing search endpoint...</strong></div><br>";
        
        // Check if running
        echo "<div class='success'>✓ PHP is running (version: " . PHP_VERSION . ")</div>";
        
        // Check Laravel
        if (file_exists(__DIR__.'/../vendor/autoload.php')) {
            echo "<div class='success'>✓ Laravel vendor directory exists</div>";
        } else {
            echo "<div class='error'>✗ Laravel vendor directory missing</div>";
        }
        
        // Check routes file
        if (file_exists(__DIR__.'/../routes/web.php')) {
            echo "<div class='success'>✓ Routes file exists</div>";
        } else {
            echo "<div class='error'>✗ Routes file missing</div>";
        }
        
        // Check controller
        if (file_exists(__DIR__.'/../app/Http/Controllers/BuyerController.php')) {
            echo "<div class='success'>✓ BuyerController exists</div>";
        } else {
            echo "<div class='error'>✗ BuyerController missing</div>";
        }
        
        // Check view
        if (file_exists(__DIR__.'/../resources/views/buyer/products.blade.php')) {
            echo "<div class='success'>✓ Products view exists</div>";
        } else {
            echo "<div class='error'>✗ Products view missing</div>";
        }
        
        // Check .env
        if (file_exists(__DIR__.'/../.env')) {
            echo "<div class='success'>✓ .env file exists</div>";
        } else {
            echo "<div class='error'>✗ .env file missing</div>";
        }
        
        echo "<br><div class='info'><strong>Route Test:</strong></div>";
        echo "<p>Try accessing: <a href='/products?q=test'>/products?q=test</a></p>";
        
        echo "<br><div class='info'><strong>Recommendation:</strong></div>";
        echo "<p>1. Clear all caches: <code>php artisan cache:clear && php artisan view:clear && php artisan config:clear</code></p>";
        echo "<p>2. Check Laravel logs: <code>storage/logs/laravel.log</code></p>";
        echo "<p>3. Ensure database connection works</p>";
        
    } catch (\Exception $e) {
        echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
</body>
</html>

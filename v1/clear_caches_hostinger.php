<!DOCTYPE html>
<html>
<head>
    <title>Clear Cache - GrabBaskets</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .command {
            background: #f8f9fa;
            padding: 10px;
            border-left: 3px solid #6c757d;
            margin: 10px 0;
            font-family: monospace;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #2980b9;
        }
        .security-notice {
            background: #ffebee;
            border: 2px solid #f44336;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Clear Laravel Caches</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
            echo "<div class='warning'><strong>⚙️ Starting Cache Clearing Process...</strong></div>";
            
            $commands = [
                'config:clear' => 'Configuration Cache',
                'cache:clear' => 'Application Cache',
                'route:clear' => 'Route Cache',
                'view:clear' => 'View Cache',
                'optimize:clear' => 'Optimization Cache',
            ];
            
            foreach ($commands as $cmd => $description) {
                exec("php artisan {$cmd} 2>&1", $output, $return);
                
                if ($return === 0) {
                    echo "<div class='success'>✅ {$description} cleared successfully</div>";
                } else {
                    echo "<div class='error'>❌ Failed to clear {$description}: " . implode('<br>', $output) . "</div>";
                }
                $output = [];
            }
            
            // Verify APP_URL
            echo "<div class='command'><strong>Verifying APP_URL configuration...</strong></div>";
            
            require __DIR__ . '/vendor/autoload.php';
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            
            $appUrl = config('app.url');
            
            if (strpos($appUrl, 'grabbaskets.com') !== false) {
                echo "<div class='success'>✅ APP_URL is correctly set to: <strong>{$appUrl}</strong></div>";
            } else {
                echo "<div class='error'>⚠️ APP_URL is set to: <strong>{$appUrl}</strong><br>";
                echo "It should be: <strong>https://grabbaskets.com</strong><br>";
                echo "Please update your .env file!</div>";
            }
            
            echo "<div class='success'><strong>✅ Cache clearing complete!</strong><br>";
            echo "All pages should now use: <strong>https://grabbaskets.com</strong></div>";
            
            echo "<div class='security-notice'>🔒 SECURITY REMINDER: Delete this file after use!<br>";
            echo "File to delete: clear_caches_hostinger.php</div>";
        } else {
        ?>
        
        <div class="warning">
            <strong>⚠️ What does this do?</strong><br>
            This script clears all Laravel caches to ensure your site uses the correct domain (grabbaskets.com instead of grabbaskets.laravel.cloud)
        </div>
        
        <div class="command">
            <strong>Current Issue:</strong><br>
            URLs showing as: https://grabbaskets.laravel.cloud/<br>
            Should be: https://grabbaskets.com/
        </div>
        
        <div class="command">
            <strong>This will clear:</strong><br>
            ✓ Configuration Cache<br>
            ✓ Application Cache<br>
            ✓ Route Cache<br>
            ✓ View Cache<br>
            ✓ Optimization Cache
        </div>
        
        <form method="POST">
            <button type="submit" name="clear_cache">🚀 Clear All Caches Now</button>
        </form>
        
        <div class="security-notice">
            🔒 <strong>IMPORTANT SECURITY NOTICE:</strong><br>
            After running this script, DELETE this file from your server immediately for security!
        </div>
        
        <?php } ?>
    </div>
</body>
</html>

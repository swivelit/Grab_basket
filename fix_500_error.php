<?php
/**
 * Emergency Cache Clear Script for Hostinger
 * Upload this file to public_html/ and visit it in your browser
 * URL: https://grabbaskets.com/fix_500_error.php
 * 
 * DELETE THIS FILE AFTER RUNNING!
 */

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Category 500 Error - GrabBaskets</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-info {
            background: #e3f2fd;
            border-color: #2196f3;
            color: #1565c0;
        }
        .alert-success {
            background: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }
        .alert-danger {
            background: #ffebee;
            border-color: #f44336;
            color: #c62828;
        }
        .alert-warning {
            background: #fff3e0;
            border-color: #ff9800;
            color: #e65100;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .step {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .step.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .step.error {
            background: #ffebee;
            color: #c62828;
        }
        .icon {
            font-size: 24px;
            margin-right: 8px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        .footer strong {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix Category 500 Error</h1>
            <p>Clear Laravel caches to resolve the issue</p>
        </div>
        
        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_now'])) {
                echo '<div class="alert alert-info">
                    <span class="icon">⚙️</span>
                    <strong>Starting fix process...</strong>
                </div>';
                
                // Change to Laravel root directory
                chdir(__DIR__);
                
                $allSuccess = true;
                
                // Step 1: Run migrations safely
                echo '<h3 style="margin-top: 20px; color: #667eea;">Step 1: Running Database Migrations</h3>';
                echo '<div class="step">';
                echo "Running migrations...\n";
                
                exec("php artisan migrate --force 2>&1", $output, $return);
                
                if ($return === 0) {
                    echo '<div class="step success">✅ Migrations completed successfully!</div>';
                } else {
                    echo '<div class="step error">⚠️ Migration output:</div>';
                    echo '<pre style="max-height: 200px; overflow-y: auto;">' . implode("\n", $output) . '</pre>';
                    // Don't fail completely on migration warnings
                }
                $output = [];
                echo '</div>';
                
                // Step 2: Clear all caches
                echo '<h3 style="margin-top: 20px; color: #667eea;">Step 2: Clearing All Caches</h3>';
                
                $commands = [
                    'config:clear' => 'Configuration Cache',
                    'cache:clear' => 'Application Cache',
                    'route:clear' => 'Route Cache',
                    'view:clear' => 'Compiled Views (Critical for 500 fix)',
                    'optimize:clear' => 'All Optimization Caches',
                ];
                
                foreach ($commands as $cmd => $description) {
                    echo '<div class="step">';
                    echo "Clearing {$description}...\n";
                    
                    exec("php artisan {$cmd} 2>&1", $output, $return);
                    
                    if ($return === 0) {
                        echo '<div class="step success">✅ ' . $description . ' cleared successfully!</div>';
                    } else {
                        echo '<div class="step error">❌ Failed to clear ' . $description . '</div>';
                        echo '<pre>' . implode("\n", $output) . '</pre>';
                        $allSuccess = false;
                    }
                    
                    $output = [];
                    echo '</div>';
                }
                
                // Step 3: Verify APP_URL
                echo '<h3 style="margin-top: 20px; color: #667eea;">Step 3: Verifying Configuration</h3>';
                echo '<div class="step">';
                
                require __DIR__ . '/vendor/autoload.php';
                $app = require_once __DIR__ . '/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                
                $appUrl = config('app.url');
                
                if (strpos($appUrl, 'grabbaskets.com') !== false) {
                    echo '<div class="step success">✅ APP_URL is correctly set: ' . $appUrl . '</div>';
                } else {
                    echo '<div class="step error">⚠️ APP_URL is: ' . $appUrl . ' (should be grabbaskets.com)</div>';
                }
                echo '</div>';
                
                if ($allSuccess) {
                    echo '<div class="alert alert-success">
                        <span class="icon">✅</span>
                        <strong>Success!</strong> All fixes have been applied.
                        <br><br>
                        <strong>Next steps:</strong>
                        <ol style="margin-left: 20px; margin-top: 10px;">
                            <li>Test these pages:
                                <ul style="margin-left: 20px; margin-top: 5px;">
                                    <li><a href="https://grabbaskets.com/" target="_blank">Homepage</a></li>
                                    <li><a href="https://grabbaskets.com/buyer/category/24" target="_blank">Category 24</a></li>
                                    <li><a href="https://grabbaskets.com/buyer/category/5" target="_blank">Category 5</a></li>
                                </ul>
                            </li>
                            <li><strong style="color: #c62828;">DELETE THIS FILE IMMEDIATELY</strong> (fix_500_error.php) for security</li>
                        </ol>
                    </div>';
                } else {
                    echo '<div class="alert alert-danger">
                        <span class="icon">❌</span>
                        <strong>Some errors occurred.</strong> The site may still work, but you should verify all pages. If issues persist, contact support.
                    </div>';
                }
                
            } else {
                ?>
                <div class="alert alert-info">
                    <span class="icon">ℹ️</span>
                    <strong>About this fix:</strong><br>
                    The 500 error on category pages is caused by stale compiled views in Laravel's cache.
                    This script will run migrations safely and clear all caches to fix the issue.
                </div>
                
                <div class="alert alert-warning">
                    <span class="icon">⚠️</span>
                    <strong>Important:</strong> Delete this file immediately after running it!
                </div>
                
                <form method="POST">
                    <button type="submit" name="fix_now" value="1">
                        🚀 Run Migrations & Clear All Caches
                    </button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; font-size: 13px;">
                    <strong>What this will do:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li><strong>Step 1:</strong> Run database migrations safely (fixes table conflicts)</li>
                        <li><strong>Step 2:</strong> Clear configuration cache</li>
                        <li><strong>Step 3:</strong> Clear application cache</li>
                        <li><strong>Step 4:</strong> Clear route cache</li>
                        <li><strong>Step 5:</strong> Clear compiled view files (fixes the 500 error)</li>
                        <li><strong>Step 6:</strong> Clear all optimization caches</li>
                        <li><strong>Step 7:</strong> Verify APP_URL configuration</li>
                    </ul>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div class="footer">
            <strong>🔒 SECURITY REMINDER:</strong> Delete this file (fix_500_error.php) after use!<br>
            GrabBaskets &copy; 2025
        </div>
    </div>
</body>
</html>

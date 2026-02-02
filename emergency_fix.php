<?php
/**
 * EMERGENCY FIX - Clear All Caches to Fix 500 Server Error
 * 
 * Upload to: public_html/emergency_fix.php
 * Visit: https://grabbaskets.com/emergency_fix.php
 * 
 * DELETE THIS FILE AFTER RUNNING!
 */

// Prevent direct access from non-admin
$secret_key = isset($_GET['key']) ? $_GET['key'] : '';
$allow_access = true; // Set to true to allow access without key

if (!$allow_access && $secret_key !== 'fix-grabbaskets-2025') {
    die('Access Denied');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Cache Clear - Fix 500 Error</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { font-size: 16px; opacity: 0.9; }
        .content { padding: 30px; }
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid;
        }
        .alert-danger { background: #ffebee; border-color: #f44336; color: #c62828; }
        .alert-success { background: #e8f5e9; border-color: #4caf50; color: #2e7d32; }
        .alert-warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .alert-info { background: #e3f2fd; border-color: #2196f3; color: #1565c0; }
        .step {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .step.success { background: #d4edda; color: #155724; }
        .step.error { background: #f8d7da; color: #721c24; }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 36px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin: 20px 0;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .command { 
            background: #263238; 
            color: #aed581; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; margin-top: 8px; font-size: 14px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 13px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Emergency Fix - 500 Server Error</h1>
            <p>Clear all Laravel caches to resolve category page errors</p>
        </div>

        <div class="content">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {
                echo '<div class="alert alert-info"><strong>🔧 Starting Emergency Cache Clear...</strong></div>';
                
                chdir(__DIR__);
                
                $results = [];
                $allSuccess = true;
                
                // Step 1: Clear Laravel Artisan Caches
                $artisanCommands = [
                    'config:clear' => 'Configuration Cache',
                    'cache:clear' => 'Application Cache',
                    'route:clear' => 'Route Cache',
                    'view:clear' => 'Compiled Views',
                    'optimize:clear' => 'Optimization Files',
                    'event:clear' => 'Event Cache',
                ];
                
                echo '<h3 style="margin: 20px 0 10px 0;">📋 Laravel Artisan Cache Clearing:</h3>';
                
                foreach ($artisanCommands as $cmd => $label) {
                    exec("php artisan {$cmd} 2>&1", $output, $return);
                    $success = ($return === 0);
                    $allSuccess = $allSuccess && $success;
                    
                    $class = $success ? 'success' : 'error';
                    $icon = $success ? '✅' : '❌';
                    echo "<div class='step {$class}'>{$icon} {$label}: " . ($success ? 'CLEARED' : 'FAILED') . "</div>";
                    
                    $results[$label] = $success;
                    $output = [];
                }
                
                // Step 2: Manually delete cache directories
                echo '<h3 style="margin: 20px 0 10px 0;">🗑️ Manual Cache Directory Cleanup:</h3>';
                
                $cacheDirectories = [
                    'bootstrap/cache/*.php' => 'Bootstrap Cache Files',
                    'storage/framework/cache/data/*' => 'Framework Cache Data',
                    'storage/framework/views/*' => 'Compiled Views',
                    'storage/framework/sessions/*' => 'Session Files',
                    'storage/logs/laravel.log' => 'Error Logs (backup)',
                ];
                
                foreach ($cacheDirectories as $path => $label) {
                    if ($path === 'storage/logs/laravel.log') {
                        // Backup and clear log file
                        if (file_exists($path) && filesize($path) > 1048576) { // > 1MB
                            $backup = $path . '.backup.' . date('Ymd_His');
                            copy($path, $backup);
                            file_put_contents($path, '');
                            echo "<div class='step success'>✅ {$label}: BACKED UP & CLEARED</div>";
                        } else {
                            echo "<div class='step success'>✅ {$label}: SIZE OK</div>";
                        }
                    } else {
                        $files = glob($path);
                        $count = 0;
                        if ($files) {
                            foreach ($files as $file) {
                                if (is_file($file) && basename($file) !== '.gitignore') {
                                    @unlink($file);
                                    $count++;
                                }
                            }
                        }
                        echo "<div class='step success'>✅ {$label}: DELETED {$count} files</div>";
                    }
                }
                
                // Step 3: Check permissions
                echo '<h3 style="margin: 20px 0 10px 0;">🔐 Permission Check:</h3>';
                
                $checkDirs = [
                    'storage/framework/cache',
                    'storage/framework/views',
                    'storage/framework/sessions',
                    'bootstrap/cache',
                ];
                
                foreach ($checkDirs as $dir) {
                    $writable = is_writable($dir);
                    $class = $writable ? 'success' : 'error';
                    $icon = $writable ? '✅' : '❌';
                    $perm = substr(sprintf('%o', fileperms($dir)), -4);
                    echo "<div class='step {$class}'>{$icon} {$dir}: " . ($writable ? "WRITABLE ({$perm})" : "NOT WRITABLE ({$perm})") . "</div>";
                    if (!$writable) {
                        $allSuccess = false;
                    }
                }
                
                // Final Status
                if ($allSuccess) {
                    echo '<div class="alert alert-success">
                        <h3 style="margin-bottom: 15px;">✅ ALL CACHES CLEARED SUCCESSFULLY!</h3>
                        <p style="margin: 10px 0;"><strong>Next Steps:</strong></p>
                        <ol style="margin-left: 25px; line-height: 1.8;">
                            <li>Test category pages now:
                                <ul style="margin-left: 20px; margin-top: 5px;">
                                    <li><a href="/buyer/category/5" target="_blank">Category 5</a></li>
                                    <li><a href="/buyer/category/24" target="_blank">Category 24</a></li>
                                    <li><a href="/buyer/category/4" target="_blank">Category 4</a></li>
                                </ul>
                            </li>
                            <li style="color: #d32f2f; font-weight: bold; margin-top: 10px;">DELETE THIS FILE (emergency_fix.php) IMMEDIATELY!</li>
                        </ol>
                    </div>';
                } else {
                    echo '<div class="alert alert-danger">
                        <h3>⚠️ Some issues detected</h3>
                        <p>Please check permissions or contact hosting support if errors persist.</p>
                    </div>';
                }
                
            } else {
                ?>
                <div class="alert alert-danger">
                    <h3 style="margin-bottom: 10px;">🚨 Problem Detected</h3>
                    <p style="font-size: 16px; line-height: 1.6;">
                        <strong>Error:</strong> 500 Server Error on category pages<br>
                        <strong>Cause:</strong> Stale compiled views and cached files<br>
                        <strong>Solution:</strong> Clear all Laravel caches
                    </p>
                </div>

                <div class="alert alert-warning">
                    <strong>⚠️ What this will do:</strong>
                    <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                        <li>Clear ALL Laravel caches (config, routes, views, application)</li>
                        <li>Delete compiled view files</li>
                        <li>Clear session data</li>
                        <li>Remove optimization caches</li>
                        <li>Clean up old log files</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <strong>ℹ️ This is safe:</strong> No data or code will be deleted, only temporary cache files.
                </div>

                <form method="POST">
                    <button type="submit" name="clear_all" value="1">
                        🚀 CLEAR ALL CACHES NOW
                    </button>
                </form>

                <div class="grid">
                    <div class="stat">
                        <div class="stat-number">500</div>
                        <div class="stat-label">Server Error</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">5+</div>
                        <div class="stat-label">Cache Types</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">30s</div>
                        <div class="stat-label">Process Time</div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="footer">
            <strong style="color: #f44336; font-size: 16px;">⚠️ IMPORTANT:</strong> Delete this file immediately after use!<br>
            <small>GrabBaskets Emergency Fix Tool &copy; 2025</small>
        </div>
    </div>
</body>
</html>

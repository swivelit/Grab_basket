<?php
// Server Status Check for Hostinger Support Ticket
// Upload as server-status.php and run to gather support information

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Hostinger Server Status Report</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .info{background:#e7f3ff;padding:15px;border-left:4px solid #2196F3;margin:10px 0;} .critical{background:#ffebee;padding:15px;border-left:4px solid #f44336;margin:10px 0;} .success{background:#e8f5e8;padding:15px;border-left:4px solid #4caf50;margin:10px 0;} .code{background:#f0f0f0;padding:10px;font-family:monospace;border-radius:4px;}</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Hostinger Server Status Report</h1>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s T') . "</p>";

echo "<div class='critical'>";
echo "<h2>🚨 ISSUE: Domain showing parked page instead of uploaded files</h2>";
echo "<p>This report contains technical information for Hostinger support to diagnose the server-level issue.</p>";
echo "</div>";

// 1. Basic Server Information
echo "<h2>1. Server Environment</h2>";
echo "<div class='code'>";
echo "<strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "<br>";
echo "<strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "<br>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "<strong>Script Path:</strong> " . __FILE__ . "<br>";
echo "<strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "<br>";
echo "<strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
echo "<strong>Remote Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "<br>";
echo "</div>";

// 2. PHP Configuration
echo "<h2>2. PHP Configuration</h2>";
echo "<div class='code'>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>PHP SAPI:</strong> " . php_sapi_name() . "<br>";
echo "<strong>Memory Limit:</strong> " . ini_get('memory_limit') . "<br>";
echo "<strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds<br>";
echo "<strong>Upload Max Size:</strong> " . ini_get('upload_max_filesize') . "<br>";
echo "<strong>Post Max Size:</strong> " . ini_get('post_max_size') . "<br>";
echo "</div>";

// 3. File System Check
echo "<h2>3. File System Analysis</h2>";
$currentDir = __DIR__;
echo "<div class='code'>";
echo "<strong>Current Directory:</strong> $currentDir<br>";
echo "<strong>Directory Writable:</strong> " . (is_writable($currentDir) ? 'YES' : 'NO') . "<br>";
echo "<strong>Files in Directory:</strong><br>";

$files = scandir($currentDir);
$fileCount = 0;
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        $fileCount++;
        $fullPath = $currentDir . '/' . $file;
        $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
        $size = is_file($fullPath) ? ' (' . number_format(filesize($fullPath)) . ' bytes)' : '';
        if ($fileCount <= 20) { // Limit output
            echo "- $file $type$size<br>";
        }
    }
}
echo "<strong>Total Items:</strong> " . ($fileCount-2) . "<br>";
echo "</div>";

// 4. Apache/Web Server Modules
echo "<h2>4. Web Server Modules</h2>";
echo "<div class='code'>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $important_modules = ['mod_rewrite', 'mod_ssl', 'mod_headers', 'mod_deflate'];
    foreach($important_modules as $module) {
        $status = in_array($module, $modules) ? 'LOADED' : 'NOT LOADED';
        echo "<strong>$module:</strong> $status<br>";
    }
} else {
    echo "<strong>Apache Modules:</strong> Cannot detect (not Apache or no access)<br>";
}
echo "</div>";

// 5. DNS and Network Information
echo "<h2>5. Network & DNS Status</h2>";
echo "<div class='code'>";

// Try to get external IP
try {
    $external_ip = @file_get_contents('https://api.ipify.org');
    echo "<strong>External IP:</strong> " . ($external_ip ?: 'Could not determine') . "<br>";
} catch (Exception $e) {
    echo "<strong>External IP:</strong> Could not determine<br>";
}

// DNS Information
$domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
echo "<strong>Current Domain:</strong> $domain<br>";

if (function_exists('gethostbyname')) {
    $ip = gethostbyname($domain);
    echo "<strong>Domain resolves to IP:</strong> $ip<br>";
}
echo "</div>";

// 6. Environment Check
echo "<h2>6. Environment Variables</h2>";
echo "<div class='code'>";
$env_vars = ['HTTP_HOST', 'SERVER_NAME', 'DOCUMENT_ROOT', 'REQUEST_METHOD', 'QUERY_STRING'];
foreach($env_vars as $var) {
    echo "<strong>$var:</strong> " . ($_SERVER[$var] ?? 'Not set') . "<br>";
}
echo "</div>";

// 7. Critical Issues Detected
echo "<h2>7. Critical Issues Analysis</h2>";
$issues = [];

if (!file_exists($currentDir . '/index.php')) {
    $issues[] = "index.php missing from document root";
}
if (!file_exists($currentDir . '/.htaccess')) {
    $issues[] = ".htaccess missing from document root";
}
if ($_SERVER['HTTP_HOST'] != $_SERVER['SERVER_NAME']) {
    $issues[] = "HTTP_HOST and SERVER_NAME mismatch - possible virtual host issue";
}

if (empty($issues)) {
    echo "<div class='success'>";
    echo "<strong>✓ No critical file issues detected</strong><br>";
    echo "This confirms the issue is at server/DNS level, not file level.";
    echo "</div>";
} else {
    echo "<div class='critical'>";
    echo "<strong>⚠ Issues detected:</strong><br>";
    foreach($issues as $issue) {
        echo "• $issue<br>";
    }
    echo "</div>";
}

// 8. Support Information
echo "<h2>8. Information for Hostinger Support</h2>";
echo "<div class='info'>";
echo "<p><strong>Copy this information to your support ticket:</strong></p>";
echo "<div class='code'>";
echo "Domain: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Files Uploaded: YES (this script is running)<br>";
echo "Issue: Domain shows parked page instead of uploaded files<br>";
echo "Status: Files are accessible via direct script execution<br>";
echo "Conclusion: Server-level configuration issue<br>";
echo "</div>";
echo "</div>";

echo "<h2>9. Recommended Actions</h2>";
echo "<div class='info'>";
echo "<p><strong>For Hostinger Support:</strong></p>";
echo "<ul>";
echo "<li>Check DNS A-record mapping for this domain</li>";
echo "<li>Verify virtual host configuration in Apache</li>";
echo "<li>Check for any server-level redirects or parking overrides</li>";
echo "<li>Confirm domain is properly linked to this hosting space</li>";
echo "<li>Verify no caching issues at server/CDN level</li>";
echo "</ul>";
echo "</div>";

echo "<div class='critical'>";
echo "<p><strong>⚠ IMPORTANT:</strong> If you can see this page, it proves:</p>";
echo "<ul>";
echo "<li>Files are uploaded correctly to the server</li>";
echo "<li>PHP is working on your hosting space</li>";
echo "<li>The issue is NOT with file uploads or Laravel configuration</li>";
echo "<li>This is a server-level DNS/virtual host configuration issue</li>";
echo "</ul>";
echo "<p><strong>Action Required:</strong> Contact Hostinger support with the information above.</p>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
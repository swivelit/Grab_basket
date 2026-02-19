<?php
// Simple .htaccess functionality test
// Upload as htaccess-test.php to public_html/

echo "<!DOCTYPE html><html><head><title>Htaccess Test</title></head><body>";
echo "<h1>🔧 .htaccess Functionality Test</h1>";

// Test if .htaccess is working
echo "<h2>1. .htaccess File Check</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<span style='color:green'>✓ .htaccess file exists</span><br>";
    
    $htaccessContent = file_get_contents(__DIR__ . '/.htaccess');
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "<span style='color:green'>✓ .htaccess contains rewrite rules</span><br>";
    } else {
        echo "<span style='color:red'>✗ .htaccess missing rewrite rules</span><br>";
    }
} else {
    echo "<span style='color:red'>✗ .htaccess file missing</span><br>";
}

// Test mod_rewrite
echo "<h2>2. Apache mod_rewrite Test</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<span style='color:green'>✓ mod_rewrite is loaded</span><br>";
    } else {
        echo "<span style='color:red'>✗ mod_rewrite not loaded</span><br>";
    }
} else {
    echo "<span style='color:orange'>⚠ Cannot check Apache modules (might still work)</span><br>";
}

// Test URL rewriting
echo "<h2>3. URL Rewrite Test</h2>";
echo "<p>Try accessing these URLs:</p>";
echo "<ul>";
echo "<li><a href='/htaccess-test.php'>Direct access (should work)</a></li>";
echo "<li><a href='/non-existent-page'>Non-existent page (should redirect to Laravel if .htaccess works)</a></li>";
echo "</ul>";

// Test Laravel routing
echo "<h2>4. Laravel Integration Test</h2>";
if (file_exists(__DIR__ . '/index.php')) {
    echo "<span style='color:green'>✓ index.php exists</span><br>";
    echo "<p><strong>Test Laravel:</strong> <a href='/'>Visit homepage</a></p>";
} else {
    echo "<span style='color:red'>✗ index.php missing</span><br>";
}

echo "<h2>5. Recommendations</h2>";
echo "<div style='background:#f0f8ff;padding:15px;border-left:4px solid #007acc;'>";
echo "<strong>If .htaccess is working correctly:</strong><br>";
echo "• Non-existent URLs should redirect to Laravel<br>";
echo "• Pretty URLs should work without index.php<br>";
echo "• Static files (CSS, JS, images) should load directly<br><br>";
echo "<strong>If .htaccess is NOT working:</strong><br>";
echo "• You'll get 404 errors for Laravel routes<br>";
echo "• Only direct file access will work<br>";
echo "• Contact Hostinger to enable mod_rewrite<br>";
echo "</div>";

echo "</body></html>";
?>